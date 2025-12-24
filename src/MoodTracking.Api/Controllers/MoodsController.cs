using FluentValidation;
using Microsoft.AspNetCore.Authorization;
using Microsoft.AspNetCore.Mvc;
using Microsoft.AspNetCore.RateLimiting;
using MoodTracking.Infrastructure.Entities;
using MoodTracking.Infrastructure.Repositories;
using MoodTracking.Shared.DTOs;

namespace MoodTracking.Api.Controllers;

/// <summary>
/// Controller for mood tracking operations.
/// </summary>
[ApiController]
[Route("api/[controller]")]
[Produces("application/json")]
public class MoodsController : ControllerBase
{
    private readonly IMoodRepository _repository;
    private readonly IValidator<CreateMoodRequest> _validator;
    private readonly ILogger<MoodsController> _logger;

    public MoodsController(
        IMoodRepository repository,
        IValidator<CreateMoodRequest> validator,
        ILogger<MoodsController> logger)
    {
        _repository = repository;
        _validator = validator;
        _logger = logger;
    }

    /// <summary>
    /// Submit a new mood event.
    /// </summary>
    /// <param name="request">The mood event details.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>The created mood event.</returns>
    /// <response code="201">Mood event created successfully.</response>
    /// <response code="400">Validation error in request.</response>
    /// <response code="401">User not authenticated.</response>
    /// <response code="429">Rate limit exceeded.</response>
    [HttpPost]
    [Authorize(Policy = "CanSubmitMood")]
    [EnableRateLimiting("MoodSubmission")]
    [ProducesResponseType(typeof(CreateMoodResponse), StatusCodes.Status201Created)]
    [ProducesResponseType(typeof(ValidationProblemDetails), StatusCodes.Status400BadRequest)]
    [ProducesResponseType(StatusCodes.Status401Unauthorized)]
    [ProducesResponseType(StatusCodes.Status429TooManyRequests)]
    public async Task<ActionResult<CreateMoodResponse>> CreateMood(
        [FromBody] CreateMoodRequest request,
        CancellationToken cancellationToken)
    {
        // Validate request
        var validationResult = await _validator.ValidateAsync(request, cancellationToken);
        if (!validationResult.IsValid)
        {
            foreach (var error in validationResult.Errors)
            {
                ModelState.AddModelError(error.PropertyName, error.ErrorMessage);
            }
            return ValidationProblem(ModelState);
        }

        // Check for duplicate submission (business rule: 1 mood per hour)
        var hasDuplicate = await _repository.HasRecentSubmissionAsync(
            request.WindowsUsername,
            request.Domain,
            windowMinutes: 60,
            cancellationToken);

        if (hasDuplicate)
        {
            _logger.LogWarning(
                "Duplicate mood submission attempt by {Username}@{Domain}",
                request.WindowsUsername,
                request.Domain);

            return Problem(
                title: "Duplicate Submission",
                detail: "You have already submitted a mood within the last hour.",
                statusCode: StatusCodes.Status429TooManyRequests);
        }

        // Get client IP address
        var clientIp = HttpContext.Connection.RemoteIpAddress?.ToString();

        // Create entity
        var moodEvent = new MoodEvent
        {
            WindowsUsername = request.WindowsUsername,
            Domain = request.Domain,
            MachineName = request.MachineName,
            UserSid = request.UserSid,
            Mood = request.Mood.ToString(),
            ClientTimestampUtc = request.ClientTimestampUtc,
            AppVersion = request.AppVersion,
            OSVersion = request.OSVersion,
            IPAddress = clientIp,
            Source = "DesktopApp"
        };

        // Save to database
        var created = await _repository.AddAsync(moodEvent, cancellationToken);

        _logger.LogInformation(
            "Mood {Mood} recorded for user {Username}@{Domain} from {MachineName}",
            moodEvent.Mood,
            moodEvent.WindowsUsername,
            moodEvent.Domain,
            moodEvent.MachineName);

        // Return response
        var response = new CreateMoodResponse
        {
            Id = created.Id,
            Mood = request.Mood,
            ServerReceivedUtc = created.ServerReceivedUtc,
            Message = GetConfirmationMessage(request.Mood)
        };

        return CreatedAtAction(
            nameof(GetMood),
            new { id = created.Id },
            response);
    }

