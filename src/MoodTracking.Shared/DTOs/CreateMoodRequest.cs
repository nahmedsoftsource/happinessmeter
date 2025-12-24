using System.ComponentModel.DataAnnotations;

namespace MoodTracking.Shared.DTOs;

/// <summary>
/// Request DTO for creating a new mood event.
/// </summary>
public class CreateMoodRequest
{
    /// <summary>
    /// The mood value selected by the user.
    /// </summary>
    [Required(ErrorMessage = "Mood is required.")]
    [EnumDataType(typeof(MoodType), ErrorMessage = "Mood must be one of: Happy, Unhappy, Sad.")]
    public MoodType Mood { get; set; }

    /// <summary>
    /// Windows username of the user (without domain).
    /// </summary>
    [Required(ErrorMessage = "WindowsUsername is required.")]
    [StringLength(256, ErrorMessage = "WindowsUsername cannot exceed 256 characters.")]
    public string WindowsUsername { get; set; } = string.Empty;

    /// <summary>
    /// Domain of the Windows user (e.g., CONTOSO). Null if local account.
    /// </summary>
    [StringLength(256, ErrorMessage = "Domain cannot exceed 256 characters.")]
    public string? Domain { get; set; }

    /// <summary>
    /// Name of the machine where the mood was submitted.
    /// </summary>
    [Required(ErrorMessage = "MachineName is required.")]
    [StringLength(256, ErrorMessage = "MachineName cannot exceed 256 characters.")]
    public string MachineName { get; set; } = string.Empty;

    /// <summary>
    /// Security Identifier (SID) of the Windows user. Optional.
    /// </summary>
    [StringLength(256, ErrorMessage = "UserSid cannot exceed 256 characters.")]
    public string? UserSid { get; set; }

    /// <summary>
    /// Timestamp when the mood was captured on the client (UTC).
    /// </summary>
    [Required(ErrorMessage = "ClientTimestampUtc is required.")]
    public DateTime ClientTimestampUtc { get; set; }

    /// <summary>
    /// Version of the desktop application.
    /// </summary>
    [StringLength(50, ErrorMessage = "AppVersion cannot exceed 50 characters.")]
    public string? AppVersion { get; set; }

    /// <summary>
    /// Operating system version string.
    /// </summary>
    [StringLength(256, ErrorMessage = "OSVersion cannot exceed 256 characters.")]
    public string? OSVersion { get; set; }
}
