-- ============================================================
-- HappinessMeter Database Creation Script
-- Script: 01-CreateDatabase.sql
-- Description: Creates the MoodTracking database
-- ============================================================

USE [master]
GO

-- Check if database exists and create if not
IF NOT EXISTS (SELECT name FROM sys.databases WHERE name = N'MoodTracking')
BEGIN
    CREATE DATABASE [MoodTracking]
    CONTAINMENT = NONE
    ON PRIMARY
    (
        NAME = N'MoodTracking',
        FILENAME = N'C:\SQLData\MoodTracking.mdf',
        SIZE = 100MB,
        MAXSIZE = UNLIMITED,
        FILEGROWTH = 100MB
    )
    LOG ON
    (
        NAME = N'MoodTracking_log',
        FILENAME = N'C:\SQLData\MoodTracking_log.ldf',
        SIZE = 50MB,
        MAXSIZE = 2048GB,
        FILEGROWTH = 50MB
    )

    PRINT 'Database [MoodTracking] created successfully.'
END
ELSE
BEGIN
    PRINT 'Database [MoodTracking] already exists.'
END
GO

-- Set recovery model to SIMPLE for less log growth (adjust for production needs)
ALTER DATABASE [MoodTracking] SET RECOVERY SIMPLE
GO

-- Enable Query Store for performance monitoring (SQL Server 2016+)
ALTER DATABASE [MoodTracking] SET QUERY_STORE = ON
GO

ALTER DATABASE [MoodTracking] SET QUERY_STORE (
    OPERATION_MODE = READ_WRITE,
    CLEANUP_POLICY = (STALE_QUERY_THRESHOLD_DAYS = 30),
    DATA_FLUSH_INTERVAL_SECONDS = 900,
    MAX_STORAGE_SIZE_MB = 100,
    INTERVAL_LENGTH_MINUTES = 60
)
GO

USE [MoodTracking]
GO

PRINT 'Database configuration completed.'
GO
