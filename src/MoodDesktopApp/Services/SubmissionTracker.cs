using System.IO;
using System.Text.Json;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.Logging;

namespace MoodDesktopApp.Services;

/// <summary>
/// Tracks mood submission state to prevent multiple prompts per day.
/// </summary>
public interface ISubmissionTracker
{
    /// <summary>
    /// Checks if the user should be prompted for mood today.
    /// Returns true if: within office hours AND not submitted in last 24 hours.
    /// </summary>
    bool ShouldPromptUser();

    /// <summary>
    /// Records that a mood was submitted.
    /// </summary>
    void RecordSubmission();

    /// <summary>
    /// Gets the last submission time, or null if never submitted.
    /// </summary>
    DateTime? GetLastSubmissionTime();
}

/// <summary>
/// File-based implementation of submission tracking.
/// Stores submission state in user's local app data.
/// </summary>
public class SubmissionTracker : ISubmissionTracker
{
    private readonly string _stateFilePath;
    private readonly ILogger<SubmissionTracker> _logger;
    private readonly TimeSpan _officeStartTime;
    private readonly TimeSpan _officeEndTime;
    private readonly int _cooldownHours;

    private SubmissionState? _cachedState;

    public SubmissionTracker(ILogger<SubmissionTracker> logger, IConfiguration configuration)
    {
        _logger = logger;

        // Read schedule configuration with defaults
        var officeStartHour = configuration.GetValue("Schedule:OfficeStartHour", 8);
        var officeEndHour = configuration.GetValue("Schedule:OfficeEndHour", 17);
        _cooldownHours = configuration.GetValue("Schedule:CooldownHours", 24);

        _officeStartTime = new TimeSpan(officeStartHour, 0, 0);
        _officeEndTime = new TimeSpan(officeEndHour, 0, 0);

        _logger.LogInformation(
            "Schedule configured: Office hours {Start}:00 - {End}:00, Cooldown: {Cooldown} hours",
            officeStartHour, officeEndHour, _cooldownHours);

        var appDataPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "HappinessMeter");

        Directory.CreateDirectory(appDataPath);
        _stateFilePath = Path.Combine(appDataPath, "submission_state.json");
    }

    /// <summary>
    /// Checks if the user should be prompted for mood.
    /// </summary>
    public bool ShouldPromptUser()
    {
        var now = DateTime.Now;
        var currentTime = now.TimeOfDay;

        // Check 1: Is it within office hours?
        if (currentTime < _officeStartTime || currentTime > _officeEndTime)
        {
            _logger.LogInformation(
                "Outside office hours ({CurrentTime}). Office hours: {Start} - {End}",
                currentTime.ToString(@"hh\:mm"),
                _officeStartTime.ToString(@"hh\:mm"),
                _officeEndTime.ToString(@"hh\:mm"));
            return false;
        }

        // Check 2: Is it a weekday? (Optional - uncomment to skip weekends)
        // if (now.DayOfWeek == DayOfWeek.Saturday || now.DayOfWeek == DayOfWeek.Sunday)
        // {
        //     _logger.LogInformation("Weekend - not prompting");
        //     return false;
        // }

        // Check 3: Has user already submitted within the cooldown period?
        var lastSubmission = GetLastSubmissionTime();
        if (lastSubmission.HasValue)
        {
            var hoursSinceLastSubmission = (now - lastSubmission.Value).TotalHours;
            if (hoursSinceLastSubmission < _cooldownHours)
            {
                _logger.LogInformation(
                    "Already submitted {Hours:F1} hours ago. Cooldown: {Cooldown} hours",
                    hoursSinceLastSubmission,
                    _cooldownHours);
                return false;
            }
        }

        _logger.LogInformation("User should be prompted for mood");
        return true;
    }

    /// <summary>
    /// Records that a mood was submitted.
    /// </summary>
    public void RecordSubmission()
    {
        var state = new SubmissionState
        {
            LastSubmissionUtc = DateTime.UtcNow,
            LastSubmissionLocal = DateTime.Now,
            SubmissionCount = (GetState()?.SubmissionCount ?? 0) + 1
        };

        SaveState(state);
        _logger.LogInformation("Recorded submission at {Time}", state.LastSubmissionLocal);
    }

    /// <summary>
    /// Gets the last submission time in local time.
    /// </summary>
    public DateTime? GetLastSubmissionTime()
    {
        var state = GetState();
        return state?.LastSubmissionLocal;
    }

    private SubmissionState? GetState()
    {
        if (_cachedState != null)
            return _cachedState;

        try
        {
            if (!File.Exists(_stateFilePath))
                return null;

            var json = File.ReadAllText(_stateFilePath);
            _cachedState = JsonSerializer.Deserialize<SubmissionState>(json);
            return _cachedState;
        }
        catch (Exception ex)
        {
            _logger.LogWarning(ex, "Failed to read submission state");
            return null;
        }
    }

    private void SaveState(SubmissionState state)
    {
        try
        {
            var json = JsonSerializer.Serialize(state, new JsonSerializerOptions
            {
                WriteIndented = true
            });
            File.WriteAllText(_stateFilePath, json);
            _cachedState = state;
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to save submission state");
        }
    }

    private class SubmissionState
    {
        public DateTime LastSubmissionUtc { get; set; }
        public DateTime LastSubmissionLocal { get; set; }
        public int SubmissionCount { get; set; }
    }
}
