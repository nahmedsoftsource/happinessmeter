using FluentValidation;
using MoodTracking.Shared.DTOs;

namespace MoodTracking.Api.Validators;

/// <summary>
/// FluentValidation validator for CreateMoodRequest.
/// </summary>
public class CreateMoodRequestValidator : AbstractValidator<CreateMoodRequest>
{
    public CreateMoodRequestValidator()
    {
        RuleFor(x => x.Mood)
            .IsInEnum()
            .WithMessage("Mood must be one of: Happy, Unhappy, Sad.");

        RuleFor(x => x.WindowsUsername)
            .NotEmpty()
            .WithMessage("WindowsUsername is required.")
            .MaximumLength(256)
            .WithMessage("WindowsUsername cannot exceed 256 characters.")
            .Matches(@"^[a-zA-Z0-9._\-]+$")
            .WithMessage("WindowsUsername contains invalid characters.");

        RuleFor(x => x.Domain)
            .MaximumLength(256)
            .WithMessage("Domain cannot exceed 256 characters.")
            .Matches(@"^[a-zA-Z0-9._\-]*$")
            .When(x => !string.IsNullOrEmpty(x.Domain))
            .WithMessage("Domain contains invalid characters.");

        RuleFor(x => x.MachineName)
            .NotEmpty()
            .WithMessage("MachineName is required.")
            .MaximumLength(256)
            .WithMessage("MachineName cannot exceed 256 characters.")
            .Matches(@"^[a-zA-Z0-9._\-]+$")
            .WithMessage("MachineName contains invalid characters.");

        RuleFor(x => x.UserSid)
            .MaximumLength(256)
            .WithMessage("UserSid cannot exceed 256 characters.")
            .Matches(@"^S-1-\d+(-\d+)*$")
            .When(x => !string.IsNullOrEmpty(x.UserSid))
            .WithMessage("UserSid is not a valid Windows SID format.");

        RuleFor(x => x.ClientTimestampUtc)
            .NotEmpty()
            .WithMessage("ClientTimestampUtc is required.")
            .Must(BeReasonableTimestamp)
            .WithMessage("ClientTimestampUtc must be within the last 24 hours and not in the future.");

        RuleFor(x => x.AppVersion)
            .MaximumLength(50)
            .WithMessage("AppVersion cannot exceed 50 characters.")
            .Matches(@"^\d+\.\d+\.\d+(\.\d+)?$")
            .When(x => !string.IsNullOrEmpty(x.AppVersion))
            .WithMessage("AppVersion must be in format X.X.X or X.X.X.X.");

        RuleFor(x => x.OSVersion)
            .MaximumLength(256)
            .WithMessage("OSVersion cannot exceed 256 characters.");
    }

    private static bool BeReasonableTimestamp(DateTime timestamp)
    {
        var now = DateTime.UtcNow;
        var oneDayAgo = now.AddDays(-1);
        var oneHourFromNow = now.AddHours(1); // Allow small clock drift

        return timestamp >= oneDayAgo && timestamp <= oneHourFromNow;
    }
}
