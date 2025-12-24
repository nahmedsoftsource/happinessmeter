-- ============================================================
-- HappinessMeter Table Creation Script
-- Script: 02-CreateTables.sql
-- Description: Creates the MoodEvents table and related objects
-- ============================================================

USE [MoodTracking]
GO

-- ============================================================
-- Create Schema (optional, for better organization)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.schemas WHERE name = N'mood')
BEGIN
    EXEC('CREATE SCHEMA [mood] AUTHORIZATION [dbo]')
    PRINT 'Schema [mood] created.'
END
GO

-- ============================================================
-- Drop existing table if recreating (CAUTION: removes data!)
-- Uncomment only for development reset
-- ============================================================
-- DROP TABLE IF EXISTS [mood].[MoodEvents]
-- GO

-- ============================================================
-- Create MoodEvents Table
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[mood].[MoodEvents]') AND type in (N'U'))
BEGIN
    CREATE TABLE [mood].[MoodEvents]
    (
        -- Primary Key
        [Id]                    UNIQUEIDENTIFIER    NOT NULL DEFAULT NEWSEQUENTIALID(),

        -- User Identity
        [WindowsUsername]       NVARCHAR(256)       NOT NULL,
        [Domain]                NVARCHAR(256)       NULL,
        [UserSid]               NVARCHAR(256)       NULL,

        -- Machine Information
        [MachineName]           NVARCHAR(256)       NOT NULL,

        -- Mood Data
        [Mood]                  NVARCHAR(50)        NOT NULL,

        -- Timestamps
        [ClientTimestampUtc]    DATETIME2(7)        NOT NULL,
        [ServerReceivedUtc]     DATETIME2(7)        NOT NULL DEFAULT SYSUTCDATETIME(),

        -- Metadata
        [AppVersion]            NVARCHAR(50)        NULL,
        [OSVersion]             NVARCHAR(256)       NULL,
        [IPAddress]             NVARCHAR(45)        NULL,  -- Supports IPv6
        [Source]                NVARCHAR(50)        NULL,  -- e.g., 'DesktopApp', 'WebPortal'

        -- Audit
        [RowVersion]            ROWVERSION          NOT NULL,
        [CreatedAt]             DATETIME2(7)        NOT NULL DEFAULT SYSUTCDATETIME(),

        -- Constraints
        CONSTRAINT [PK_MoodEvents] PRIMARY KEY CLUSTERED ([Id] ASC)
            WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, IGNORE_DUP_KEY = OFF,
                  ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON, OPTIMIZE_FOR_SEQUENTIAL_KEY = ON),

        CONSTRAINT [CK_MoodEvents_Mood] CHECK ([Mood] IN (N'Happy', N'Unhappy', N'Sad')),

        CONSTRAINT [CK_MoodEvents_ClientTimestamp] CHECK ([ClientTimestampUtc] <= DATEADD(HOUR, 1, SYSUTCDATETIME()))
    )

    PRINT 'Table [mood].[MoodEvents] created successfully.'
END
ELSE
BEGIN
    PRINT 'Table [mood].[MoodEvents] already exists.'
END
GO

-- ============================================================
-- Create Archive Table (for data retention)
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[mood].[MoodEvents_Archive]') AND type in (N'U'))
BEGIN
    CREATE TABLE [mood].[MoodEvents_Archive]
    (
        [Id]                    UNIQUEIDENTIFIER    NOT NULL,
        [WindowsUsername]       NVARCHAR(256)       NOT NULL,
        [Domain]                NVARCHAR(256)       NULL,
        [UserSid]               NVARCHAR(256)       NULL,
        [MachineName]           NVARCHAR(256)       NOT NULL,
        [Mood]                  NVARCHAR(50)        NOT NULL,
        [ClientTimestampUtc]    DATETIME2(7)        NOT NULL,
        [ServerReceivedUtc]     DATETIME2(7)        NOT NULL,
        [AppVersion]            NVARCHAR(50)        NULL,
        [OSVersion]             NVARCHAR(256)       NULL,
        [IPAddress]             NVARCHAR(45)        NULL,
        [Source]                NVARCHAR(50)        NULL,
        [CreatedAt]             DATETIME2(7)        NOT NULL,
        [ArchivedAt]            DATETIME2(7)        NOT NULL DEFAULT SYSUTCDATETIME(),

        CONSTRAINT [PK_MoodEvents_Archive] PRIMARY KEY CLUSTERED ([Id] ASC)
    )

    PRINT 'Table [mood].[MoodEvents_Archive] created successfully.'
END
GO

-- ============================================================
-- Create Daily Summary View (for reporting)
-- ============================================================
IF EXISTS (SELECT * FROM sys.views WHERE object_id = OBJECT_ID(N'[mood].[vw_DailyMoodSummary]'))
    DROP VIEW [mood].[vw_DailyMoodSummary]
GO

CREATE VIEW [mood].[vw_DailyMoodSummary]
AS
SELECT
    CAST([ServerReceivedUtc] AS DATE) AS [Date],
    [Mood],
    COUNT(*) AS [Count],
    COUNT(DISTINCT [WindowsUsername]) AS [UniqueUsers],
    COUNT(DISTINCT [MachineName]) AS [UniqueMachines]
FROM [mood].[MoodEvents]
GROUP BY CAST([ServerReceivedUtc] AS DATE), [Mood]
GO

PRINT 'View [mood].[vw_DailyMoodSummary] created successfully.'
GO

-- ============================================================
-- Create User Mood History View
-- ============================================================
IF EXISTS (SELECT * FROM sys.views WHERE object_id = OBJECT_ID(N'[mood].[vw_UserMoodHistory]'))
    DROP VIEW [mood].[vw_UserMoodHistory]
GO

CREATE VIEW [mood].[vw_UserMoodHistory]
AS
SELECT
    [Id],
    [WindowsUsername],
    [Domain],
    CASE
        WHEN [Domain] IS NOT NULL THEN [Domain] + N'\' + [WindowsUsername]
        ELSE [WindowsUsername]
    END AS [FullUsername],
    [MachineName],
    [Mood],
    [ClientTimestampUtc],
    [ServerReceivedUtc],
    [AppVersion]
FROM [mood].[MoodEvents]
GO

PRINT 'View [mood].[vw_UserMoodHistory] created successfully.'
GO

-- ============================================================
-- Grant Permissions to API Service Account
-- Replace 'DOMAIN\MoodApiService' with your actual service account
-- ============================================================

-- Create a database user for the API service account (Windows Auth)
-- Uncomment and modify for your environment:

/*
IF NOT EXISTS (SELECT * FROM sys.database_principals WHERE name = N'DOMAIN\MoodApiService')
BEGIN
    CREATE USER [DOMAIN\MoodApiService] FOR LOGIN [DOMAIN\MoodApiService]
    PRINT 'Database user [DOMAIN\MoodApiService] created.'
END
GO

-- Grant necessary permissions
GRANT SELECT, INSERT ON [mood].[MoodEvents] TO [DOMAIN\MoodApiService]
GRANT SELECT ON [mood].[vw_DailyMoodSummary] TO [DOMAIN\MoodApiService]
GRANT SELECT ON [mood].[vw_UserMoodHistory] TO [DOMAIN\MoodApiService]

PRINT 'Permissions granted to [DOMAIN\MoodApiService].'
GO
*/

PRINT 'Table creation script completed successfully.'
GO
