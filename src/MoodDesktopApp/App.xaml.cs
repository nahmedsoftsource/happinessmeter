using System.IO;
using System.Windows;
using Microsoft.Extensions.Configuration;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.Extensions.Hosting;
using Microsoft.Extensions.Logging;
using MoodDesktopApp.Services;
using MoodDesktopApp.ViewModels;
using MoodDesktopApp.Workers;
using Polly;
using Polly.Extensions.Http;
using Serilog;

namespace MoodDesktopApp;

/// <summary>
/// Main application class for HappinessMeter desktop app.
/// </summary>
public partial class App : Application
{
    private IHost? _host;

    public static IServiceProvider Services { get; private set; } = null!;

    private void Application_Startup(object sender, StartupEventArgs e)
    {
        // Check for single instance
        if (!EnsureSingleInstance())
        {
            Shutdown();
            return;
        }

        // Build configuration
        var configuration = new ConfigurationBuilder()
            .SetBasePath(AppContext.BaseDirectory)
            .AddJsonFile("appsettings.json", optional: false, reloadOnChange: true)
            .AddJsonFile($"appsettings.{Environment.GetEnvironmentVariable("DOTNET_ENVIRONMENT") ?? "Production"}.json", optional: true)
            .Build();

        // Configure Serilog
        var logPath = Path.Combine(
            Environment.GetFolderPath(Environment.SpecialFolder.LocalApplicationData),
            "HappinessMeter",
            "logs",
            "moodapp-.log");

        Log.Logger = new LoggerConfiguration()
            .ReadFrom.Configuration(configuration)
            .Enrich.FromLogContext()
            .WriteTo.File(
                path: logPath,
                rollingInterval: RollingInterval.Day,
                retainedFileCountLimit: 7,
                outputTemplate: "{Timestamp:yyyy-MM-dd HH:mm:ss.fff} [{Level:u3}] {Message:lj}{NewLine}{Exception}")
            .CreateLogger();

        Log.Information("HappinessMeter application starting...");

        // Build host
        _host = Host.CreateDefaultBuilder()
            .UseSerilog()
            .ConfigureServices((context, services) =>
            {
                ConfigureServices(services, configuration);
            })
            .Build();

        Services = _host.Services;

        // Ensure auto-start is configured (registers in Windows Registry on first run)
        var autoStartService = Services.GetRequiredService<IAutoStartService>();
        autoStartService.EnsureAutoStartConfigured();

        // Start background services
        _host.StartAsync();

        // Show main window
        var mainWindow = Services.GetRequiredService<MainWindow>();
        mainWindow.Show();

        Log.Information("HappinessMeter window displayed");
    }

    private void ConfigureServices(IServiceCollection services, IConfiguration configuration)
    {
        // Configuration
        services.AddSingleton(configuration);

        // System info service
        services.AddSingleton<ISystemInfoService, SystemInfoService>();

        // Auto-start service (registers app to run at Windows login)
        services.AddSingleton<IAutoStartService, AutoStartService>();

        // Submission tracker (checks office hours and 24-hour cooldown)
        services.AddSingleton<ISubmissionTracker, SubmissionTracker>();

        // Offline queue service (SQLite)
        services.AddSingleton<IOfflineQueueService, OfflineQueueService>();

        // HTTP client with retry policy
        services.AddHttpClient<IMoodApiClient, MoodApiClient>(client =>
        {
            var baseUrl = configuration["Api:BaseUrl"] ?? "https://localhost:5001";
            client.BaseAddress = new Uri(baseUrl);
            client.DefaultRequestHeaders.Add("Accept", "application/json");
            client.DefaultRequestHeaders.Add("X-Client-Version", GetAppVersion());
        })
        .ConfigurePrimaryHttpMessageHandler(() => new HttpClientHandler
        {
            // Use Windows credentials for integrated auth
            UseDefaultCredentials = true,
            PreAuthenticate = true
        })
        .AddPolicyHandler(GetRetryPolicy())
        .AddPolicyHandler(GetCircuitBreakerPolicy());

        // Background sync worker
        services.AddHostedService<SyncBackgroundService>();

        // ViewModels
        services.AddTransient<MainViewModel>();

        // Windows
        services.AddTransient<MainWindow>();

        // Logging
        services.AddLogging(builder =>
        {
            builder.AddSerilog(dispose: true);
        });
    }

    private static IAsyncPolicy<HttpResponseMessage> GetRetryPolicy()
    {
        return HttpPolicyExtensions
            .HandleTransientHttpError()
            .OrResult(msg => msg.StatusCode == System.Net.HttpStatusCode.TooManyRequests)
            .WaitAndRetryAsync(3, retryAttempt =>
                TimeSpan.FromSeconds(Math.Pow(2, retryAttempt)),
                onRetry: (outcome, timespan, retryAttempt, context) =>
                {
                    Log.Warning(
                        "Retry {RetryAttempt} after {Delay}s due to {Reason}",
                        retryAttempt,
                        timespan.TotalSeconds,
                        outcome.Exception?.Message ?? outcome.Result?.StatusCode.ToString());
                });
    }

    private static IAsyncPolicy<HttpResponseMessage> GetCircuitBreakerPolicy()
    {
        return HttpPolicyExtensions
            .HandleTransientHttpError()
            .CircuitBreakerAsync(
                handledEventsAllowedBeforeBreaking: 5,
                durationOfBreak: TimeSpan.FromMinutes(1),
                onBreak: (outcome, breakDelay) =>
                {
                    Log.Warning(
                        "Circuit breaker opened for {BreakDelay}s due to {Reason}",
                        breakDelay.TotalSeconds,
                        outcome.Exception?.Message ?? outcome.Result?.StatusCode.ToString());
                },
                onReset: () => Log.Information("Circuit breaker reset"),
                onHalfOpen: () => Log.Information("Circuit breaker half-open"));
    }

    private static string GetAppVersion()
    {
        var version = System.Reflection.Assembly.GetExecutingAssembly().GetName().Version;
        return version?.ToString() ?? "1.0.0.0";
    }

    private bool EnsureSingleInstance()
    {
        const string mutexName = "Global\\HappinessMeter_SingleInstance";
        bool createdNew;

        var mutex = new System.Threading.Mutex(true, mutexName, out createdNew);

        if (!createdNew)
        {
            // Another instance is already running - exit silently
            Log.Information("Another instance is already running");
            return false;
        }

        // Keep mutex alive for application lifetime
        GC.KeepAlive(mutex);
        return true;
    }

    private async void Application_Exit(object sender, ExitEventArgs e)
    {
        Log.Information("HappinessMeter application shutting down");

        if (_host != null)
        {
            await _host.StopAsync(TimeSpan.FromSeconds(5));
            _host.Dispose();
        }

        Log.CloseAndFlush();
    }
}
