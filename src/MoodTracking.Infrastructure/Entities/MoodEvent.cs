namespace MoodTracking.Infrastructure.Entities;

/// <summary>
/// Database entity representing a mood event.
/// </summary>
public class MoodEvent
{
    /// <summary>
    /// Unique identifier for the mood event.
    /// </summary>
    public Guid Id { get; set; }

    /// <summary>
    /// Windows username of the user.
    /// </summary>
    public string WindowsUsername { get; set; } = string.Empty;

    /// <summary>
    /// Domain of the Windows user.
    /// </summary>
    public string? Domain { get; set; }

    /// <summary>
    /// Security Identifier (SID) of the Windows user.
    /// </summary>
    public string? UserSid { get; set; }

    /// <summary>
    /// Name of the machine where the mood was submitted.
    /// </summary>
    public string MachineName { get; set; } = string.Empty;

    /// <summary>
    /// The mood value (Happy, Unhappy, Sad).
    /// </summary>
    public string Mood { get; set; } = string.Empty;

    /// <summary>
    /// Timestamp when the client captured the mood (UTC).
    /// </summary>
    public DateTime ClientTimestampUtc { get; set; }

    /// <summary>
    /// Timestamp when the server received the request (UTC).
    /// </summary>
    public DateTime ServerReceivedUtc { get; set; }

    /// <summary>
    /// Version of the desktop application.
    /// </summary>
    public string? AppVersion { get; set; }

    /// <summary>
    /// Operating system version string.
    /// </summary>
    public string? OSVersion { get; set; }

    /// <summary>
    /// IP address of the client (IPv4 or IPv6).
    /// </summary>
    public string? IPAddress { get; set; }

    /// <summary>
    /// Source of the mood event (e.g., DesktopApp, WebPortal).
    /// </summary>
    public string? Source { get; set; }

    /// <summary>
    /// Row version for optimistic concurrency.
    /// </summary>
    public byte[]? RowVersion { get; set; }

    /// <summary>
    /// Timestamp when the record was created.
    /// </summary>
    public DateTime CreatedAt { get; set; }
}
