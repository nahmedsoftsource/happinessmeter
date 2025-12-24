-- ============================================================
-- HappinessMeter Stored Procedures Script
-- Script: 05-StoredProcedures.sql
-- Description: Creates stored procedures for data operations
-- ============================================================

USE [MoodTracking]
GO

-- ============================================================
-- Procedure: Insert Mood Event
-- ============================================================
IF EXISTS (SELECT * FROM sys.procedures WHERE name = N'usp_InsertMoodEvent')
    DROP PROCEDURE [mood].[usp_InsertMoodEvent]
GO

CREATE PROCEDURE [mood].[usp_InsertMoodEvent]
    @Id                 UNIQUEIDENTIFIER = NULL OUTPUT,
    @WindowsUsername    NVARCHAR(256),
    @Domain             NVARCHAR(256) = NULL,
    @MachineName        NVARCHAR(256),
    @UserSid            NVARCHAR(256) = NULL,
    @Mood               NVARCHAR(50),
    @ClientTimestampUtc DATETIME2(7),
    @AppVersion         NVARCHAR(50) = NULL,
    @OSVersion          NVARCHAR(256) = NULL,
    @IPAddress          NVARCHAR(45) = NULL,
    @Source             NVARCHAR(50) = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- Generate ID if not provided
    IF @Id IS NULL
        SET @Id = NEWID()

    -- Validate mood
    IF @Mood NOT IN ('Happy', 'Unhappy', 'Sad')
    BEGIN
        RAISERROR('Invalid mood value. Must be Happy, Unhappy, or Sad.', 16, 1)
        RETURN -1
    END

    -- Insert the record
    INSERT INTO [mood].[MoodEvents]
    (
        [Id],
        [WindowsUsername],
        [Domain],
        [MachineName],
        [UserSid],
        [Mood],
        [ClientTimestampUtc],
        [ServerReceivedUtc],
        [AppVersion],
        [OSVersion],
        [IPAddress],
        [Source]
    )
    VALUES
    (
        @Id,
        @WindowsUsername,
        @Domain,
        @MachineName,
        @UserSid,
        @Mood,
        @ClientTimestampUtc,
        SYSUTCDATETIME(),
        @AppVersion,
        @OSVersion,
        @IPAddress,
        @Source
    )

    RETURN 0
END
GO

PRINT 'Procedure [mood].[usp_InsertMoodEvent] created.'
GO

-- ============================================================
-- Procedure: Get Mood Events (Paginated)
-- ============================================================
IF EXISTS (SELECT * FROM sys.procedures WHERE name = N'usp_GetMoodEvents')
    DROP PROCEDURE [mood].[usp_GetMoodEvents]
GO

CREATE PROCEDURE [mood].[usp_GetMoodEvents]
    @StartDate          DATETIME2(7) = NULL,
    @EndDate            DATETIME2(7) = NULL,
    @Username           NVARCHAR(256) = NULL,
    @Domain             NVARCHAR(256) = NULL,
    @MachineName        NVARCHAR(256) = NULL,
    @Mood               NVARCHAR(50) = NULL,
    @Page               INT = 1,
    @PageSize           INT = 50,
    @TotalCount         INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    -- Validate pagination
    IF @Page < 1 SET @Page = 1
    IF @PageSize < 1 SET @PageSize = 50
    IF @PageSize > 1000 SET @PageSize = 1000

    -- Get total count
    SELECT @TotalCount = COUNT(*)
    FROM [mood].[MoodEvents]
    WHERE (@StartDate IS NULL OR [ServerReceivedUtc] >= @StartDate)
      AND (@EndDate IS NULL OR [ServerReceivedUtc] <= @EndDate)
      AND (@Username IS NULL OR [WindowsUsername] LIKE @Username + '%')
      AND (@Domain IS NULL OR [Domain] = @Domain)
      AND (@MachineName IS NULL OR [MachineName] LIKE @MachineName + '%')
      AND (@Mood IS NULL OR [Mood] = @Mood)

    -- Get paginated results
    SELECT
        [Id],
        [WindowsUsername],
        [Domain],
        [MachineName],
        [UserSid],
        [Mood],
        [ClientTimestampUtc],
        [ServerReceivedUtc],
        [AppVersion],
        [OSVersion],
        [IPAddress],
        [Source]
    FROM [mood].[MoodEvents]
    WHERE (@StartDate IS NULL OR [ServerReceivedUtc] >= @StartDate)
      AND (@EndDate IS NULL OR [ServerReceivedUtc] <= @EndDate)
      AND (@Username IS NULL OR [WindowsUsername] LIKE @Username + '%')
      AND (@Domain IS NULL OR [Domain] = @Domain)
      AND (@MachineName IS NULL OR [MachineName] LIKE @MachineName + '%')
      AND (@Mood IS NULL OR [Mood] = @Mood)
    ORDER BY [ServerReceivedUtc] DESC
    OFFSET (@Page - 1) * @PageSize ROWS
    FETCH NEXT @PageSize ROWS ONLY
END
GO

PRINT 'Procedure [mood].[usp_GetMoodEvents] created.'
GO

