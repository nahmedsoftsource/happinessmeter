using System.Threading.RateLimiting;
using FluentValidation;
using Microsoft.AspNetCore.Authentication.Negotiate;
using Microsoft.AspNetCore.RateLimiting;
using Microsoft.EntityFrameworkCore;
using MoodTracking.Api.Extensions;
using MoodTracking.Api.Middleware;
using MoodTracking.Api.Validators;
using MoodTracking.Infrastructure.Data;
using MoodTracking.Infrastructure.Repositories;
using MoodTracking.Shared.DTOs;
using Serilog;

// Configure Serilog early
Log.Logger = new LoggerConfiguration()
    .WriteTo.Console()
    .CreateBootstrapLogger();

try
{
    Log.Information("Starting MoodTracking API");

    var builder = WebApplication.CreateBuilder(args);

    // Configure Serilog from appsettings
    builder.Host.UseSerilog((context, services, configuration) => configuration
        .ReadFrom.Configuration(context.Configuration)
        .ReadFrom.Services(services)
        .Enrich.FromLogContext()
        .Enrich.WithMachineName()
        .Enrich.WithEnvironmentName()
        .WriteTo.Console(
            outputTemplate: "[{Timestamp:HH:mm:ss} {Level:u3}] {Message:lj} {Properties:j}{NewLine}{Exception}")
        .WriteTo.File(
            path: "logs/moodtracking-.log",
            rollingInterval: RollingInterval.Day,
            retainedFileCountLimit: 30,
            outputTemplate: "{Timestamp:yyyy-MM-dd HH:mm:ss.fff zzz} [{Level:u3}] {Message:lj} {Properties:j}{NewLine}{Exception}")
        .WriteTo.EventLog(
            source: "MoodTracking.Api",
            logName: "Application",
            restrictedToMinimumLevel: Serilog.Events.LogEventLevel.Warning));

    // Add services to the container

    // Database Context
    builder.Services.AddDbContext<MoodDbContext>(options =>
    {
        var connectionString = builder.Configuration.GetConnectionString("MoodTrackingDb");
        options.UseSqlServer(connectionString, sqlOptions =>
        {
            sqlOptions.EnableRetryOnFailure(
                maxRetryCount: 3,
                maxRetryDelay: TimeSpan.FromSeconds(10),
                errorNumbersToAdd: null);
            sqlOptions.CommandTimeout(30);
        });
    });

    // Repositories
    builder.Services.AddScoped<IMoodRepository, MoodRepository>();

    // FluentValidation
    builder.Services.AddValidatorsFromAssemblyContaining<CreateMoodRequestValidator>();

    // Windows Authentication
    builder.Services.AddAuthentication(NegotiateDefaults.AuthenticationScheme)
        .AddNegotiate();

    builder.Services.AddAuthorization(options =>
    {
        // All authenticated users can submit moods
        options.AddPolicy("CanSubmitMood", policy =>
            policy.RequireAuthenticatedUser());

        // Only admins can query moods (adjust group name for your environment)
        options.AddPolicy("CanQueryMoods", policy =>
            policy.RequireRole("MoodTracking-Admins", "Domain Admins", "Administrators"));
    });

    // Rate Limiting
    builder.Services.AddRateLimiter(options =>
    {
        options.RejectionStatusCode = StatusCodes.Status429TooManyRequests;

        // Global rate limit per user
        options.AddPolicy("PerUser", context =>
            RateLimitPartition.GetSlidingWindowLimiter(
                partitionKey: context.User?.Identity?.Name ?? context.Connection.RemoteIpAddress?.ToString() ?? "anonymous",
                factory: _ => new SlidingWindowRateLimiterOptions
                {
                    PermitLimit = 10,
                    Window = TimeSpan.FromMinutes(1),
                    SegmentsPerWindow = 6,
                    QueueProcessingOrder = QueueProcessingOrder.OldestFirst,
                    QueueLimit = 2
                }));

        // Stricter limit for mood submissions (1 per hour per user)
        options.AddPolicy("MoodSubmission", context =>
            RateLimitPartition.GetFixedWindowLimiter(
                partitionKey: context.User?.Identity?.Name ?? context.Connection.RemoteIpAddress?.ToString() ?? "anonymous",
                factory: _ => new FixedWindowRateLimiterOptions
                {
                    PermitLimit = 5, // Allow some retries
                    Window = TimeSpan.FromHours(1),
                    QueueProcessingOrder = QueueProcessingOrder.OldestFirst,
                    QueueLimit = 0
                }));

        options.OnRejected = async (context, token) =>
        {
            context.HttpContext.Response.StatusCode = StatusCodes.Status429TooManyRequests;
            context.HttpContext.Response.ContentType = "application/problem+json";

            var retryAfter = context.Lease.TryGetMetadata(MetadataName.RetryAfter, out var retry)
                ? (int)retry.TotalSeconds
                : 60;

            context.HttpContext.Response.Headers.RetryAfter = retryAfter.ToString();

            await context.HttpContext.Response.WriteAsJsonAsync(new
            {
                type = "https://tools.ietf.org/html/rfc6585#section-4",
                title = "Too Many Requests",
                status = 429,
                detail = $"Rate limit exceeded. Try again in {retryAfter} seconds."
            }, token);
        };
    });

    // Controllers
    builder.Services.AddControllers()
        .AddJsonOptions(options =>
        {
            options.JsonSerializerOptions.PropertyNamingPolicy = System.Text.Json.JsonNamingPolicy.CamelCase;
            options.JsonSerializerOptions.Converters.Add(new System.Text.Json.Serialization.JsonStringEnumConverter());
        });

    // API Documentation
    builder.Services.AddEndpointsApiExplorer();
    builder.Services.AddSwaggerGen(options =>
    {
        options.SwaggerDoc("v1", new Microsoft.OpenApi.Models.OpenApiInfo
        {
            Title = "MoodTracking API",
            Version = "v1",
            Description = "API for tracking employee mood in HappinessMeter system",
            Contact = new Microsoft.OpenApi.Models.OpenApiContact
            {
                Name = "IT Support",
                Email = "it-support@company.com"
            }
        });

        // Include XML comments if available
        var xmlFile = $"{System.Reflection.Assembly.GetExecutingAssembly().GetName().Name}.xml";
        var xmlPath = Path.Combine(AppContext.BaseDirectory, xmlFile);
        if (File.Exists(xmlPath))
        {
            options.IncludeXmlComments(xmlPath);
        }
    });

    // Health Checks
    builder.Services.AddHealthChecks()
        .AddSqlServer(
            connectionString: builder.Configuration.GetConnectionString("MoodTrackingDb")!,
            name: "sqlserver",
            tags: new[] { "db", "sql", "sqlserver" });

    // CORS (if needed for admin web portal)
    builder.Services.AddCors(options =>
    {
        options.AddPolicy("AllowInternalApps", policy =>
        {
            policy
                .WithOrigins(builder.Configuration.GetSection("Cors:AllowedOrigins").Get<string[]>() ?? Array.Empty<string>())
                .AllowAnyMethod()
                .AllowAnyHeader()
                .AllowCredentials();
        });
    });

    // HttpContext accessor for IP address logging
    builder.Services.AddHttpContextAccessor();

    var app = builder.Build();

    // Configure the HTTP request pipeline

    // Exception handling middleware (first in pipeline)
    app.UseMiddleware<ExceptionHandlingMiddleware>();

    // Request logging
    app.UseSerilogRequestLogging(options =>
    {
        options.EnrichDiagnosticContext = (diagnosticContext, httpContext) =>
        {
            diagnosticContext.Set("UserName", httpContext.User?.Identity?.Name ?? "anonymous");
            diagnosticContext.Set("ClientIP", httpContext.Connection.RemoteIpAddress?.ToString());
        };
    });

    // Swagger (development only by default, or enable for internal use)
    if (app.Environment.IsDevelopment() || builder.Configuration.GetValue<bool>("EnableSwagger"))
    {
        app.UseSwagger();
        app.UseSwaggerUI(options =>
        {
            options.SwaggerEndpoint("/swagger/v1/swagger.json", "MoodTracking API v1");
            options.RoutePrefix = "swagger";
        });
    }

    // HTTPS redirection
    app.UseHttpsRedirection();

    // CORS
    app.UseCors("AllowInternalApps");

    // Rate limiting
    app.UseRateLimiter();

    // Authentication & Authorization
    app.UseAuthentication();
    app.UseAuthorization();

    // Health check endpoints
    app.MapHealthChecks("/health", new Microsoft.AspNetCore.Diagnostics.HealthChecks.HealthCheckOptions
    {
        Predicate = _ => false // Quick liveness check
    });

    app.MapHealthChecks("/health/ready", new Microsoft.AspNetCore.Diagnostics.HealthChecks.HealthCheckOptions
    {
        Predicate = check => check.Tags.Contains("db") // Check database connection
    });

    // Map controllers
    app.MapControllers();

    // Run the application
    app.Run();
}
catch (Exception ex)
{
    Log.Fatal(ex, "Application terminated unexpectedly");
}
finally
{
    Log.CloseAndFlush();
}
