using MoodTracking.Shared.DTOs;

namespace MoodDesktopApp.Services;

/// <summary>
/// Interface for managing offline mood event queue.
/// </summary>
public interface IOfflineQueueService
{
    /// <summary>
    /// Adds a mood request to the offline queue.
    /// </summary>
    /// <param name="request">The mood request to queue.</param>
    Task EnqueueAsync(CreateMoodRequest request);

    /// <summary>
    /// Gets all pending mood requests from the queue.
    /// </summary>
    Task<IReadOnlyList<PendingMoodEvent>> GetPendingAsync();

    /// <summary>
    /// Removes a successfully synced event from the queue.
    /// </summary>
    /// <param name="id">The local queue ID of the event.</param>
    Task MarkAsSyncedAsync(int id);

    /// <summary>
    /// Updates the retry count and last attempt time for a failed sync.
    /// </summary>
    /// <param name="id">The local queue ID of the event.</param>
    Task MarkAsFailedAsync(int id);

    /// <summary>
    /// Gets the count of pending events.
    /// </summary>
    Task<int> GetPendingCountAsync();
}

/// <summary>
/// Represents a mood event stored in the offline queue.
/// </summary>
public class PendingMoodEvent
{
    public int Id { get; set; }
    public CreateMoodRequest Request { get; set; } = null!;
    public DateTime QueuedAt { get; set; }
    public DateTime? LastAttemptAt { get; set; }
    public int RetryCount { get; set; }
}
