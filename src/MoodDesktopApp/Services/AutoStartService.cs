using Microsoft.Win32;
using Microsoft.Extensions.Logging;

namespace MoodDesktopApp.Services;

/// <summary>
/// Manages auto-start registration for the application.
/// </summary>
public interface IAutoStartService
{
    /// <summary>
    /// Checks if auto-start is enabled.
    /// </summary>
    bool IsAutoStartEnabled { get; }

    /// <summary>
    /// Enables auto-start at Windows login.
    /// </summary>
    void EnableAutoStart();

    /// <summary>
    /// Disables auto-start.
    /// </summary>
    void DisableAutoStart();

    /// <summary>
    /// Ensures auto-start is configured on first run.
    /// </summary>
    void EnsureAutoStartConfigured();
}

/// <summary>
/// Registry-based auto-start service.
/// Uses HKCU\SOFTWARE\Microsoft\Windows\CurrentVersion\Run
/// Does NOT require administrator privileges.
/// </summary>
public class AutoStartService : IAutoStartService
{
    private const string RegistryKeyPath = @"SOFTWARE\Microsoft\Windows\CurrentVersion\Run";
    private const string AppName = "HappinessMeter";
    private readonly string _executablePath;
    private readonly ILogger<AutoStartService> _logger;

    public AutoStartService(ILogger<AutoStartService> logger)
    {
        _logger = logger;
        _executablePath = Environment.ProcessPath ?? GetExecutablePath();
    }

    /// <summary>
    /// Checks if auto-start is currently enabled.
    /// </summary>
    public bool IsAutoStartEnabled
    {
        get
        {
            try
            {
                using var key = Registry.CurrentUser.OpenSubKey(RegistryKeyPath, false);
                var value = key?.GetValue(AppName) as string;
                return !string.IsNullOrEmpty(value);
            }
            catch (Exception ex)
            {
                _logger.LogWarning(ex, "Failed to check auto-start status");
                return false;
            }
        }
    }

    /// <summary>
    /// Enables auto-start at Windows login.
    /// </summary>
    public void EnableAutoStart()
    {
        try
        {
            using var key = Registry.CurrentUser.OpenSubKey(RegistryKeyPath, true);
            if (key != null)
            {
                key.SetValue(AppName, $"\"{_executablePath}\"");
                _logger.LogInformation("Auto-start enabled: {Path}", _executablePath);
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to enable auto-start");
        }
    }

    /// <summary>
    /// Disables auto-start.
    /// </summary>
    public void DisableAutoStart()
    {
        try
        {
            using var key = Registry.CurrentUser.OpenSubKey(RegistryKeyPath, true);
            if (key?.GetValue(AppName) != null)
            {
                key.DeleteValue(AppName, false);
                _logger.LogInformation("Auto-start disabled");
            }
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Failed to disable auto-start");
        }
    }

    /// <summary>
    /// Ensures auto-start is configured on first run.
    /// Called automatically during app startup.
    /// </summary>
    public void EnsureAutoStartConfigured()
    {
        if (!IsAutoStartEnabled)
        {
            _logger.LogInformation("First run detected - enabling auto-start");
            EnableAutoStart();
        }
        else
        {
            // Update path in case app was moved
            EnableAutoStart();
        }
    }

    private static string GetExecutablePath()
    {
        var assembly = System.Reflection.Assembly.GetExecutingAssembly();
        return assembly.Location.Replace(".dll", ".exe");
    }
}