    /// <summary>
    /// Get a specific mood event by ID.
    /// </summary>
    /// <param name="id">The mood event ID.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>The mood event.</returns>
    [HttpGet("{id:guid}")]
    [Authorize(Policy = "CanQueryMoods")]
    [EnableRateLimiting("PerUser")]
    [ProducesResponseType(typeof(MoodEventResponse), StatusCodes.Status200OK)]
    [ProducesResponseType(StatusCodes.Status404NotFound)]
    public async Task<ActionResult<MoodEventResponse>> GetMood(
        Guid id,
        CancellationToken cancellationToken)
    {
        var moodEvent = await _repository.GetByIdAsync(id, cancellationToken);

        if (moodEvent == null)
        {
            return NotFound();
        }

        return Ok(MapToResponse(moodEvent));
    }

    /// <summary>
    /// Query mood events with filtering and pagination.
    /// Requires admin privileges.
    /// </summary>
    /// <param name="parameters">Query parameters.</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>Paginated list of mood events.</returns>
    [HttpGet]
    [Authorize(Policy = "CanQueryMoods")]
    [EnableRateLimiting("PerUser")]
    [ProducesResponseType(typeof(PagedResult<MoodEventResponse>), StatusCodes.Status200OK)]
    public async Task<ActionResult<PagedResult<MoodEventResponse>>> GetMoods(
        [FromQuery] MoodQueryParameters parameters,
        CancellationToken cancellationToken)
    {
        var (items, totalCount) = await _repository.GetPagedAsync(parameters, cancellationToken);

        var response = new PagedResult<MoodEventResponse>
        {
            Items = items.Select(MapToResponse).ToList(),
            Page = parameters.Page,
            PageSize = parameters.PageSize,
            TotalCount = totalCount
        };

        return Ok(response);
    }

    /// <summary>
    /// Get mood statistics for a date range.
    /// Requires admin privileges.
    /// </summary>
    /// <param name="startDate">Start date (defaults to 30 days ago).</param>
    /// <param name="endDate">End date (defaults to today).</param>
    /// <param name="cancellationToken">Cancellation token.</param>
    /// <returns>Mood counts by type.</returns>
    [HttpGet("stats")]
    [Authorize(Policy = "CanQueryMoods")]
    [EnableRateLimiting("PerUser")]
    [ProducesResponseType(typeof(Dictionary<string, int>), StatusCodes.Status200OK)]
    public async Task<ActionResult<Dictionary<string, int>>> GetStats(
        [FromQuery] DateTime? startDate,
        [FromQuery] DateTime? endDate,
        CancellationToken cancellationToken)
    {
        var start = startDate ?? DateTime.UtcNow.AddDays(-30);
        var end = endDate ?? DateTime.UtcNow;

        var stats = await _repository.GetMoodCountsAsync(start, end, cancellationToken);

        return Ok(stats);
    }

    private static MoodEventResponse MapToResponse(MoodEvent entity)
    {
        return new MoodEventResponse
        {
            Id = entity.Id,
            WindowsUsername = entity.WindowsUsername,
            Domain = entity.Domain,
            MachineName = entity.MachineName,
            UserSid = entity.UserSid,
            Mood = Enum.Parse<MoodType>(entity.Mood, ignoreCase: true),
            ClientTimestampUtc = entity.ClientTimestampUtc,
            ServerReceivedUtc = entity.ServerReceivedUtc,
            AppVersion = entity.AppVersion,
            OSVersion = entity.OSVersion,
            IPAddress = entity.IPAddress
        };
    }

    private static string GetConfirmationMessage(MoodType mood)
    {
        return mood switch
        {
            MoodType.Happy => "Great to hear you're feeling happy! Have a wonderful day!",
            MoodType.Unhappy => "Thank you for sharing. We hope things improve for you.",
            MoodType.Sad => "We're sorry to hear that. Your feedback is appreciated.",
            _ => "Mood recorded successfully."
        };
    }
}
