-- ============================================================
-- HappinessMeter Index Creation Script
-- Script: 03-CreateIndexes.sql
-- Description: Creates indexes for optimal query performance
-- ============================================================

USE [MoodTracking]
GO

-- ============================================================
-- Index: Query by Username and Date Range
-- Use Case: Get mood history for a specific user
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_WindowsUsername_ServerReceivedUtc')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_WindowsUsername_ServerReceivedUtc]
    ON [mood].[MoodEvents] ([WindowsUsername] ASC, [ServerReceivedUtc] DESC)
    INCLUDE ([Domain], [MachineName], [Mood], [ClientTimestampUtc], [AppVersion])
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_WindowsUsername_ServerReceivedUtc] created.'
END
GO

-- ============================================================
-- Index: Query by Server Timestamp (Descending)
-- Use Case: Recent moods, pagination, date range queries
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_ServerReceivedUtc')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_ServerReceivedUtc]
    ON [mood].[MoodEvents] ([ServerReceivedUtc] DESC)
    INCLUDE ([WindowsUsername], [Domain], [MachineName], [Mood], [AppVersion])
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_ServerReceivedUtc] created.'
END
GO

-- ============================================================
-- Index: Query by Mood Type
-- Use Case: Filter by specific mood, aggregate counts
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_Mood')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_Mood]
    ON [mood].[MoodEvents] ([Mood] ASC, [ServerReceivedUtc] DESC)
    INCLUDE ([WindowsUsername], [Domain], [MachineName])
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_Mood] created.'
END
GO

-- ============================================================
-- Index: Query by Machine Name
-- Use Case: Find all moods from a specific machine
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_MachineName')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_MachineName]
    ON [mood].[MoodEvents] ([MachineName] ASC, [ServerReceivedUtc] DESC)
    INCLUDE ([WindowsUsername], [Domain], [Mood])
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_MachineName] created.'
END
GO

-- ============================================================
-- Index: Query by Domain
-- Use Case: Find moods from specific domain/department
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_Domain')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_Domain]
    ON [mood].[MoodEvents] ([Domain] ASC, [ServerReceivedUtc] DESC)
    INCLUDE ([WindowsUsername], [MachineName], [Mood])
    WHERE [Domain] IS NOT NULL
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_Domain] created.'
END
GO

-- ============================================================
-- Index: Composite for Daily Summary View
-- Use Case: Aggregation queries for reporting
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_DateMood')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_DateMood]
    ON [mood].[MoodEvents] (CAST([ServerReceivedUtc] AS DATE), [Mood])
    INCLUDE ([WindowsUsername], [MachineName])
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_DateMood] created.'
END
GO

-- ============================================================
-- Index: Archive Table - Date Range Queries
-- ============================================================
IF NOT EXISTS (SELECT * FROM sys.indexes WHERE name = N'IX_MoodEvents_Archive_ServerReceivedUtc')
BEGIN
    CREATE NONCLUSTERED INDEX [IX_MoodEvents_Archive_ServerReceivedUtc]
    ON [mood].[MoodEvents_Archive] ([ServerReceivedUtc] DESC)
    INCLUDE ([WindowsUsername], [Mood])
    WITH (PAD_INDEX = OFF, STATISTICS_NORECOMPUTE = OFF, SORT_IN_TEMPDB = ON,
          DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS = ON, ALLOW_PAGE_LOCKS = ON)

    PRINT 'Index [IX_MoodEvents_Archive_ServerReceivedUtc] created.'
END
GO

-- ============================================================
-- Update Statistics
-- ============================================================
UPDATE STATISTICS [mood].[MoodEvents]
PRINT 'Statistics updated for [mood].[MoodEvents].'
GO

-- ============================================================
-- Display Index Summary
-- ============================================================
SELECT
    i.name AS IndexName,
    i.type_desc AS IndexType,
    i.is_unique AS IsUnique,
    i.is_primary_key AS IsPrimaryKey,
    STUFF((
        SELECT ', ' + c.name
        FROM sys.index_columns ic
        INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
        WHERE ic.object_id = i.object_id AND ic.index_id = i.index_id AND ic.is_included_column = 0
        ORDER BY ic.key_ordinal
        FOR XML PATH('')
    ), 1, 2, '') AS KeyColumns,
    STUFF((
        SELECT ', ' + c.name
        FROM sys.index_columns ic
        INNER JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
        WHERE ic.object_id = i.object_id AND ic.index_id = i.index_id AND ic.is_included_column = 1
        ORDER BY ic.key_ordinal
        FOR XML PATH('')
    ), 1, 2, '') AS IncludedColumns
FROM sys.indexes i
WHERE i.object_id = OBJECT_ID('[mood].[MoodEvents]')
ORDER BY i.index_id

PRINT 'Index creation script completed successfully.'
GO
