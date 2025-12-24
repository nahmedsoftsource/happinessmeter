using MoodTracking.Infrastructure.Entities;
using MoodTracking.Shared.DTOs;

namespace MoodTracking.Infrastructure.Repositories;

/// <summary>
/// Repository interface for mood event operations.
/// </summary>
public interface IMoodRepository
{
    /// <summary>
    /// Adds a new mood event to the database.
    /// </summary>
    /// <param name="moodEvent">The mood event to add.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>The created mood event with generated ID.</returns>
    Task<MoodEvent> AddAsync(MoodEvent moodEvent, CancellationToken cancellationToken = default);

    /// <summary>
    /// Gets a mood event by its ID.
    /// </summary>
    /// <param name="id">The mood event ID.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>The mood event if found, null otherwise.</returns>
    Task<MoodEvent?> GetByIdAsync(Guid id, CancellationToken cancellationToken = default);

    /// <summary>
    /// Gets paginated mood events with optional filtering.
    /// </summary>
    /// <param name="parameters">Query parameters for filtering and pagination.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>Paginated result of mood events.</returns>
    Task<(IReadOnlyList<MoodEvent> Items, int TotalCount)> GetPagedAsync(
        MoodQueryParameters parameters,
        CancellationToken cancellationToken = default);

    /// <summary>
    /// Checks if a user has submitted a mood within the specified time window.
    /// </summary>
    /// <param name="username">Windows username.</param>
    /// <param name="domain">Domain (optional).</param>
    /// <param name="windowMinutes">Time window in minutes.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>True if a submission exists within the window.</returns>
    Task<bool> HasRecentSubmissionAsync(
        string username,
        string? domain,
        int windowMinutes = 60,
        CancellationToken cancellationToken = default);

    /// <summary>
    /// Gets the count of mood events by mood type for a date range.
    /// </summary>
    /// <param name="startDate">Start date (inclusive).</param>
    /// <param name="endDate">End date (inclusive).</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>Dictionary of mood type to count.</returns>
    Task<Dictionary<string, int>> GetMoodCountsAsync(
        DateTime startDate,
        DateTime endDate,
        CancellationToken cancellationToken = default);
}
