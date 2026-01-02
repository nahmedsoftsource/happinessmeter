# HappinessMeter Build Script
# This script publishes the app and creates an installer

param(
    [switch]$SkipPublish,
    [switch]$SkipInstaller
)

$ErrorActionPreference = "Stop"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  HappinessMeter Build Script" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Paths
$projectPath = "src\MoodDesktopApp\MoodDesktopApp.csproj"
$publishPath = "publish"
$installerScript = "installer\HappinessMeterSetup.iss"
$outputPath = "output"

# Step 1: Publish the application
if (-not $SkipPublish) {
    Write-Host "[1/3] Publishing application..." -ForegroundColor Yellow

    # Clean publish folder
    if (Test-Path $publishPath) {
        Remove-Item -Path $publishPath -Recurse -Force
    }

    # Publish as self-contained single file
    dotnet publish $projectPath `
        -c Release `
        -r win-x64 `
        --self-contained true `
        -p:PublishSingleFile=false `
        -p:IncludeNativeLibrariesForSelfExtract=true `
        -o $publishPath

    if ($LASTEXITCODE -ne 0) {
        Write-Host "ERROR: Publish failed!" -ForegroundColor Red
        exit 1
    }

    Write-Host "Published to: $publishPath" -ForegroundColor Green
} else {
    Write-Host "[1/3] Skipping publish (using existing files)" -ForegroundColor Gray
}

# Step 2: Create output directory
Write-Host "[2/3] Preparing output directory..." -ForegroundColor Yellow
if (-not (Test-Path $outputPath)) {
    New-Item -ItemType Directory -Path $outputPath | Out-Null
}

# Step 3: Build installer
if (-not $SkipInstaller) {
    Write-Host "[3/3] Building installer..." -ForegroundColor Yellow

    # Check if Inno Setup is installed
    $innoSetupPath = "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe"
    if (-not (Test-Path $innoSetupPath)) {
        $innoSetupPath = "${env:ProgramFiles}\Inno Setup 6\ISCC.exe"
    }

    if (Test-Path $innoSetupPath) {
        & $innoSetupPath $installerScript

        if ($LASTEXITCODE -ne 0) {
            Write-Host "ERROR: Installer build failed!" -ForegroundColor Red
            exit 1
        }

        Write-Host "Installer created: output\HappinessMeterSetup.exe" -ForegroundColor Green
    } else {
        Write-Host "WARNING: Inno Setup not found!" -ForegroundColor Yellow
        Write-Host "Download from: https://jrsoftware.org/isdl.php" -ForegroundColor Yellow
        Write-Host ""
        Write-Host "After installing Inno Setup, you can:" -ForegroundColor Yellow
        Write-Host "  1. Open installer\HappinessMeterSetup.iss in Inno Setup" -ForegroundColor White
        Write-Host "  2. Click Build > Compile" -ForegroundColor White
        Write-Host "  3. Installer will be in 'output' folder" -ForegroundColor White
    }
} else {
    Write-Host "[3/3] Skipping installer build" -ForegroundColor Gray
}

Write-Host ""
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "  Build Complete!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Files:" -ForegroundColor White
Write-Host "  - Published app: $publishPath\" -ForegroundColor Gray
Write-Host "  - Installer: $outputPath\HappinessMeterSetup.exe" -ForegroundColor Gray
