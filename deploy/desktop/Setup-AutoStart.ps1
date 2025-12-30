#Requires -RunAsAdministrator
<#
.SYNOPSIS
    Sets up HappinessMeter to automatically start at Windows login.

.DESCRIPTION
    This script creates a Windows Task Scheduler task that runs HappinessMeter
    when a user logs in. The app will only show during office hours (8am-5pm)
    and will not prompt again for 24 hours after submission.

.PARAMETER AppPath
    Path to the MoodDesktopApp.exe executable. If not specified, uses the
    default installation path.

.PARAMETER Uninstall
    Removes the scheduled task instead of creating it.

.EXAMPLE
    .\Setup-AutoStart.ps1
    Creates the scheduled task with default settings.

.EXAMPLE
    .\Setup-AutoStart.ps1 -AppPath "C:\Program Files\HappinessMeter\MoodDesktopApp.exe"
    Creates the scheduled task with a custom app path.

.EXAMPLE
    .\Setup-AutoStart.ps1 -Uninstall
    Removes the scheduled task.
#>

param(
    [string]$AppPath = "",
    [switch]$Uninstall
)

$TaskName = "HappinessMeter"
$TaskDescription = "Launches HappinessMeter mood tracking app at user login (8am-5pm only)"

# Find app path if not specified
if ([string]::IsNullOrEmpty($AppPath)) {
    $possiblePaths = @(
        "$PSScriptRoot\..\..\publish\MoodDesktopApp.exe",
        "$env:ProgramFiles\HappinessMeter\MoodDesktopApp.exe",
        "$env:LOCALAPPDATA\HappinessMeter\MoodDesktopApp.exe",
        ".\MoodDesktopApp.exe"
    )

    foreach ($path in $possiblePaths) {
        if (Test-Path $path) {
            $AppPath = (Resolve-Path $path).Path
            break
        }
    }
}

function Install-Task {
    if ([string]::IsNullOrEmpty($AppPath) -or !(Test-Path $AppPath)) {
        Write-Error "Could not find MoodDesktopApp.exe. Please specify -AppPath parameter."
        Write-Host ""
        Write-Host "Example:"
        Write-Host "  .\Setup-AutoStart.ps1 -AppPath 'C:\path\to\MoodDesktopApp.exe'"
        exit 1
    }

    Write-Host "Setting up HappinessMeter auto-start..." -ForegroundColor Cyan
    Write-Host "App Path: $AppPath"
    Write-Host ""

    # Remove existing task if present
    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Write-Host "Removing existing task..." -ForegroundColor Yellow
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
    }

    # Create the task action
    $action = New-ScheduledTaskAction -Execute $AppPath

    # Create the trigger - at user logon
    $trigger = New-ScheduledTaskTrigger -AtLogOn

    # Create task settings
    $settings = New-ScheduledTaskSettingsSet `
        -AllowStartIfOnBatteries `
        -DontStopIfGoingOnBatteries `
        -StartWhenAvailable `
        -ExecutionTimeLimit (New-TimeSpan -Minutes 5) `
        -RestartCount 3 `
        -RestartInterval (New-TimeSpan -Minutes 1)

    # Create the principal (run as current user)
    $principal = New-ScheduledTaskPrincipal `
        -GroupId "BUILTIN\Users" `
        -RunLevel Limited

    # Register the task
    try {
        Register-ScheduledTask `
            -TaskName $TaskName `
            -Description $TaskDescription `
            -Action $action `
            -Trigger $trigger `
            -Settings $settings `
            -Principal $principal `
            -Force | Out-Null

        Write-Host ""
        Write-Host "SUCCESS! HappinessMeter will now start automatically at login." -ForegroundColor Green
        Write-Host ""
        Write-Host "Behavior:"
        Write-Host "  - App launches at each Windows login"
        Write-Host "  - Only shows popup during office hours (8am - 5pm)"
        Write-Host "  - After submitting mood, won't show again for 24 hours"
        Write-Host "  - Disappears automatically after submission"
        Write-Host ""
        Write-Host "To test, run:" -ForegroundColor Cyan
        Write-Host "  Start-ScheduledTask -TaskName '$TaskName'"
        Write-Host ""
        Write-Host "To remove:" -ForegroundColor Yellow
        Write-Host "  .\Setup-AutoStart.ps1 -Uninstall"
    }
    catch {
        Write-Error "Failed to create scheduled task: $_"
        exit 1
    }
}

function Uninstall-Task {
    Write-Host "Removing HappinessMeter auto-start..." -ForegroundColor Cyan

    $existingTask = Get-ScheduledTask -TaskName $TaskName -ErrorAction SilentlyContinue
    if ($existingTask) {
        Unregister-ScheduledTask -TaskName $TaskName -Confirm:$false
        Write-Host "SUCCESS! Scheduled task removed." -ForegroundColor Green
    }
    else {
        Write-Host "Task '$TaskName' not found. Nothing to remove." -ForegroundColor Yellow
    }
}

# Main
Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  HappinessMeter Auto-Start Setup" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

if ($Uninstall) {
    Uninstall-Task
}
else {
    Install-Task
}
