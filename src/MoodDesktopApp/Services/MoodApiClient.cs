using System.Net.Http.Json;
using System.Text.Json;
using Microsoft.Extensions.Logging;
using MoodTracking.Shared.DTOs;

namespace MoodDesktopApp.Services;

/// <summary>
/// HTTP client for communicating with the MoodTracking API.
/// Uses Windows integrated authentication.
/// </summary>
public class MoodApiClient : IMoodApiClient
{
    private readonly HttpClient _httpClient;
    private readonly ILogger<MoodApiClient> _logger;
    private readonly JsonSerializerOptions _jsonOptions;

    public MoodApiClient(HttpClient httpClient, ILogger<MoodApiClient> logger)
    {
        _httpClient = httpClient;
        _logger = logger;

        _jsonOptions = new JsonSerializerOptions
        {
            PropertyNamingPolicy = JsonNamingPolicy.CamelCase,
            PropertyNameCaseInsensitive = true
        };
    }

    /// <summary>
    /// Submits a mood event to the API.
    /// </summary>
    public async Task<MoodSubmissionResult> SubmitMoodAsync(
        CreateMoodRequest request,
        CancellationToken cancellationToken = default)
    {
        try
        {
            _logger.LogDebug(
                "Submitting mood {Mood} for user {Username}",
                request.Mood,
                request.WindowsUsername);

            var response = await _httpClient.PostAsJsonAsync(
                "api/moods",
                request,
                _jsonOptions,
                cancellationToken);

            if (response.IsSuccessStatusCode)
            {
                var result = await response.Content.ReadFromJsonAsync<CreateMoodResponse>(
                    _jsonOptions,
                    cancellationToken);

                _logger.LogInformation(
                    "Mood submitted successfully with ID {Id}",
                    result?.Id);

                return new MoodSubmissionResult
                {
                    Success = true,
                    Id = result?.Id,
                    Message = result?.Message
                };
            }

            // Handle specific error cases
            var errorContent = await response.Content.ReadAsStringAsync(cancellationToken);

            if (response.StatusCode == System.Net.HttpStatusCode.TooManyRequests)
            {
                _logger.LogWarning("Rate limit exceeded for mood submission");
                return new MoodSubmissionResult
                {
                    Success = false,
                    ErrorMessage = "You've already submitted your mood recently. Please try again later."
                };
            }

            if (response.StatusCode == System.Net.HttpStatusCode.Unauthorized)
            {
                _logger.LogWarning("Authentication failed for mood submission");
                return new MoodSubmissionResult
                {
                    Success = false,
                    ErrorMessage = "Authentication failed. Please ensure you're connected to the corporate network."
                };
            }

            _logger.LogWarning(
                "API returned error {StatusCode}: {Content}",
                response.StatusCode,
                errorContent);

            return new MoodSubmissionResult
            {
                Success = false,
                ErrorMessage = $"Server error: {response.StatusCode}"
            };
        }
        catch (HttpRequestException ex)
        {
            _logger.LogWarning(ex, "Network error during mood submission");
            return new MoodSubmissionResult
            {
                Success = false,
                IsOffline = true,
                ErrorMessage = "Unable to reach server. Your mood will be saved and synced later."
            };
        }
        catch (TaskCanceledException ex) when (ex.InnerException is TimeoutException)
        {
            _logger.LogWarning(ex, "Timeout during mood submission");
            return new MoodSubmissionResult
            {
                Success = false,
                IsOffline = true,
                ErrorMessage = "Request timed out. Your mood will be saved and synced later."
            };
        }
        catch (Exception ex)
        {
            _logger.LogError(ex, "Unexpected error during mood submission");
            return new MoodSubmissionResult
            {
                Success = false,
                ErrorMessage = "An unexpected error occurred."
            };
        }
    }

    /// <summary>
    /// Checks if the API is reachable.
    /// </summary>
    public async Task<bool> IsApiAvailableAsync(CancellationToken cancellationToken = default)
    {
        try
        {
            var response = await _httpClient.GetAsync("health", cancellationToken);
            return response.IsSuccessStatusCode;
        }
        catch (Exception ex)
        {
            _logger.LogDebug(ex, "API health check failed");
            return false;
        }
    }
}
