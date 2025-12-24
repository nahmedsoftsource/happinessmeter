namespace MoodTracking.Shared.DTOs;

/// <summary>
/// Response DTO returned after successfully creating a mood event.
/// </summary>
public class CreateMoodResponse
{
    /// <summary>
    /// Unique identifier of the created mood event.
    /// </summary>
    public Guid Id { get; set; }

    /// <summary>
    /// The mood value that was recorded.
    /// </summary>
    public MoodType Mood { get; set; }

    /// <summary>
    /// Timestamp when the server received the mood event (UTC).
    /// </summary>
    public DateTime ServerReceivedUtc { get; set; }

    /// <summary>
    /// Human-readable confirmation message.
    /// </summary>
    public string Message { get; set; } = "Mood recorded successfully";
}

/// <summary>
/// Response DTO for a single mood event in query results.
/// </summary>
public class MoodEventResponse
{
    public Guid Id { get; set; }
    public string WindowsUsername { get; set; } = string.Empty;
    public string? Domain { get; set; }
    public string MachineName { get; set; } = string.Empty;
    public string? UserSid { get; set; }
    public MoodType Mood { get; set; }
    public DateTime ClientTimestampUtc { get; set; }
    public DateTime ServerReceivedUtc { get; set; }
    public string? AppVersion { get; set; }
    public string? OSVersion { get; set; }
    public string? IPAddress { get; set; }
}

/// <summary>
/// Paginated result wrapper for list queries.
/// </summary>
/// <typeparam name="T">Type of items in the result.</typeparam>
public class PagedResult<T>
{
    /// <summary>
    /// The items in the current page.
    /// </summary>
    public IReadOnlyList<T> Items { get; set; } = Array.Empty<T>();

    /// <summary>
    /// Current page number (1-based).
    /// </summary>
    public int Page { get; set; }

    /// <summary>
    /// Number of items per page.
    /// </summary>
    public int PageSize { get; set; }

    /// <summary>
    /// Total number of items across all pages.
    /// </summary>
    public int TotalCount { get; set; }

    /// <summary>
    /// Total number of pages.
    /// </summary>
    public int TotalPages => (int)Math.Ceiling((double)TotalCount / PageSize);

    /// <summary>
    /// Whether there is a next page.
    /// </summary>
    public bool HasNextPage => Page < TotalPages;

    /// <summary>
    /// Whether there is a previous page.
    /// </summary>
    public bool HasPreviousPage => Page > 1;
}
