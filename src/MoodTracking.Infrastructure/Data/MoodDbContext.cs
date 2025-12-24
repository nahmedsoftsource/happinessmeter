using Microsoft.EntityFrameworkCore;
using MoodTracking.Infrastructure.Entities;

namespace MoodTracking.Infrastructure.Data;

/// <summary>
/// Entity Framework Core DbContext for MoodTracking database.
/// </summary>
public class MoodDbContext : DbContext
{
    public MoodDbContext(DbContextOptions<MoodDbContext> options)
        : base(options)
    {
    }

    /// <summary>
    /// Mood events table.
    /// </summary>
    public DbSet<MoodEvent> MoodEvents => Set<MoodEvent>();

    protected override void OnModelCreating(ModelBuilder modelBuilder)
    {
        base.OnModelCreating(modelBuilder);

        // Apply all configurations from the current assembly
        modelBuilder.ApplyConfigurationsFromAssembly(typeof(MoodDbContext).Assembly);

        // Set default schema
        modelBuilder.HasDefaultSchema("mood");
    }

    /// <summary>
    /// Override SaveChanges to set audit fields.
    /// </summary>
    public override int SaveChanges()
    {
        SetAuditFields();
        return base.SaveChanges();
    }

    /// <summary>
    /// Override SaveChangesAsync to set audit fields.
    /// </summary>
    public override Task<int> SaveChangesAsync(CancellationToken cancellationToken = default)
    {
        SetAuditFields();
        return base.SaveChangesAsync(cancellationToken);
    }

    private void SetAuditFields()
    {
        var entries = ChangeTracker.Entries<MoodEvent>()
            .Where(e => e.State == EntityState.Added);

        foreach (var entry in entries)
        {
            if (entry.Entity.Id == Guid.Empty)
            {
                entry.Entity.Id = Guid.NewGuid();
            }

            entry.Entity.ServerReceivedUtc = DateTime.UtcNow;
            entry.Entity.CreatedAt = DateTime.UtcNow;
        }
    }
}
