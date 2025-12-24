using FluentAssertions;
using FluentValidation;
using FluentValidation.Results;
using Microsoft.AspNetCore.Http;
using Microsoft.AspNetCore.Mvc;
using Microsoft.Extensions.Logging;
using Moq;
using MoodTracking.Api.Controllers;
using MoodTracking.Infrastructure.Entities;
using MoodTracking.Infrastructure.Repositories;
using MoodTracking.Shared.DTOs;
using Xunit;

namespace MoodTracking.Api.Tests.Controllers;

public class MoodsControllerTests
{
    private readonly Mock<IMoodRepository> _repositoryMock;
    private readonly Mock<IValidator<CreateMoodRequest>> _validatorMock;
    private readonly Mock<ILogger<MoodsController>> _loggerMock;
    private readonly MoodsController _controller;

    public MoodsControllerTests()
    {
        _repositoryMock = new Mock<IMoodRepository>();
        _validatorMock = new Mock<IValidator<CreateMoodRequest>>();
        _loggerMock = new Mock<ILogger<MoodsController>>();

        _controller = new MoodsController(
            _repositoryMock.Object,
            _validatorMock.Object,
            _loggerMock.Object);

        // Setup HttpContext for IP address
        var httpContext = new DefaultHttpContext();
        httpContext.Connection.RemoteIpAddress = System.Net.IPAddress.Parse("192.168.1.100");
        _controller.ControllerContext = new ControllerContext
        {
            HttpContext = httpContext
        };
    }

    [Fact]
    public async Task CreateMood_WithValidRequest_ReturnsCreated()
    {
        // Arrange
        var request = new CreateMoodRequest
        {
            Mood = MoodType.Happy,
            WindowsUsername = "jsmith",
            Domain = "CONTOSO",
            MachineName = "WS-001",
            ClientTimestampUtc = DateTime.UtcNow
        };

        _validatorMock.Setup(v => v.ValidateAsync(request, It.IsAny<CancellationToken>()))
            .ReturnsAsync(new ValidationResult());

        _repositoryMock.Setup(r => r.HasRecentSubmissionAsync(
            request.WindowsUsername, request.Domain, 60, It.IsAny<CancellationToken>()))
            .ReturnsAsync(false);

        var createdEvent = new MoodEvent
        {
            Id = Guid.NewGuid(),
            WindowsUsername = request.WindowsUsername,
            Domain = request.Domain,
            MachineName = request.MachineName,
            Mood = "Happy",
            ClientTimestampUtc = request.ClientTimestampUtc,
            ServerReceivedUtc = DateTime.UtcNow
        };

        _repositoryMock.Setup(r => r.AddAsync(It.IsAny<MoodEvent>(), It.IsAny<CancellationToken>()))
            .ReturnsAsync(createdEvent);

        // Act
        var result = await _controller.CreateMood(request, CancellationToken.None);

        // Assert
        var createdResult = result.Result.Should().BeOfType<CreatedAtActionResult>().Subject;
        var response = createdResult.Value.Should().BeOfType<CreateMoodResponse>().Subject;
        response.Id.Should().Be(createdEvent.Id);
        response.Mood.Should().Be(MoodType.Happy);
    }

    [Fact]
    public async Task CreateMood_WithInvalidRequest_ReturnsBadRequest()
    {
        // Arrange
        var request = new CreateMoodRequest
        {
            WindowsUsername = "",  // Invalid
            MachineName = "WS-001",
            ClientTimestampUtc = DateTime.UtcNow
        };

        var validationResult = new ValidationResult(new[]
        {
            new ValidationFailure("WindowsUsername", "WindowsUsername is required.")
        });

        _validatorMock.Setup(v => v.ValidateAsync(request, It.IsAny<CancellationToken>()))
            .ReturnsAsync(validationResult);

        // Act
        var result = await _controller.CreateMood(request, CancellationToken.None);

        // Assert
        result.Result.Should().BeOfType<ObjectResult>()
            .Which.StatusCode.Should().Be(400);
    }

