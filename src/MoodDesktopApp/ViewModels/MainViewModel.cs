using System.Windows;
using System.Windows.Media;
using System.Windows.Threading;
using CommunityToolkit.Mvvm.ComponentModel;
using CommunityToolkit.Mvvm.Input;
using Microsoft.Extensions.Logging;
using MoodDesktopApp.Services;
using MoodTracking.Shared.DTOs;

namespace MoodDesktopApp.ViewModels;

public partial class MainViewModel : ObservableObject
{
    private readonly IMoodApiClient _apiClient;
    private readonly ISystemInfoService _systemInfo;
    private readonly IOfflineQueueService _offlineQueue;
    private readonly ISubmissionTracker _submissionTracker;
    private readonly ILogger<MainViewModel> _logger;
    private readonly DispatcherTimer _closeTimer;

    private static readonly Brush DefaultBackground = new SolidColorBrush(Color.FromRgb(255, 255, 255));
    private static readonly Brush DefaultBorder = new SolidColorBrush(Color.FromRgb(224, 224, 224));
    private static readonly Brush SelectedBackground = new SolidColorBrush(Color.FromRgb(187, 222, 251));
    private static readonly Brush SelectedBorder = new SolidColorBrush(Color.FromRgb(33, 150, 243));
    private static readonly Thickness DefaultBorderThickness = new(1);
    private static readonly Thickness SelectedBorderThickness = new(2);

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

        _closeTimer = new DispatcherTimer { Interval = TimeSpan.FromSeconds(2) };
        _closeTimer.Tick += (s, e) =>
        {
            _closeTimer.Stop();
            CloseRequested?.Invoke(this, EventArgs.Empty);
        };

