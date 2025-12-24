using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;
using MoodDesktopApp.Services;

namespace MoodDesktopApp.Workers;

/// <summary>
/// Background service that periodically syncs offline mood events to the API.
/// </summary>
public class SyncBackgroundService : BackgroundService
{
    private readonly IMoodApiClient _apiClient;
    private readonly IOfflineQueueService _offlineQueue;
    private readonly ILogger<SyncBackgroundService> _logger;
    private readonly TimeSpan _syncInterval = TimeSpan.FromMinutes(5);

    public SyncBackgroundService(
        IMoodApiClient apiClient,
        IOfflineQueueService offlineQueue,
        ILogger<SyncBackgroundService> logger)
    {
        _apiClient = apiClient;
        _offlineQueue = offlineQueue;
        _logger = logger;
    }

    protected override async Task ExecuteAsync(CancellationToken stoppingToken)
    {
        _logger.LogInformation("Sync background service started");

        // Initial delay to let app start up
        await Task.Delay(TimeSpan.FromSeconds(30), stoppingToken);

        while (!stoppingToken.IsCancellationRequested)
        {
            try
            {
                await SyncPendingEventsAsync(stoppingToken);
            }
            catch (OperationCanceledException) when (stoppingToken.IsCancellationRequested)
            {
                break;
            }
            catch (Exception ex)
            {
                _logger.LogError(ex, "Error in sync background service");
            }

            try
            {
                await Task.Delay(_syncInterval, stoppingToken);
            }
            catch (OperationCanceledException)
            {
                break;
            }
        }

        _logger.LogInformation("Sync background service stopped");
    }

    private async Task SyncPendingEventsAsync(CancellationToken cancellationToken)
    {
        // Check if API is available first
        var isAvailable = await _apiClient.IsApiAvailableAsync(cancellationToken);
        if (!isAvailable)
        {
            _logger.LogDebug("API not available, skipping sync");
            return;
        }

        var pendingEvents = await _offlineQueue.GetPendingAsync();
        if (pendingEvents.Count == 0)
        {
            _logger.LogDebug("No pending events to sync");
            return;
        }

        _logger.LogInformation("Syncing {Count} pending mood events", pendingEvents.Count);

        var successCount = 0;
        var failCount = 0;

        foreach (var pending in pendingEvents)
        {
            if (cancellationToken.IsCancellationRequested)
                break;

            try
            {
                var result = await _apiClient.SubmitMoodAsync(pending.Request, cancellationToken);

                if (result.Success)
                {
                    await _offlineQueue.MarkAsSyncedAsync(pending.Id);
                    successCount++;
                    _logger.LogDebug("Successfully synced event {Id}", pending.Id);
                }
                else if (result.ErrorMessage?.Contains("already submitted", StringComparison.OrdinalIgnoreCase) == true)
                {
                    // Duplicate detected, remove from queue
                    await _offlineQueue.MarkAsSyncedAsync(pending.Id);
                    _logger.LogDebug("Removed duplicate event {Id}", pending.Id);
                }
                else
                {
                    await _offlineQueue.MarkAsFailedAsync(pending.Id);
                    failCount++;
                    _logger.LogWarning(
                        "Failed to sync event {Id}: {Error}",
                        pending.Id,
                        result.ErrorMessage);
                }

                // Small delay between requests to avoid overwhelming the server
                await Task.Delay(TimeSpan.FromMilliseconds(500), cancellationToken);
            }
            catch (Exception ex)
            {
                await _offlineQueue.MarkAsFailedAsync(pending.Id);
                failCount++;
                _logger.LogWarning(ex, "Error syncing event {Id}", pending.Id);
            }
        }

        if (successCount > 0 || failCount > 0)
        {
            _logger.LogInformation(
                "Sync complete: {Success} succeeded, {Failed} failed",
                successCount,
                failCount);
        }
    }
}
