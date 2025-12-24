namespace MoodTracking.Shared.DTOs;

/// <summary>
/// Represents the possible mood values.
/// </summary>
public enum MoodType
{
    /// <summary>
    /// User is feeling happy.
    /// </summary>
    Happy = 1,

    /// <summary>
    /// User is feeling unhappy (neutral/dissatisfied).
    /// </summary>
    Unhappy = 2,

    /// <summary>
    /// User is feeling sad.
    /// </summary>
    Sad = 3
}
