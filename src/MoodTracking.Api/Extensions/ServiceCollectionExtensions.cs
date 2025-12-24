using Microsoft.EntityFrameworkCore;
using MoodTracking.Infrastructure.Data;
using MoodTracking.Infrastructure.Repositories;

namespace MoodTracking.Api.Extensions;

/// <summary>
/// Extension methods for IServiceCollection to configure application services.
/// </summary>
public static class ServiceCollectionExtensions
{
    /// <summary>
    /// Adds database services to the service collection.
    /// </summary>
    public static IServiceCollection AddDatabaseServices(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        var connectionString = configuration.GetConnectionString("MoodTrackingDb")
            ?? throw new InvalidOperationException("Connection string 'MoodTrackingDb' not found.");

        services.AddDbContext<MoodDbContext>(options =>
        {
            options.UseSqlServer(connectionString, sqlOptions =>
            {
                sqlOptions.EnableRetryOnFailure(
                    maxRetryCount: 3,
                    maxRetryDelay: TimeSpan.FromSeconds(10),
                    errorNumbersToAdd: null);
                sqlOptions.CommandTimeout(30);
                sqlOptions.MigrationsHistoryTable("__EFMigrationsHistory", "mood");
            });
        });

        services.AddScoped<IMoodRepository, MoodRepository>();

        return services;
    }

    /// <summary>
    /// Adds health check services.
    /// </summary>
    public static IServiceCollection AddHealthCheckServices(
        this IServiceCollection services,
        IConfiguration configuration)
    {
        var connectionString = configuration.GetConnectionString("MoodTrackingDb")
            ?? throw new InvalidOperationException("Connection string 'MoodTrackingDb' not found.");

        services.AddHealthChecks()
            .AddSqlServer(
                connectionString: connectionString,
                name: "sqlserver",
                tags: new[] { "db", "sql", "sqlserver" });

        return services;
    }
}
