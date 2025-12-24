using Microsoft.EntityFrameworkCore;
using Microsoft.EntityFrameworkCore.Metadata.Builders;
using MoodTracking.Infrastructure.Entities;

namespace MoodTracking.Infrastructure.Data.Configurations;

/// <summary>
/// Entity Framework Core configuration for MoodEvent entity.
/// </summary>
public class MoodEventConfiguration : IEntityTypeConfiguration<MoodEvent>
{
    public void Configure(EntityTypeBuilder<MoodEvent> builder)
    {
        // Table name and schema
        builder.ToTable("MoodEvents", "mood");

        // Primary key
        builder.HasKey(e => e.Id);

        // Properties
        builder.Property(e => e.Id)
            .HasDefaultValueSql("NEWSEQUENTIALID()");

        builder.Property(e => e.WindowsUsername)
            .IsRequired()
            .HasMaxLength(256);

        builder.Property(e => e.Domain)
            .HasMaxLength(256);

        builder.Property(e => e.UserSid)
            .HasMaxLength(256);

        builder.Property(e => e.MachineName)
            .IsRequired()
            .HasMaxLength(256);

        builder.Property(e => e.Mood)
            .IsRequired()
            .HasMaxLength(50);

        builder.Property(e => e.ClientTimestampUtc)
            .IsRequired();

        builder.Property(e => e.ServerReceivedUtc)
            .IsRequired()
            .HasDefaultValueSql("SYSUTCDATETIME()");

        builder.Property(e => e.AppVersion)
            .HasMaxLength(50);

        builder.Property(e => e.OSVersion)
            .HasMaxLength(256);

        builder.Property(e => e.IPAddress)
            .HasMaxLength(45);

        builder.Property(e => e.Source)
            .HasMaxLength(50);

        builder.Property(e => e.RowVersion)
            .IsRowVersion();

        builder.Property(e => e.CreatedAt)
            .IsRequired()
            .HasDefaultValueSql("SYSUTCDATETIME()");

        // Indexes (matching SQL scripts)
        builder.HasIndex(e => new { e.WindowsUsername, e.ServerReceivedUtc })
            .HasDatabaseName("IX_MoodEvents_WindowsUsername_ServerReceivedUtc");

        builder.HasIndex(e => e.ServerReceivedUtc)
            .HasDatabaseName("IX_MoodEvents_ServerReceivedUtc")
            .IsDescending();

        builder.HasIndex(e => e.Mood)
            .HasDatabaseName("IX_MoodEvents_Mood");

        builder.HasIndex(e => e.MachineName)
            .HasDatabaseName("IX_MoodEvents_MachineName");

        // Check constraint for valid mood values
        builder.HasCheckConstraint("CK_MoodEvents_Mood", "[Mood] IN ('Happy', 'Unhappy', 'Sad')");
    }
}
