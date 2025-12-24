using Microsoft.EntityFrameworkCore;
using MoodTracking.Infrastructure.Data;
using MoodTracking.Infrastructure.Entities;
using MoodTracking.Shared.DTOs;

namespace MoodTracking.Infrastructure.Repositories;

/// <summary>
/// Repository implementation for mood event operations.
/// </summary>
public class MoodRepository : IMoodRepository
{
    private readonly MoodDbContext _context;

    public MoodRepository(MoodDbContext context)
    {
        _context = context;
    }

    public async Task<MoodEvent> AddAsync(MoodEvent moodEvent, CancellationToken cancellationToken = default)
    {
        _context.MoodEvents.Add(moodEvent);
        await _context.SaveChangesAsync(cancellationToken);
        return moodEvent;
    }

    public async Task<MoodEvent?> GetByIdAsync(Guid id, CancellationToken cancellationToken = default)
    {
        return await _context.MoodEvents
            .AsNoTracking()
            .FirstOrDefaultAsync(e => e.Id == id, cancellationToken);
    }

    public async Task<(IReadOnlyList<MoodEvent> Items, int TotalCount)> GetPagedAsync(
        MoodQueryParameters parameters,
        CancellationToken cancellationToken = default)
    {
        var query = _context.MoodEvents.AsNoTracking();

        // Apply filters
        if (parameters.StartDate.HasValue)
        {
            query = query.Where(e => e.ServerReceivedUtc >= parameters.StartDate.Value);
        }

        if (parameters.EndDate.HasValue)
        {
            query = query.Where(e => e.ServerReceivedUtc <= parameters.EndDate.Value);
        }

        if (!string.IsNullOrWhiteSpace(parameters.Username))
        {
            query = query.Where(e => e.WindowsUsername.StartsWith(parameters.Username));
        }

        if (!string.IsNullOrWhiteSpace(parameters.Domain))
        {
            query = query.Where(e => e.Domain == parameters.Domain);
        }

        if (!string.IsNullOrWhiteSpace(parameters.MachineName))
        {
            query = query.Where(e => e.MachineName.StartsWith(parameters.MachineName));
        }

        if (parameters.Mood.HasValue)
        {
            var moodString = parameters.Mood.Value.ToString();
            query = query.Where(e => e.Mood == moodString);
        }

        // Get total count before pagination
        var totalCount = await query.CountAsync(cancellationToken);

        // Apply ordering and pagination
        var items = await query
            .OrderByDescending(e => e.ServerReceivedUtc)
            .Skip((parameters.Page - 1) * parameters.PageSize)
            .Take(parameters.PageSize)
            .ToListAsync(cancellationToken);

        return (items, totalCount);
    }

    public async Task<bool> HasRecentSubmissionAsync(
        string username,
        string? domain,
        int windowMinutes = 60,
        CancellationToken cancellationToken = default)
    {
        var windowStart = DateTime.UtcNow.AddMinutes(-windowMinutes);

        return await _context.MoodEvents
            .AsNoTracking()
            .AnyAsync(e =>
                e.WindowsUsername == username &&
                e.Domain == domain &&
                e.ServerReceivedUtc >= windowStart,
                cancellationToken);
    }

    public async Task<Dictionary<string, int>> GetMoodCountsAsync(
        DateTime startDate,
        DateTime endDate,
        CancellationToken cancellationToken = default)
    {
        return await _context.MoodEvents
            .AsNoTracking()
            .Where(e => e.ServerReceivedUtc >= startDate && e.ServerReceivedUtc <= endDate)
            .GroupBy(e => e.Mood)
            .Select(g => new { Mood = g.Key, Count = g.Count() })
            .ToDictionaryAsync(x => x.Mood, x => x.Count, cancellationToken);
    }
}
