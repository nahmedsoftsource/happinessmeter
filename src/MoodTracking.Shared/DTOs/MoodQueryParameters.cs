using System.ComponentModel.DataAnnotations;

namespace MoodTracking.Shared.DTOs;

/// <summary>
/// Query parameters for filtering and paginating mood events.
/// </summary>
public class MoodQueryParameters
{
    private const int MaxPageSize = 100;
    private int _pageSize = 50;

    /// <summary>
    /// Start date for filtering (inclusive). Format: yyyy-MM-dd or ISO 8601.
    /// </summary>
    public DateTime? StartDate { get; set; }

    /// <summary>
    /// End date for filtering (inclusive). Format: yyyy-MM-dd or ISO 8601.
    /// </summary>
    public DateTime? EndDate { get; set; }

    /// <summary>
    /// Filter by Windows username (partial match supported).
    /// </summary>
    [StringLength(256)]
    public string? Username { get; set; }

    /// <summary>
    /// Filter by domain (exact match).
    /// </summary>
    [StringLength(256)]
    public string? Domain { get; set; }

    /// <summary>
    /// Filter by machine name (partial match supported).
    /// </summary>
    [StringLength(256)]
    public string? MachineName { get; set; }

    /// <summary>
    /// Filter by mood type.
    /// </summary>
    [EnumDataType(typeof(MoodType))]
    public MoodType? Mood { get; set; }

    /// <summary>
    /// Page number (1-based). Default is 1.
    /// </summary>
    [Range(1, int.MaxValue)]
    public int Page { get; set; } = 1;

    /// <summary>
    /// Number of items per page. Default is 50, max is 100.
    /// </summary>
    [Range(1, MaxPageSize)]
    public int PageSize
    {
        get => _pageSize;
        set => _pageSize = Math.Min(value, MaxPageSize);
    }
}
