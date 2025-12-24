# HappinessMeter Deployment Guide

This guide covers deploying both the API and Desktop application in an on-premise environment.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Database Setup](#database-setup)
3. [API Deployment (IIS)](#api-deployment-iis)
4. [Desktop App Deployment](#desktop-app-deployment)
5. [Security Configuration](#security-configuration)
6. [Monitoring & Troubleshooting](#monitoring--troubleshooting)

---

## Prerequisites

### Server Requirements

**API Server:**
- Windows Server 2019 or 2022
- IIS 10 with ASP.NET Core Hosting Bundle 8.0
- .NET 8.0 Runtime
- Network access to SQL Server
- SSL certificate (internal CA or commercial)

**SQL Server:**
- SQL Server 2019 or later
- Windows Authentication enabled
- Network accessible from API server

**Client Workstations:**
- Windows 10 (1809+) or Windows 11
- .NET 8.0 Runtime (or bundle with self-contained publish)
- Domain-joined (for Windows Authentication)

### Download Links

- [.NET 8.0 Runtime](https://dotnet.microsoft.com/download/dotnet/8.0)
- [ASP.NET Core Hosting Bundle](https://dotnet.microsoft.com/download/dotnet/8.0)

---

## Database Setup

### 1. Create Database

Connect to SQL Server with admin credentials and run the scripts in order:

```powershell
# Using sqlcmd
sqlcmd -S SQLSERVER01 -E -i database\scripts\01-CreateDatabase.sql
sqlcmd -S SQLSERVER01 -E -i database\scripts\02-CreateTables.sql
sqlcmd -S SQLSERVER01 -E -i database\scripts\03-CreateIndexes.sql
sqlcmd -S SQLSERVER01 -E -i database\scripts\05-StoredProcedures.sql
```

### 2. Create Service Account

Create a domain service account for the API to use:

```sql
-- On SQL Server
USE [MoodTracking]
GO

-- Create login for the API service account
CREATE LOGIN [CONTOSO\MoodApiService] FROM WINDOWS
GO

-- Create database user
CREATE USER [CONTOSO\MoodApiService] FOR LOGIN [CONTOSO\MoodApiService]
GO

-- Grant permissions
EXEC sp_addrolemember 'db_datareader', 'CONTOSO\MoodApiService'
EXEC sp_addrolemember 'db_datawriter', 'CONTOSO\MoodApiService'
GO

-- Grant execute on stored procedures
GRANT EXECUTE ON SCHEMA::mood TO [CONTOSO\MoodApiService]
GO
```

### 3. Verify Connection

Test the connection from the API server:

```powershell
# Test SQL connection
sqlcmd -S SQLSERVER01 -E -d MoodTracking -Q "SELECT @@VERSION"
```

---

## API Deployment (IIS)

### 1. Install IIS and Dependencies

Run as Administrator on the API server:

```powershell
# Install IIS features
Install-WindowsFeature -Name Web-Server -IncludeManagementTools
Install-WindowsFeature -Name Web-Windows-Auth
Install-WindowsFeature -Name Web-Mgmt-Console

# Install ASP.NET Core Hosting Bundle
# Download and run: dotnet-hosting-8.0.x-win.exe

# Verify module is registered
Get-WebGlobalModule | Where-Object { $_.Name -like "*AspNetCore*" }
```

### 2. Publish API

On your build machine:

```powershell
# Navigate to API project
cd src\MoodTracking.Api

# Publish for production
dotnet publish -c Release -o C:\publish\MoodTracking.Api

# Or publish self-contained (no runtime required on server)
dotnet publish -c Release --self-contained -r win-x64 -o C:\publish\MoodTracking.Api
```

### 3. Configure IIS Site

```powershell
# Create application pool
New-WebAppPool -Name "MoodTrackingAppPool"
Set-ItemProperty -Path "IIS:\AppPools\MoodTrackingAppPool" -Name "managedRuntimeVersion" -Value ""
Set-ItemProperty -Path "IIS:\AppPools\MoodTrackingAppPool" -Name "processModel.identityType" -Value "SpecificUser"
Set-ItemProperty -Path "IIS:\AppPools\MoodTrackingAppPool" -Name "processModel.userName" -Value "CONTOSO\MoodApiService"
Set-ItemProperty -Path "IIS:\AppPools\MoodTrackingAppPool" -Name "processModel.password" -Value "YourSecurePassword"

# Create website
New-Website -Name "MoodTracking" `
    -PhysicalPath "C:\inetpub\MoodTracking.Api" `
    -ApplicationPool "MoodTrackingAppPool" `
    -Port 443 `
    -Ssl `
    -HostHeader "moodtracker.contoso.local"

# Copy published files
Copy-Item -Path "C:\publish\MoodTracking.Api\*" -Destination "C:\inetpub\MoodTracking.Api" -Recurse
```

### 4. Configure Windows Authentication

```powershell
# Disable anonymous auth
Set-WebConfigurationProperty -PSPath "MACHINE/WEBROOT/APPHOST/MoodTracking" `
    -Filter "system.webServer/security/authentication/anonymousAuthentication" `
    -Name "enabled" -Value $false

# Enable Windows auth
Set-WebConfigurationProperty -PSPath "MACHINE/WEBROOT/APPHOST/MoodTracking" `
    -Filter "system.webServer/security/authentication/windowsAuthentication" `
    -Name "enabled" -Value $true
```

### 5. Bind SSL Certificate

```powershell
# If using internal CA, import the certificate first
Import-PfxCertificate -FilePath "C:\certs\moodtracker.pfx" `
    -CertStoreLocation "Cert:\LocalMachine\My" `
    -Password (ConvertTo-SecureString -String "CertPassword" -Force -AsPlainText)

# Get certificate thumbprint
$cert = Get-ChildItem -Path "Cert:\LocalMachine\My" | Where-Object { $_.Subject -like "*moodtracker*" }

# Bind to website
New-WebBinding -Name "MoodTracking" -Protocol "https" -Port 443 -HostHeader "moodtracker.contoso.local" -SslFlags 1
$binding = Get-WebBinding -Name "MoodTracking" -Protocol "https"
$binding.AddSslCertificate($cert.Thumbprint, "My")
```

### 6. Configure appsettings.Production.json

Edit `C:\inetpub\MoodTracking.Api\appsettings.Production.json`:

```json
{
  "ConnectionStrings": {
    "MoodTrackingDb": "Server=SQLSERVER01;Database=MoodTracking;Integrated Security=true;TrustServerCertificate=false;Encrypt=true;"
  },
  "Serilog": {
    "MinimumLevel": {
      "Default": "Information"
    }
  },
  "EnableSwagger": false
}
```

### 7. Create DNS Record

Add a DNS A record pointing `moodtracker.contoso.local` to the API server IP.

### 8. Configure Firewall

```powershell
# Allow HTTPS inbound
New-NetFirewallRule -DisplayName "MoodTracking API (HTTPS)" `
    -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow
```

### 9. Verify Deployment

```powershell
# Test health endpoint
Invoke-RestMethod -Uri "https://moodtracker.contoso.local/health" -UseDefaultCredentials
```

---

## Desktop App Deployment

### Option 1: MSIX Package (Recommended)

#### Create MSIX Package

1. Open solution in Visual Studio 2022
2. Right-click `MoodDesktopApp` project → Publish
3. Select "Sideloading" and configure:
   - Package name: `HappinessMeter`
   - Publisher: Your organization
   - Sign with code signing certificate

4. Create the package:

```powershell
# Or use command line
dotnet publish src\MoodDesktopApp\MoodDesktopApp.csproj `
    -c Release `
    -p:PublishProfile=MSIX `
    -p:PackageCertificateKeyFile=C:\certs\CodeSigning.pfx `
    -p:PackageCertificatePassword=CertPassword
```

#### Deploy via Group Policy

1. Copy MSIX package to network share: `\\fileserver\apps\HappinessMeter\`

2. Create `.appinstaller` file for auto-updates:

```xml
<?xml version="1.0" encoding="utf-8"?>
<AppInstaller Uri="\\fileserver\apps\HappinessMeter\HappinessMeter.appinstaller"
              Version="1.0.0.0"
              xmlns="http://schemas.microsoft.com/appx/appinstaller/2017/2">
  <MainPackage Name="YourOrganization.HappinessMeter"
               Version="1.0.0.0"
               Publisher="CN=Your Organization"
               Uri="\\fileserver\apps\HappinessMeter\HappinessMeter.msix"
               ProcessorArchitecture="x64"/>
  <UpdateSettings>
    <OnLaunch HoursBetweenUpdateChecks="24"/>
  </UpdateSettings>
</AppInstaller>
```

3. Deploy via GPO or SCCM/Intune

#### Deploy Signing Certificate

Distribute the code signing certificate's public key via Group Policy:

1. Open Group Policy Management
2. Create/edit GPO
3. Navigate to: Computer Configuration → Policies → Windows Settings → Security Settings → Public Key Policies → Trusted Publishers
4. Import the certificate

### Option 2: Traditional Installer (MSI/ClickOnce)

For non-MSIX deployment:

```powershell
# Publish as self-contained
dotnet publish src\MoodDesktopApp\MoodDesktopApp.csproj `
    -c Release `
    -r win-x64 `
    --self-contained `
    -o C:\publish\MoodDesktopApp

# Create MSI with WiX or Inno Setup
# Or use ClickOnce publish profile
```

### Configure Auto-Start (Non-MSIX)

If not using MSIX startup task, configure via registry:

```powershell
# Add to HKCU Run key (per-user)
$regPath = "HKCU:\SOFTWARE\Microsoft\Windows\CurrentVersion\Run"
Set-ItemProperty -Path $regPath -Name "HappinessMeter" -Value "C:\Program Files\HappinessMeter\MoodDesktopApp.exe"

# Or via scheduled task (recommended for enterprise)
$action = New-ScheduledTaskAction -Execute "C:\Program Files\HappinessMeter\MoodDesktopApp.exe"
$trigger = New-ScheduledTaskTrigger -AtLogOn
$settings = New-ScheduledTaskSettingsSet -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries
Register-ScheduledTask -TaskName "HappinessMeter" -Action $action -Trigger $trigger -Settings $settings
```

### Configure API URL

Edit `appsettings.json` in the installation directory:

```json
{
  "Api": {
    "BaseUrl": "https://moodtracker.contoso.local"
  }
}
```

Or distribute via GPO/registry:

```powershell
# Set API URL via registry
New-Item -Path "HKLM:\SOFTWARE\HappinessMeter" -Force
Set-ItemProperty -Path "HKLM:\SOFTWARE\HappinessMeter" -Name "ApiBaseUrl" -Value "https://moodtracker.contoso.local"
```

---

## Security Configuration

### SSL Certificate Setup (Internal CA)

1. **Request certificate from internal CA:**

```
Subject: CN=moodtracker.contoso.local
SAN: DNS:moodtracker, DNS:moodtracker.contoso.local, IP:10.0.1.50
Key Usage: Digital Signature, Key Encipherment
Extended Key Usage: Server Authentication
```

2. **Export and install on IIS server**

3. **Deploy root CA to clients via GPO:**
   - Computer Configuration → Policies → Windows Settings → Security Settings → Public Key Policies → Trusted Root Certification Authorities

### Windows Authentication Requirements

- API server and client must be in the same domain (or trusted domains)
- Kerberos must be properly configured
- SPN may need to be registered:

```powershell
# Register SPN for the service account
setspn -S HTTP/moodtracker.contoso.local CONTOSO\MoodApiService
setspn -S HTTP/moodtracker CONTOSO\MoodApiService
```

### Firewall Rules

**API Server:**
```powershell
# Inbound HTTPS
New-NetFirewallRule -DisplayName "MoodTracking HTTPS In" -Direction Inbound -Protocol TCP -LocalPort 443 -Action Allow

# Outbound to SQL Server
New-NetFirewallRule -DisplayName "SQL Server Out" -Direction Outbound -Protocol TCP -RemotePort 1433 -Action Allow
```

**Client Workstations:**
```powershell
# Outbound HTTPS to API server
New-NetFirewallRule -DisplayName "MoodTracking HTTPS Out" -Direction Outbound -Protocol TCP -RemotePort 443 -Action Allow
```

---

## Monitoring & Troubleshooting

### Health Checks

```powershell
# Basic health check
Invoke-RestMethod -Uri "https://moodtracker.contoso.local/health"

# Detailed readiness check (includes DB)
Invoke-RestMethod -Uri "https://moodtracker.contoso.local/health/ready"
```

### Log Locations

**API Logs:**
- File: `C:\inetpub\MoodTracking.Api\logs\moodtracking-*.log`
- Windows Event Log: Application log, Source: "MoodTracking.Api"

**Desktop App Logs:**
- `%LOCALAPPDATA%\HappinessMeter\logs\moodapp-*.log`

### Common Issues

**1. Windows Authentication Fails (401 Unauthorized)**

Check:
- Windows Auth enabled in IIS
- SPN registered correctly
- Client is domain-joined
- No proxy interfering with NTLM/Kerberos

```powershell
# Test Kerberos
klist tickets
```

**2. Database Connection Fails**

Check:
- SQL Server is accessible from API server
- Service account has correct permissions
- Connection string is correct
- SQL Server allows Windows auth

```powershell
# Test from API server
sqlcmd -S SQLSERVER01 -E -d MoodTracking -Q "SELECT 1"
```

**3. Desktop App Can't Reach API**

Check:
- DNS resolution: `nslookup moodtracker.contoso.local`
- Firewall allows outbound 443
- SSL certificate is trusted
- VPN connected (if remote)

**4. Offline Queue Growing**

Check:
- API is reachable
- Sync service is running
- Check desktop app logs for errors

### Performance Monitoring

**API Metrics to Watch:**
- Request rate and response times
- Error rate (should be < 1%)
- Database connection pool usage
- Memory and CPU usage

**Recommended Tools:**
- Windows Performance Monitor
- Application Insights (optional)
- SQL Server Profiler for query analysis

### Backup Strategy

**Database:**
```powershell
# Daily backup job
BACKUP DATABASE [MoodTracking] TO DISK = 'C:\SQLBackups\MoodTracking_Daily.bak'
WITH COMPRESSION, INIT
```

**Configuration:**
- Backup `appsettings.Production.json`
- Export IIS site configuration
- Document SSL certificate details

---

## Upgrade Procedures

### API Upgrade

1. Take backup of current deployment
2. Publish new version
3. Stop app pool
4. Replace files
5. Start app pool
6. Verify health endpoint

```powershell
# Zero-downtime with slot deployment
Stop-WebAppPool -Name "MoodTrackingAppPool"
Copy-Item -Path "C:\publish\new\*" -Destination "C:\inetpub\MoodTracking.Api" -Recurse -Force
Start-WebAppPool -Name "MoodTrackingAppPool"
```

### Desktop App Upgrade

**MSIX:** Update `.appinstaller` file with new version; clients auto-update on launch.

**Traditional:** Deploy new version via SCCM/GPO; old version uninstalls first.

---

## Rollback Procedures

### API Rollback

```powershell
Stop-WebAppPool -Name "MoodTrackingAppPool"
# Restore from backup folder
Copy-Item -Path "C:\backup\MoodTracking.Api.previous\*" -Destination "C:\inetpub\MoodTracking.Api" -Recurse -Force
Start-WebAppPool -Name "MoodTrackingAppPool"
```

### Database Rollback

Only needed if schema changed. Test migrations in staging first.

```sql
-- If using EF Core migrations
dotnet ef database update PreviousMigration
```

---

*Document Version: 1.0.0*
*Last Updated: 2024*
