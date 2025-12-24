namespace MoodTracking.Shared.Constants;

/// <summary>
/// API-related constants shared between client and server.
/// </summary>
public static class ApiConstants
{
    /// <summary>
    /// API route prefixes.
    /// </summary>
    public static class Routes
    {
        public const string MoodsBase = "api/moods";
        public const string Health = "health";
        public const string HealthReady = "health/ready";
    }

    /// <summary>
    /// HTTP header names.
    /// </summary>
    public static class Headers
    {
        public const string ApiVersion = "X-Api-Version";
        public const string CorrelationId = "X-Correlation-Id";
        public const string ClientVersion = "X-Client-Version";
        public const string RateLimitRemaining = "X-RateLimit-Remaining";
        public const string RateLimitReset = "X-RateLimit-Reset";
    }

    /// <summary>
    /// Validation limits.
    /// </summary>
    public static class Limits
    {
        public const int MaxUsernameLength = 256;
        public const int MaxDomainLength = 256;
        public const int MaxMachineNameLength = 256;
        public const int MaxAppVersionLength = 50;
        public const int MaxOSVersionLength = 256;
        public const int MaxPageSize = 100;
        public const int DefaultPageSize = 50;
    }

    /// <summary>
    /// Valid mood string values.
    /// </summary>
    public static class MoodValues
    {
        public const string Happy = "Happy";
        public const string Unhappy = "Unhappy";
        public const string Sad = "Sad";

        public static readonly string[] AllMoods = { Happy, Unhappy, Sad };

        public static bool IsValid(string mood)
            => AllMoods.Contains(mood, StringComparer.OrdinalIgnoreCase);
    }

    /// <summary>
    /// API versioning.
    /// </summary>
    public static class Versions
    {
        public const string V1 = "1.0";
        public const string Current = V1;
    }
}
