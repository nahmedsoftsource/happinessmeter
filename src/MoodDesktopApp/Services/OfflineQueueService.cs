using System.Text.Json;
using Microsoft.Data.Sqlite;
using Microsoft.Extensions.Logging;
using MoodTracking.Shared.DTOs;

namespace MoodDesktopApp.Services;

/// <summary>
/// SQLite-based implementation of offline mood queue.
/// Stores mood events locally when API is unreachable.
/// </summary>
public class OfflineQueueService : IOfflineQueueService, IDisposable
{
    private readonly string _connectionString;
    private readonly ILogger<OfflineQueueService> _logger;
    private readonly JsonSerializerOptions _jsonOptions;
    private readonly SemaphoreSlim _lock = new(1, 1);
    private bool _initialized;

    public OfflineQueueService(ILogger<OfflineQueueService> logger)
    {
        _logger = logger;

        // Store database in user's local app data
        var appDataPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "HappinessMeter");

        Directory.CreateDirectory(appDataPath);

        var dbPath = Path.Combine(appDataPath, "offline_queue.db");
        _connectionString = $"Data Source={dbPath}";

        _jsonOptions = new JsonSerializerOptions
        {
            PropertyNamingPolicy = JsonNamingPolicy.CamelCase
        };

        _logger.LogInformation("Offline queue database path: {Path}", dbPath);
    }

    private async Task EnsureInitializedAsync()
    {
        if (_initialized) return;

        await _lock.WaitAsync();
        try
        {
            if (_initialized) return;

            await using var connection = new SqliteConnection(_connectionString);
            await connection.OpenAsync();

            var command = connection.CreateCommand();
            command.CommandText = @"
                CREATE TABLE IF NOT EXISTS PendingMoodEvents (
                    Id INTEGER PRIMARY KEY AUTOINCREMENT,
                    RequestJson TEXT NOT NULL,
                    QueuedAt TEXT NOT NULL,
                    LastAttemptAt TEXT NULL,
                    RetryCount INTEGER NOT NULL DEFAULT 0
                );

                CREATE INDEX IF NOT EXISTS IX_PendingMoodEvents_QueuedAt
                ON PendingMoodEvents (QueuedAt);
            ";
            await command.ExecuteNonQueryAsync();

            _initialized = true;
            _logger.LogDebug("Offline queue database initialized");
        }
        finally
        {
            _lock.Release();
        }
    }

    public async Task EnqueueAsync(CreateMoodRequest request)
    {
        await EnsureInitializedAsync();

        await using var connection = new SqliteConnection(_connectionString);
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = @"
            INSERT INTO PendingMoodEvents (RequestJson, QueuedAt, RetryCount)
            VALUES (@RequestJson, @QueuedAt, 0)
        ";

        var requestJson = JsonSerializer.Serialize(request, _jsonOptions);
        command.Parameters.AddWithValue("@RequestJson", requestJson);
        command.Parameters.AddWithValue("@QueuedAt", DateTime.UtcNow.ToString("O"));

        await command.ExecuteNonQueryAsync();

        _logger.LogInformation("Mood event queued for offline sync");
    }

    public async Task<IReadOnlyList<PendingMoodEvent>> GetPendingAsync()
    {
        await EnsureInitializedAsync();

        var results = new List<PendingMoodEvent>();

        await using var connection = new SqliteConnection(_connectionString);
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = @"
            SELECT Id, RequestJson, QueuedAt, LastAttemptAt, RetryCount
            FROM PendingMoodEvents
            WHERE RetryCount < 10
            ORDER BY QueuedAt ASC
            LIMIT 50
        ";

        await using var reader = await command.ExecuteReaderAsync();
        while (await reader.ReadAsync())
        {
            var requestJson = reader.GetString(1);
            var request = JsonSerializer.Deserialize<CreateMoodRequest>(requestJson, _jsonOptions);

            if (request != null)
            {
                results.Add(new PendingMoodEvent
                {
                    Id = reader.GetInt32(0),
                    Request = request,
                    QueuedAt = DateTime.Parse(reader.GetString(2)),
                    LastAttemptAt = reader.IsDBNull(3) ? null : DateTime.Parse(reader.GetString(3)),
                    RetryCount = reader.GetInt32(4)
                });
            }
        }

        return results;
    }

    public async Task MarkAsSyncedAsync(int id)
    {
        await EnsureInitializedAsync();

        await using var connection = new SqliteConnection(_connectionString);
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = "DELETE FROM PendingMoodEvents WHERE Id = @Id";
        command.Parameters.AddWithValue("@Id", id);

        await command.ExecuteNonQueryAsync();

        _logger.LogDebug("Removed synced event {Id} from offline queue", id);
    }

    public async Task MarkAsFailedAsync(int id)
    {
        await EnsureInitializedAsync();

        await using var connection = new SqliteConnection(_connectionString);
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = @"
            UPDATE PendingMoodEvents
            SET RetryCount = RetryCount + 1,
                LastAttemptAt = @LastAttemptAt
            WHERE Id = @Id
        ";
        command.Parameters.AddWithValue("@Id", id);
        command.Parameters.AddWithValue("@LastAttemptAt", DateTime.UtcNow.ToString("O"));

        await command.ExecuteNonQueryAsync();

        _logger.LogDebug("Marked event {Id} as failed, will retry", id);
    }

    public async Task<int> GetPendingCountAsync()
    {
        await EnsureInitializedAsync();

        await using var connection = new SqliteConnection(_connectionString);
        await connection.OpenAsync();

        var command = connection.CreateCommand();
        command.CommandText = "SELECT COUNT(*) FROM PendingMoodEvents WHERE RetryCount < 10";

        var result = await command.ExecuteScalarAsync();
        return Convert.ToInt32(result);
    }

    public void Dispose()
    {
        _lock.Dispose();
    }
}
