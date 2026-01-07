namespace MoodTracking.Shared.DTOs;

/// <summary>
/// Represents the possible mood values.
/// </summary>
public enum MoodType
{
    /// <summary>
    /// User is feeling very happy.
    /// </summary>
    SoHappy = 1,

    /// <summary>
    /// User is very busy working.
    /// </summary>
    VeryBusy = 2,

    /// <summary>
    /// User has positive energy.
    /// </summary>
    PositiveEnergy = 3,

    /// <summary>
    /// User is not in the mood.
    /// </summary>
    NotInMood = 4,

    /// <summary>
    /// User is feeling hungry.
    /// </summary>
    Hungry = 5,

    /// <summary>
    /// User is feeling grumpy.
    /// </summary>
    Grumpy = 6,

    /// <summary>
    /// User is feeling sleepy.
    /// </summary>
    Sleepy = 7
}