    [Fact]
    public async Task CreateMood_WithRecentSubmission_ReturnsTooManyRequests()
    {
        // Arrange
        var request = new CreateMoodRequest
        {
            Mood = MoodType.Happy,
            WindowsUsername = "jsmith",
            Domain = "CONTOSO",
            MachineName = "WS-001",
            ClientTimestampUtc = DateTime.UtcNow
        };

        _validatorMock.Setup(v => v.ValidateAsync(request, It.IsAny<CancellationToken>()))
            .ReturnsAsync(new ValidationResult());

        _repositoryMock.Setup(r => r.HasRecentSubmissionAsync(
            request.WindowsUsername, request.Domain, 60, It.IsAny<CancellationToken>()))
            .ReturnsAsync(true);  // Already submitted

        // Act
        var result = await _controller.CreateMood(request, CancellationToken.None);

        // Assert
        result.Result.Should().BeOfType<ObjectResult>()
            .Which.StatusCode.Should().Be(429);
    }

    [Fact]
    public async Task GetMood_WithExistingId_ReturnsOk()
    {
        // Arrange
        var id = Guid.NewGuid();
        var moodEvent = new MoodEvent
        {
            Id = id,
            WindowsUsername = "jsmith",
            Domain = "CONTOSO",
            MachineName = "WS-001",
            Mood = "Happy",
            ClientTimestampUtc = DateTime.UtcNow.AddMinutes(-5),
            ServerReceivedUtc = DateTime.UtcNow.AddMinutes(-5)
        };

        _repositoryMock.Setup(r => r.GetByIdAsync(id, It.IsAny<CancellationToken>()))
            .ReturnsAsync(moodEvent);

        // Act
        var result = await _controller.GetMood(id, CancellationToken.None);

        // Assert
        var okResult = result.Result.Should().BeOfType<OkObjectResult>().Subject;
        var response = okResult.Value.Should().BeOfType<MoodEventResponse>().Subject;
        response.Id.Should().Be(id);
        response.Mood.Should().Be(MoodType.Happy);
    }

    [Fact]
    public async Task GetMood_WithNonExistingId_ReturnsNotFound()
    {
        // Arrange
        var id = Guid.NewGuid();

        _repositoryMock.Setup(r => r.GetByIdAsync(id, It.IsAny<CancellationToken>()))
            .ReturnsAsync((MoodEvent?)null);

        // Act
        var result = await _controller.GetMood(id, CancellationToken.None);

        // Assert
        result.Result.Should().BeOfType<NotFoundResult>();
    }

    [Fact]
    public async Task GetMoods_WithParameters_ReturnsPagedResult()
    {
        // Arrange
        var parameters = new MoodQueryParameters
        {
            Page = 1,
            PageSize = 10,
            Mood = MoodType.Happy
        };

        var events = new List<MoodEvent>
        {
            new MoodEvent
            {
                Id = Guid.NewGuid(),
                WindowsUsername = "jsmith",
                Mood = "Happy",
                MachineName = "WS-001",
                ClientTimestampUtc = DateTime.UtcNow,
                ServerReceivedUtc = DateTime.UtcNow
            }
        };

        _repositoryMock.Setup(r => r.GetPagedAsync(parameters, It.IsAny<CancellationToken>()))
            .ReturnsAsync((events.AsReadOnly(), 1));

        // Act
        var result = await _controller.GetMoods(parameters, CancellationToken.None);

        // Assert
        var okResult = result.Result.Should().BeOfType<OkObjectResult>().Subject;
        var response = okResult.Value.Should().BeOfType<PagedResult<MoodEventResponse>>().Subject;
        response.Items.Should().HaveCount(1);
        response.TotalCount.Should().Be(1);
        response.Page.Should().Be(1);
    }
}