-- ============================================================
-- Procedure: Get Daily Summary
-- ============================================================
IF EXISTS (SELECT * FROM sys.procedures WHERE name = N'usp_GetDailySummary')
    DROP PROCEDURE [mood].[usp_GetDailySummary]
GO

CREATE PROCEDURE [mood].[usp_GetDailySummary]
    @StartDate  DATE = NULL,
    @EndDate    DATE = NULL
AS
BEGIN
    SET NOCOUNT ON;

    -- Default to last 30 days
    IF @StartDate IS NULL
        SET @StartDate = DATEADD(DAY, -30, CAST(GETUTCDATE() AS DATE))

    IF @EndDate IS NULL
        SET @EndDate = CAST(GETUTCDATE() AS DATE)

    SELECT
        CAST([ServerReceivedUtc] AS DATE) AS [Date],
        [Mood],
        COUNT(*) AS [Count],
        COUNT(DISTINCT [WindowsUsername]) AS [UniqueUsers],
        COUNT(DISTINCT [MachineName]) AS [UniqueMachines]
    FROM [mood].[MoodEvents]
    WHERE CAST([ServerReceivedUtc] AS DATE) BETWEEN @StartDate AND @EndDate
    GROUP BY CAST([ServerReceivedUtc] AS DATE), [Mood]
    ORDER BY [Date] DESC, [Mood]
END
GO

PRINT 'Procedure [mood].[usp_GetDailySummary] created.'
GO

-- ============================================================
-- Procedure: Archive Old Records
-- ============================================================
IF EXISTS (SELECT * FROM sys.procedures WHERE name = N'usp_ArchiveOldRecords')
    DROP PROCEDURE [mood].[usp_ArchiveOldRecords]
GO

CREATE PROCEDURE [mood].[usp_ArchiveOldRecords]
    @RetentionDays      INT = 730,  -- 2 years default
    @BatchSize          INT = 10000,
    @ArchivedCount      INT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @CutoffDate DATETIME2(7) = DATEADD(DAY, -@RetentionDays, SYSUTCDATETIME())
    SET @ArchivedCount = 0

    -- Archive in batches to avoid long locks
    WHILE 1 = 1
    BEGIN
        BEGIN TRANSACTION

        -- Move records to archive
        INSERT INTO [mood].[MoodEvents_Archive]
        (
            [Id], [WindowsUsername], [Domain], [UserSid], [MachineName],
            [Mood], [ClientTimestampUtc], [ServerReceivedUtc],
            [AppVersion], [OSVersion], [IPAddress], [Source], [CreatedAt]
        )
        SELECT TOP (@BatchSize)
            [Id], [WindowsUsername], [Domain], [UserSid], [MachineName],
            [Mood], [ClientTimestampUtc], [ServerReceivedUtc],
            [AppVersion], [OSVersion], [IPAddress], [Source], [CreatedAt]
        FROM [mood].[MoodEvents]
        WHERE [ServerReceivedUtc] < @CutoffDate
          AND [Id] NOT IN (SELECT [Id] FROM [mood].[MoodEvents_Archive])

        IF @@ROWCOUNT = 0
        BEGIN
            COMMIT TRANSACTION
            BREAK
        END

        SET @ArchivedCount = @ArchivedCount + @@ROWCOUNT

        -- Delete archived records
        DELETE TOP (@BatchSize)
        FROM [mood].[MoodEvents]
        WHERE [ServerReceivedUtc] < @CutoffDate
          AND [Id] IN (SELECT [Id] FROM [mood].[MoodEvents_Archive])

        COMMIT TRANSACTION

        -- Brief pause to reduce lock contention
        WAITFOR DELAY '00:00:00.100'
    END

    PRINT 'Archived ' + CAST(@ArchivedCount AS NVARCHAR(20)) + ' records.'
END
GO

PRINT 'Procedure [mood].[usp_ArchiveOldRecords] created.'
GO

-- ============================================================
-- Procedure: Check Duplicate Submission (Rate Limiting)
-- ============================================================
IF EXISTS (SELECT * FROM sys.procedures WHERE name = N'usp_CheckDuplicateSubmission')
    DROP PROCEDURE [mood].[usp_CheckDuplicateSubmission]
GO

CREATE PROCEDURE [mood].[usp_CheckDuplicateSubmission]
    @WindowsUsername    NVARCHAR(256),
    @Domain             NVARCHAR(256) = NULL,
    @MinuteWindow       INT = 60,  -- Check within last N minutes
    @IsDuplicate        BIT OUTPUT
AS
BEGIN
    SET NOCOUNT ON;

    DECLARE @WindowStart DATETIME2(7) = DATEADD(MINUTE, -@MinuteWindow, SYSUTCDATETIME())

    IF EXISTS (
        SELECT 1
        FROM [mood].[MoodEvents]
        WHERE [WindowsUsername] = @WindowsUsername
          AND ([Domain] = @Domain OR (@Domain IS NULL AND [Domain] IS NULL))
          AND [ServerReceivedUtc] >= @WindowStart
    )
        SET @IsDuplicate = 1
    ELSE
        SET @IsDuplicate = 0
END
GO

PRINT 'Procedure [mood].[usp_CheckDuplicateSubmission] created.'
GO

PRINT 'Stored procedures script completed.'
GO
