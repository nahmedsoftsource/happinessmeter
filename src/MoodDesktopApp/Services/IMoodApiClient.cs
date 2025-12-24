using MoodTracking.Shared.DTOs;

namespace MoodDesktopApp.Services;

/// <summary>
/// Interface for communicating with the MoodTracking API.
/// </summary>
public interface IMoodApiClient
{
    /// <summary>
    /// Submits a mood event to the API.
    /// </summary>
    /// <param name="request">The mood request.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>Result indicating success or failure.</returns>
    Task<MoodSubmissionResult> SubmitMoodAsync(CreateMoodRequest request, CancellationToken cancellationToken = default);

    /// <summary>
    /// Checks if the API is reachable.
    /// </summary>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>True if API is reachable.</returns>
    Task<bool> IsApiAvailableAsync(CancellationToken cancellationToken = default);
}

/// <summary>
/// Result of a mood submission attempt.
/// </summary>
public class MoodSubmissionResult
{
    public bool Success { get; set; }
    public Guid? Id { get; set; }
    public string? Message { get; set; }
    public string? ErrorMessage { get; set; }
    public bool IsOffline { get; set; }
}