        UpdateGreeting();
        CheckAvailabilityStatus();
        ResetButtonStyles();
    }

    private void CheckAvailabilityStatus()
    {
        IsWithinOfficeHours = _submissionTracker.IsWithinOfficeHours();
        HasAlreadySubmitted = _submissionTracker.HasSubmittedRecently();

        if (!IsWithinOfficeHours)
        {
            StatusMessage = $"This service is available during office hours only ({_submissionTracker.GetOfficeHoursDisplay()}).";
            ShowMoodButtons = true;
        }
        else if (HasAlreadySubmitted)
        {
            StatusMessage = "Thank you! You have already submitted your mood today. Please come back tomorrow.";
            ShowMoodButtons = true;
        }
    }

    [ObservableProperty] private string _greeting = string.Empty;
    [ObservableProperty] private bool _isLoading;
    [ObservableProperty] private bool _showMoodButtons = true;
    [ObservableProperty] private bool _showConfirmation;
    [ObservableProperty] private string _confirmationMessage = string.Empty;
    [ObservableProperty] private bool _isOffline;
    [ObservableProperty] private bool _isWithinOfficeHours = true;
    [ObservableProperty] private bool _hasAlreadySubmitted;
    [ObservableProperty] private string _statusMessage = string.Empty;
    [ObservableProperty] private MoodType? _selectedMood;
    [ObservableProperty] private string _selectedMoodDisplay = string.Empty;
    [ObservableProperty] private string _selectedMoodEmoji = string.Empty;

    // Button properties
    [ObservableProperty] private Brush _soHappyBackground = DefaultBackground;
    [ObservableProperty] private Brush _soHappyBorder = DefaultBorder;
    [ObservableProperty] private Thickness _soHappyBorderThickness = DefaultBorderThickness;
    [ObservableProperty] private Brush _veryBusyBackground = DefaultBackground;
    [ObservableProperty] private Brush _veryBusyBorder = DefaultBorder;
    [ObservableProperty] private Thickness _veryBusyBorderThickness = DefaultBorderThickness;
    [ObservableProperty] private Brush _positiveEnergyBackground = DefaultBackground;
    [ObservableProperty] private Brush _positiveEnergyBorder = DefaultBorder;
    [ObservableProperty] private Thickness _positiveEnergyBorderThickness = DefaultBorderThickness;
    [ObservableProperty] private Brush _notInMoodBackground = DefaultBackground;
    [ObservableProperty] private Brush _notInMoodBorder = DefaultBorder;
    [ObservableProperty] private Thickness _notInMoodBorderThickness = DefaultBorderThickness;
    [ObservableProperty] private Brush _hungryBackground = DefaultBackground;
    [ObservableProperty] private Brush _hungryBorder = DefaultBorder;
    [ObservableProperty] private Thickness _hungryBorderThickness = DefaultBorderThickness;
    [ObservableProperty] private Brush _grumpyBackground = DefaultBackground;
    [ObservableProperty] private Brush _grumpyBorder = DefaultBorder;
    [ObservableProperty] private Thickness _grumpyBorderThickness = DefaultBorderThickness;
    [ObservableProperty] private Brush _sleepyBackground = DefaultBackground;
    [ObservableProperty] private Brush _sleepyBorder = DefaultBorder;
    [ObservableProperty] private Thickness _sleepyBorderThickness = DefaultBorderThickness;

    public bool CanSelectMood => !IsLoading && IsWithinOfficeHours && !HasAlreadySubmitted;
    public bool ShowStatusMessage => !IsWithinOfficeHours || HasAlreadySubmitted;
    public bool HasSelectedMood => SelectedMood.HasValue;

    [RelayCommand]
    private void SelectMood(string moodString)
    {
        if (!Enum.TryParse<MoodType>(moodString, out var mood)) return;
        SelectedMood = mood;
        UpdateSelectedMoodDisplay();
        UpdateButtonStyles();
        OnPropertyChanged(nameof(HasSelectedMood));
    }

    [RelayCommand]
    private void ClearSelection()
    {
        SelectedMood = null;
        SelectedMoodDisplay = string.Empty;
        SelectedMoodEmoji = string.Empty;
        ResetButtonStyles();
        OnPropertyChanged(nameof(HasSelectedMood));
    }

    [RelayCommand]
    private async Task SaveAndCloseAsync()
    {
        if (!SelectedMood.HasValue) return;

        IsLoading = true;
        ShowMoodButtons = false;

        try
        {
            var mood = SelectedMood.Value;
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

            var result = await _apiClient.SubmitMoodAsync(request);

            if (result.Success)
            {
                _submissionTracker.RecordSubmission();
                ShowSuccessConfirmation(result.Message ?? GetDefaultMessage(mood));
            }
            else
            {
                await SaveOfflineAsync(request);
                _submissionTracker.RecordSubmission();
                ShowOfflineConfirmation();
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Error submitting mood");
            await SaveOfflineAsync(new CreateMoodRequest
            {
                Mood = SelectedMood.Value,
                WindowsUsername = _systemInfo.Username,
                Domain = _systemInfo.Domain,
                MachineName = _systemInfo.MachineName,
                UserSid = _systemInfo.UserSid,
                ClientTimestampUtc = DateTime.UtcNow,
                AppVersion = _systemInfo.AppVersion,
                OSVersion = _systemInfo.OSVersion
            });
            _submissionTracker.RecordSubmission();
            ShowOfflineConfirmation();
        }
        finally
        {
            IsLoading = false;
        }
    }

    private void UpdateSelectedMoodDisplay()
    {
        if (!SelectedMood.HasValue) return;
        (SelectedMoodDisplay, SelectedMoodEmoji) = SelectedMood.Value switch
        {
            MoodType.SoHappy => ("So Happy", "😁"),
            MoodType.VeryBusy => ("Very Busy", "🧑‍💻"),
            MoodType.PositiveEnergy => ("Positive Energy", "⚡"),
            MoodType.NotInMood => ("Not in Mood", "😔"),
            MoodType.Hungry => ("Hungry", "🍔"),
            MoodType.Grumpy => ("Grumpy", "🤢"),
            MoodType.Sleepy => ("Sleepy", "😴"),
            _ => ("", "")
        };
    }

    private void ResetButtonStyles()
    {
        SoHappyBackground = VeryBusyBackground = PositiveEnergyBackground = NotInMoodBackground =
            HungryBackground = GrumpyBackground = SleepyBackground = DefaultBackground;
        SoHappyBorder = VeryBusyBorder = PositiveEnergyBorder = NotInMoodBorder =
            HungryBorder = GrumpyBorder = SleepyBorder = DefaultBorder;
        SoHappyBorderThickness = VeryBusyBorderThickness = PositiveEnergyBorderThickness = NotInMoodBorderThickness =
            HungryBorderThickness = GrumpyBorderThickness = SleepyBorderThickness = DefaultBorderThickness;
    }

    private void UpdateButtonStyles()
    {
        ResetButtonStyles();
        if (!SelectedMood.HasValue) return;

        Action setSelected = SelectedMood.Value switch
        {
            MoodType.SoHappy => () => { SoHappyBackground = SelectedBackground; SoHappyBorder = SelectedBorder; SoHappyBorderThickness = SelectedBorderThickness; },
            MoodType.VeryBusy => () => { VeryBusyBackground = SelectedBackground; VeryBusyBorder = SelectedBorder; VeryBusyBorderThickness = SelectedBorderThickness; },
            MoodType.PositiveEnergy => () => { PositiveEnergyBackground = SelectedBackground; PositiveEnergyBorder = SelectedBorder; PositiveEnergyBorderThickness = SelectedBorderThickness; },
            MoodType.NotInMood => () => { NotInMoodBackground = SelectedBackground; NotInMoodBorder = SelectedBorder; NotInMoodBorderThickness = SelectedBorderThickness; },
            MoodType.Hungry => () => { HungryBackground = SelectedBackground; HungryBorder = SelectedBorder; HungryBorderThickness = SelectedBorderThickness; },
            MoodType.Grumpy => () => { GrumpyBackground = SelectedBackground; GrumpyBorder = SelectedBorder; GrumpyBorderThickness = SelectedBorderThickness; },
            MoodType.Sleepy => () => { SleepyBackground = SelectedBackground; SleepyBorder = SelectedBorder; SleepyBorderThickness = SelectedBorderThickness; },
            _ => () => { }
        };
        setSelected();
    }

    private async Task SaveOfflineAsync(CreateMoodRequest request)
    {
        try { await _offlineQueue.EnqueueAsync(request); IsOffline = true; }
        catch (Exception ex) { _logger.LogError(ex, "Failed to save to offline queue"); }
    }

    private void ShowSuccessConfirmation(string message)
    {
        ConfirmationMessage = message;
        ShowConfirmation = true;
        _closeTimer.Start();
    }

    private void ShowOfflineConfirmation()
    {
        ConfirmationMessage = "Thanks for sharing! Your mood has been saved and will be synced when you're back online.";
        ShowConfirmation = true;
        IsOffline = true;
        _closeTimer.Start();
    }

    private void UpdateGreeting()
    {
        var parts = _systemInfo.Username.Split(new[] { '.', '_', '-' }, StringSplitOptions.RemoveEmptyEntries);
        var name = string.Join(" ", parts.Select(p => char.ToUpper(p[0]) + p[1..].ToLower()));
        Greeting = $"Hello, {(string.IsNullOrEmpty(name) ? "there" : name)}";
    }

    private static string GetDefaultMessage(MoodType mood) => mood switch
    {
        MoodType.SoHappy => "Awesome! Great to hear you're feeling so happy!",
        MoodType.VeryBusy => "Thanks for letting us know! Stay focused!",
        MoodType.PositiveEnergy => "Fantastic! Your positive energy is contagious!",
        MoodType.NotInMood => "Thank you for sharing. We hope things get better soon.",
        MoodType.Hungry => "Time for a snack break! Don't forget to refuel!",
        MoodType.Grumpy => "We understand. Hope your day improves!",
        MoodType.Sleepy => "Maybe grab a coffee! Thanks for checking in.",
        _ => "Thank you for sharing your mood!"
    };
}
