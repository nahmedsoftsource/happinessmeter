using System.Reflection;
using System.Security.Principal;

namespace MoodDesktopApp.Services;

/// <summary>
/// Implementation of ISystemInfoService for Windows.
/// Retrieves Windows user details, machine name, and other system information.
/// </summary>
public class SystemInfoService : ISystemInfoService
{
    private readonly Lazy<string> _username;
    private readonly Lazy<string?> _domain;
    private readonly Lazy<string> _machineName;
    private readonly Lazy<string?> _userSid;
    private readonly Lazy<string> _osVersion;
    private readonly Lazy<string> _appVersion;

    public SystemInfoService()
    {
        _username = new Lazy<string>(GetUsername);
        _domain = new Lazy<string?>(GetDomain);
        _machineName = new Lazy<string>(GetMachineName);
        _userSid = new Lazy<string?>(GetUserSid);
        _osVersion = new Lazy<string>(GetOSVersion);
        _appVersion = new Lazy<string>(GetAppVersion);
    }

    /// <summary>
    /// Gets the current Windows username (without domain).
    /// </summary>
    public string Username => _username.Value;

    /// <summary>
    /// Gets the Windows domain name (if domain-joined).
    /// </summary>
    public string? Domain => _domain.Value;

    /// <summary>
    /// Gets the local machine name.
    /// </summary>
    public string MachineName => _machineName.Value;

    /// <summary>
    /// Gets the Windows Security Identifier (SID) of the current user.
    /// </summary>
    public string? UserSid => _userSid.Value;

    /// <summary>
    /// Gets the operating system version string.
    /// </summary>
    public string OSVersion => _osVersion.Value;

    /// <summary>
    /// Gets the application version.
    /// </summary>
    public string AppVersion => _appVersion.Value;

    private static string GetUsername()
    {
        try
        {
            // Get username from Windows identity
            var identity = WindowsIdentity.GetCurrent();
            var name = identity.Name;

            // Extract username from DOMAIN\username format
            if (name.Contains('\\'))
            {
                return name.Split('\\')[1];
            }

            // Fallback to environment variable
            return Environment.UserName;
        }
        catch
        {
            return Environment.UserName;
        }
    }

    private static string? GetDomain()
    {
        try
        {
            // Get domain from Windows identity
            var identity = WindowsIdentity.GetCurrent();
            var name = identity.Name;

            // Extract domain from DOMAIN\username format
            if (name.Contains('\\'))
            {
                var domain = name.Split('\\')[0];
                // Return null if it's the local machine name
                if (!domain.Equals(Environment.MachineName, StringComparison.OrdinalIgnoreCase))
                {
                    return domain;
                }
            }

            // Try environment variable
            var envDomain = Environment.UserDomainName;
            if (!string.IsNullOrEmpty(envDomain) &&
                !envDomain.Equals(Environment.MachineName, StringComparison.OrdinalIgnoreCase))
            {
                return envDomain;
            }

            return null;
        }
        catch
        {
            return null;
        }
    }

    private static string GetMachineName()
    {
        try
        {
            return Environment.MachineName;
        }
        catch
        {
            return "UNKNOWN";
        }
    }

    private static string? GetUserSid()
    {
        try
        {
            var identity = WindowsIdentity.GetCurrent();
            return identity.User?.Value;
        }
        catch
        {
            return null;
        }
    }

    private static string GetOSVersion()
    {
        try
        {
            return Environment.OSVersion.ToString();
        }
        catch
        {
            return "Unknown";
        }
    }

    private static string GetAppVersion()
    {
        try
        {
            var version = Assembly.GetExecutingAssembly().GetName().Version;
            return version?.ToString() ?? "1.0.0.0";
        }
        catch
        {
            return "1.0.0.0";
        }
    }
}
