-- ============================================================
-- HappinessMeter Seed Data Script
-- Script: 04-SeedData.sql
-- Description: Inserts sample data for testing (DEV/TEST only)
-- WARNING: Do NOT run in production!
-- ============================================================

USE [MoodTracking]
GO

-- ============================================================
-- Only proceed if in development mode
-- Check for a flag or uncomment for development use
-- ============================================================
/*
DECLARE @Environment NVARCHAR(50) = 'Development' -- Change to 'Production' to skip

IF @Environment = 'Production'
BEGIN
    PRINT 'Skipping seed data in Production environment.'
    RETURN
END
*/

-- ============================================================
-- Insert Sample Mood Events
-- ============================================================
PRINT 'Inserting sample mood events...'

-- Clear existing test data (optional - use with caution)
-- DELETE FROM [mood].[MoodEvents] WHERE [Source] = 'SeedScript'

DECLARE @BaseDate DATETIME2 = DATEADD(DAY, -30, SYSUTCDATETIME())
DECLARE @Counter INT = 0
DECLARE @MaxRecords INT = 100
DECLARE @RandomMood NVARCHAR(50)
DECLARE @RandomUser NVARCHAR(256)
DECLARE @RandomMachine NVARCHAR(256)
DECLARE @RandomDays INT
DECLARE @RandomHours INT

-- Sample user pool
DECLARE @Users TABLE (Username NVARCHAR(256), Domain NVARCHAR(256))
INSERT INTO @Users VALUES
    ('jsmith', 'CONTOSO'),
    ('mjohnson', 'CONTOSO'),
    ('agarcia', 'CONTOSO'),
    ('bwilliams', 'CONTOSO'),
    ('cdavis', 'CONTOSO'),
    ('dmiller', 'CORP'),
    ('ewilson', 'CORP'),
    ('fmoore', 'CORP'),
    ('gtaylor', 'CORP'),
    ('handerson', 'CORP')

-- Sample machine pool
DECLARE @Machines TABLE (MachineName NVARCHAR(256))
INSERT INTO @Machines VALUES
    ('WS-DEV-001'),
    ('WS-DEV-002'),
    ('WS-QA-001'),
    ('WS-SALES-001'),
    ('WS-SALES-002'),
    ('WS-HR-001'),
    ('WS-FIN-001'),
    ('WS-IT-001'),
    ('WS-IT-002'),
    ('WS-EXEC-001')

-- Insert random mood events
WHILE @Counter < @MaxRecords
BEGIN
    -- Random mood selection
    SELECT @RandomMood = CASE (ABS(CHECKSUM(NEWID())) % 10)
        WHEN 0 THEN 'Sad'
        WHEN 1 THEN 'Sad'
        WHEN 2 THEN 'Unhappy'
        WHEN 3 THEN 'Unhappy'
        WHEN 4 THEN 'Unhappy'
        ELSE 'Happy'  -- 50% happy
    END

    -- Random user
    SELECT TOP 1 @RandomUser = Username
    FROM @Users
    ORDER BY NEWID()

    -- Random machine
    SELECT TOP 1 @RandomMachine = MachineName
    FROM @Machines
    ORDER BY NEWID()

    -- Random timestamp within last 30 days
    SET @RandomDays = ABS(CHECKSUM(NEWID())) % 30
    SET @RandomHours = ABS(CHECKSUM(NEWID())) % 10 + 7  -- Between 7 AM and 5 PM

    INSERT INTO [mood].[MoodEvents]
    (
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
    SELECT
        @RandomUser,
        u.Domain,
        @RandomMachine,
        'S-1-5-21-' + CAST(ABS(CHECKSUM(NEWID())) AS NVARCHAR(20)),
        @RandomMood,
        DATEADD(HOUR, @RandomHours, DATEADD(DAY, @RandomDays, @BaseDate)),
        DATEADD(SECOND, ABS(CHECKSUM(NEWID())) % 5, DATEADD(HOUR, @RandomHours, DATEADD(DAY, @RandomDays, @BaseDate))),
        '1.0.' + CAST(ABS(CHECKSUM(NEWID())) % 5 AS NVARCHAR(5)) + '.0',
        'Microsoft Windows NT 10.0.' + CAST(19000 + (ABS(CHECKSUM(NEWID())) % 5000) AS NVARCHAR(10)),
        '10.0.1.' + CAST(ABS(CHECKSUM(NEWID())) % 255 AS NVARCHAR(5)),
        'SeedScript'
    FROM @Users u
    WHERE u.Username = @RandomUser

    SET @Counter = @Counter + 1
END

PRINT CAST(@Counter AS NVARCHAR(10)) + ' sample mood events inserted.'
GO

-- ============================================================
-- Verify Seed Data
-- ============================================================
SELECT
    'Total Records' AS Metric,
    COUNT(*) AS Value
FROM [mood].[MoodEvents]

UNION ALL

SELECT
    'Happy Count',
    COUNT(*)
FROM [mood].[MoodEvents]
WHERE [Mood] = 'Happy'

UNION ALL

SELECT
    'Unhappy Count',
    COUNT(*)
FROM [mood].[MoodEvents]
WHERE [Mood] = 'Unhappy'

UNION ALL

SELECT
    'Sad Count',
    COUNT(*)
FROM [mood].[MoodEvents]
WHERE [Mood] = 'Sad'

UNION ALL

SELECT
    'Unique Users',
    COUNT(DISTINCT [WindowsUsername])
FROM [mood].[MoodEvents]

UNION ALL

SELECT
    'Unique Machines',
    COUNT(DISTINCT [MachineName])
FROM [mood].[MoodEvents]

UNION ALL

SELECT
    'Date Range Start',
    CAST(MIN([ServerReceivedUtc]) AS NVARCHAR(50))
FROM [mood].[MoodEvents]

UNION ALL

SELECT
    'Date Range End',
    CAST(MAX([ServerReceivedUtc]) AS NVARCHAR(50))
FROM [mood].[MoodEvents]
GO

PRINT 'Seed data script completed.'
GO
