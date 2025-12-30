using System.Windows.Threading;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using Microsoft.Extensions.Logging;
using MoodDesktopApp.Services;
using MoodTracking.Shared.DTOs;

namespace MoodDesktopApp.ViewModels;

/// <summary>
/// ViewModel for the main mood selection window.
/// </summary>
public partial class MainViewModel : ObservableObject
{
    private readonly IMoodApiClient _apiClient;
    private readonly ISystemInfoService _systemInfo;
    private readonly IOfflineQueueService _offlineQueue;
    private readonly ISubmissionTracker _submissionTracker;
    private readonly ILogger<MainViewModel> _logger;
    private readonly DispatcherTimer _closeTimer;

    public event EventHandler? CloseRequested;

    public MainViewModel(
        IMoodApiClient apiClient,
        ISystemInfoService systemInfo,
        IOfflineQueueService offlineQueue,
        ISubmissionTracker submissionTracker,
        ILogger<MainViewModel> logger)
    {
        _apiClient = apiClient;
        _systemInfo = systemInfo;
        _offlineQueue = offlineQueue;
        _submissionTracker = submissionTracker;
        _logger = logger;

        // Initialize timer for auto-close after confirmation (2 seconds)
        _closeTimer = new DispatcherTimer
        {
            Interval = TimeSpan.FromSeconds(2)
        };
        _closeTimer.Tick += (s, e) =>
        {
            _closeTimer.Stop();
            CloseRequested?.Invoke(this, EventArgs.Empty);
        };

        // Set initial values
        UpdateGreeting();
        UserInfo = $"{_systemInfo.Domain}\\{_systemInfo.Username} on {_systemInfo.MachineName}";

        // Check availability status
        CheckAvailabilityStatus();
    }

    private void CheckAvailabilityStatus()
    {
        IsWithinOfficeHours = _submissionTracker.IsWithinOfficeHours();
        HasAlreadySubmitted = _submissionTracker.HasSubmittedRecently();

        if (!IsWithinOfficeHours)
        {
            var officeHours = _submissionTracker.GetOfficeHoursDisplay();
            StatusMessage = $"This service is available during office hours only ({officeHours}).";
            ShowMoodButtons = true; // Show but disabled
            _logger.LogInformation("Outside office hours - buttons disabled");
        }
        else if (HasAlreadySubmitted)
        {
            StatusMessage = "Thank you! You have already submitted your mood today. Please come back tomorrow.";
            ShowMoodButtons = true; // Show but disabled
            _logger.LogInformation("Already submitted today - buttons disabled");
        }
    }

    [ObservableProperty]
    private string _greeting = string.Empty;

    [ObservableProperty]
    private string _userInfo = string.Empty;

    [ObservableProperty]
    private bool _isLoading;

    [ObservableProperty]
    private bool _showMoodButtons = true;

    [ObservableProperty]
    private bool _showConfirmation;

    [ObservableProperty]
    private string _confirmationMessage = string.Empty;

    [ObservableProperty]
    private bool _isOffline;

    [ObservableProperty]
    private bool _isWithinOfficeHours = true;

    [ObservableProperty]
    private bool _hasAlreadySubmitted;

    [ObservableProperty]
    private string _statusMessage = string.Empty;

    public bool CanSelectMood => !IsLoading && IsWithinOfficeHours && !HasAlreadySubmitted;

    public bool ShowStatusMessage => !IsWithinOfficeHours || HasAlreadySubmitted;

    [RelayCommand]
    private async Task SelectMoodAsync(string moodString)
    {
        if (!Enum.TryParse<MoodType>(moodString, out var mood))
        {
            _logger.LogWarning("Invalid mood value: {Mood}", moodString);
            return;
        }

        IsLoading = true;
        ShowMoodButtons = false;

        try
        {
            var request = new CreateMoodRequest
            {
                Mood = mood,
                WindowsUsername = _systemInfo.Username,
                Domain = _systemInfo.Domain,
                MachineName = _systemInfo.MachineName,
                UserSid = _systemInfo.UserSid,
                ClientTimestampUtc = DateTime.UtcNow,
                AppVersion = _systemInfo.AppVersion,
                OSVersion = _systemInfo.OSVersion
            };

            // Try to send to API
            var result = await _apiClient.SubmitMoodAsync(request);

            if (result.Success)
            {
                _logger.LogInformation("Mood {Mood} submitted successfully", mood);

                // Record submission to prevent re-prompting for 24 hours
                _submissionTracker.RecordSubmission();

                ShowSuccessConfirmation(result.Message ?? GetDefaultMessage(mood));
            }
            else
            {
                // Save offline for later sync
                _logger.LogWarning("API submission failed, saving offline: {Error}", result.ErrorMessage);
                await SaveOfflineAsync(request);

                // Still record submission even if offline (will sync later)
                _submissionTracker.RecordSubmission();

                ShowOfflineConfirmation(mood);
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error submitting mood");
            await SaveOfflineAsync(new CreateMoodRequest
            {
                Mood = mood,
                WindowsUsername = _systemInfo.Username,
                Domain = _systemInfo.Domain,
                MachineName = _systemInfo.MachineName,
                UserSid = _systemInfo.UserSid,
                ClientTimestampUtc = DateTime.UtcNow,
                AppVersion = _systemInfo.AppVersion,
                OSVersion = _systemInfo.OSVersion
            });

            // Still record submission even on error (will sync later)
            _submissionTracker.RecordSubmission();

            ShowOfflineConfirmation(mood);
        }
        finally
        {
            IsLoading = false;
        }
    }

    private async Task SaveOfflineAsync(CreateMoodRequest request)
    {
        try
        {
            await _offlineQueue.EnqueueAsync(request);
            IsOffline = true;
            _logger.LogInformation("Mood saved to offline queue");
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to save to offline queue");
        }
    }

    private void ShowSuccessConfirmation(string message)
    {
        ConfirmationMessage = message;
        ShowConfirmation = true;
        _closeTimer.Start();
    }

    private void ShowOfflineConfirmation(MoodType mood)
    {
        ConfirmationMessage = $"Thanks for sharing! Your {mood.ToString().ToLower()} mood has been saved and will be synced when you're back online.";
        ShowConfirmation = true;
        IsOffline = true;
        _closeTimer.Start();
    }

    private void UpdateGreeting()
    {
        var hour = DateTime.Now.Hour;
        var firstName = GetFirstName(_systemInfo.Username);

        Greeting = hour switch
        {
            < 12 => $"Good morning, {firstName}!",
            < 17 => $"Good afternoon, {firstName}!",
            _ => $"Good evening, {firstName}!"
        };
    }

    private static string GetFirstName(string username)
    {
        // Try to extract first name from username (e.g., "jsmith" -> "J")
        if (string.IsNullOrEmpty(username))
            return "there";

        // Capitalize first letter
        return char.ToUpper(username[0]) + username[1..].ToLower();
    }

    private static string GetDefaultMessage(MoodType mood)
    {
        return mood switch
        {
            MoodType.Happy => "Great to hear you're feeling happy! Have a wonderful day!",
            MoodType.Unhappy => "Thank you for sharing. We hope things improve for you.",
            MoodType.Sad => "We're sorry to hear that. Your feedback is appreciated.",
            _ => "Thank you for sharing your mood!"
        };
    }
}
