namespace MoodDesktopApp.Services;

/// <summary>
/// Interface for retrieving Windows system information.
/// </summary>
public interface ISystemInfoService
{
    /// <summary>
    /// Gets the current Windows username (without domain).
    /// </summary>
    string Username { get; }

    /// <summary>
    /// Gets the Windows domain name (if domain-joined).
    /// </summary>
    string? Domain { get; }

    /// <summary>
    /// Gets the local machine name.
    /// </summary>
    string MachineName { get; }

    /// <summary>
    /// Gets the Windows Security Identifier (SID) of the current user.
    /// </summary>
    string? UserSid { get; }

    /// <summary>
    /// Gets the operating system version string.
    /// </summary>
    string OSVersion { get; }

    /// <summary>
    /// Gets the application version.
    /// </summary>
    string AppVersion { get; }
}
