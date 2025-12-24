# HappinessMeter - Mood Tracking System Architecture

## Executive Summary

HappinessMeter is an enterprise Windows desktop mood-tracking solution consisting of two main components:
1. **MoodDesktopApp** - A lightweight WPF application that captures employee mood at login
2. **MoodTracking.Api** - An ASP.NET Core Web API that receives and stores mood data in SQL Server

This document provides complete architecture, design decisions, and implementation guidance.

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Technology Decisions & Justifications](#2-technology-decisions--justifications)
3. [Data Flow](#3-data-flow)
4. [Security Architecture](#4-security-architecture)
5. [Database Design](#5-database-design)
6. [API Contract](#6-api-contract)
7. [Solution Structure](#7-solution-structure)
8. [Deployment Strategy](#8-deployment-strategy)
9. [Best Practices Checklist](#9-best-practices-checklist)

---

## 1. Architecture Overview

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                           CORPORATE NETWORK (LAN/VPN)                        │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                              │
│  ┌──────────────────┐     HTTPS/443      ┌──────────────────────────────┐  │
│  │  Windows Client  │ ─────────────────► │     IIS Web Server           │  │
│  │  Workstation     │                    │                              │  │
│  │                  │                    │  ┌────────────────────────┐  │  │
│  │ ┌──────────────┐ │   POST /api/moods  │  │  MoodTracking.Api      │  │  │
│  │ │MoodDesktopApp│ │ ──────────────────►│  │  (ASP.NET Core 8.0)    │  │  │
│  │ │   (WPF)      │ │                    │  │                        │  │  │
│  │ │              │ │◄───────────────────│  │  - Windows Auth        │  │  │
│  │ │ ┌──────────┐ │ │   JSON Response    │  │  - Rate Limiting       │  │  │
│  │ │ │ SQLite   │ │ │                    │  │  - Request Logging     │  │  │
│  │ │ │ (Offline)│ │ │                    │  └──────────┬─────────────┘  │  │
│  │ │ └──────────┘ │ │                    │             │                │  │
│  │ └──────────────┘ │                    │             │ EF Core        │  │
│  └──────────────────┘                    │             ▼                │  │
│                                          │  ┌────────────────────────┐  │  │
│  ┌──────────────────┐                    │  │   SQL Server           │  │  │
│  │  Windows Client  │                    │  │   (On-Premise)         │  │  │
│  │  Workstation #N  │ ──────────────────►│  │                        │  │  │
│  └──────────────────┘                    │  │  - MoodEvents table    │  │  │
│                                          │  │  - Integrated Auth     │  │  │
│                                          │  └────────────────────────┘  │  │
│                                          └──────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Key Architectural Principles

1. **Separation of Concerns**: Desktop app NEVER connects directly to SQL Server
2. **Defense in Depth**: Multiple security layers (Windows Auth, HTTPS, rate limiting)
3. **Offline Resilience**: Local SQLite queue for network failures
4. **Minimal Footprint**: Desktop app is lightweight and non-intrusive
5. **Auditable**: All mood events include server-side timestamps

---

## 2. Technology Decisions & Justifications

### 2.1 Desktop Framework: WPF (Recommended)

| Framework | Pros | Cons | Verdict |
|-----------|------|------|---------|
| **WPF** | Mature, stable, excellent tooling, XAML data binding, works on all Windows 7+ | Not cross-platform | ✅ **RECOMMENDED** |
| WinUI 3 | Modern UI, Fluent Design | Requires Windows 10 1809+, less mature tooling, larger deployment size | ❌ Not for enterprise |
| WinForms | Simple, fast development | Limited styling, dated appearance | ❌ Too basic for modern UX |

**Decision: WPF (.NET 8.0)**

Justification:
- **Enterprise compatibility**: Works on Windows 7, 8, 10, 11
- **Mature ecosystem**: 15+ years of stability, extensive documentation
- **MVVM support**: Clean separation for testability
- **Small footprint**: Minimal runtime dependencies
- **Styling flexibility**: Can match corporate branding easily

### 2.2 Packaging: MSIX (Recommended)

| Option | Pros | Cons | Verdict |
|--------|------|------|---------|
| **MSIX** | Modern, clean install/uninstall, auto-update support, startup task capability | Requires signing certificate | ✅ **RECOMMENDED** |
| ClickOnce | Simple, auto-update | Legacy, limited control | ❌ |
| MSI (WiX) | Full control | Complex authoring | ⚠️ Alternative |

**Decision: MSIX with sideloading**

For internal enterprise deployment, MSIX can be sideloaded without Microsoft Store using:
- Self-signed or internal CA certificate
- Group Policy for trusted certificate deployment
- SCCM/Intune distribution

### 2.3 API Framework: ASP.NET Core 8.0

The only choice for modern .NET API development. Benefits:
- Cross-platform (though we'll host on Windows IIS)
- Excellent performance
- Built-in dependency injection
- Native Windows Authentication support
- OpenAPI/Swagger integration

### 2.4 Database: SQL Server (On-Premise)

As specified. Will use:
- **Entity Framework Core 8.0** for ORM
- **Windows Integrated Authentication** for connection (no SQL passwords in config)
- **Proper indexing** for query performance

### 2.5 Authentication: Windows Authentication (Recommended)

| Option | Complexity | Security | On-Prem Fit | Verdict |
|--------|------------|----------|-------------|---------|
| **Windows Auth** | Low | High | Excellent | ✅ **RECOMMENDED** |
| API Keys | Low | Medium | Good | ⚠️ Simple alternative |
| JWT + Identity | High | High | Overkill | ❌ For MVP |

**Decision: Windows Integrated Authentication**

Justification for on-premise internal deployment:
- **Zero credential management**: Uses existing AD credentials
- **SSO experience**: No additional login for users
- **Audit trail**: Full Windows user identity in logs
- **Simple deployment**: IIS native support

Migration path to JWT documented in security section if needed later.

---

## 3. Data Flow

### 3.1 Happy Path Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Windows   │    │   Desktop   │    │    API      │    │ SQL Server  │
│   Login     │    │    App      │    │   Server    │    │  Database   │
└──────┬──────┘    └──────┬──────┘    └──────┬──────┘    └──────┬──────┘
       │                  │                  │                  │
       │ User logs in     │                  │                  │
       ├─────────────────►│                  │                  │
       │                  │                  │                  │
       │                  │ Startup Task     │                  │
       │                  │ triggers app     │                  │
       │                  │                  │                  │
       │                  │ Show mood prompt │                  │
       │                  │◄────────────────►│                  │
       │                  │ User:CONTOSO\joe │                  │
       │                  │ Machine:WS-001   │                  │
       │                  │                  │                  │
       │  User clicks     │                  │                  │
       │  "Happy" ───────►│                  │                  │
       │                  │                  │                  │
       │                  │ POST /api/moods  │                  │
       │                  │ {mood:"Happy",...}                  │
       │                  ├─────────────────►│                  │
       │                  │ Windows Auth     │                  │
       │                  │ (Kerberos ticket)│                  │
       │                  │                  │                  │
       │                  │                  │ Validate payload │
       │                  │                  │ Add ServerUtc    │
       │                  │                  │                  │
       │                  │                  │ INSERT INTO      │
       │                  │                  │ MoodEvents       │
       │                  │                  ├─────────────────►│
       │                  │                  │                  │
       │                  │                  │◄─────────────────┤
       │                  │                  │  Success         │
       │                  │                  │                  │
       │                  │◄─────────────────┤                  │
       │                  │ 201 Created      │                  │
       │                  │ {id: "guid..."}  │                  │
       │                  │                  │                  │
       │◄─────────────────┤                  │                  │
       │ "Thanks! Have a  │                  │                  │
       │  great day!"     │                  │                  │
       │ [Window closes]  │                  │                  │
       │                  │                  │                  │
```

### 3.2 Offline/Retry Flow

```
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│   Desktop   │    │   SQLite    │    │    API      │    │  Sync       │
│    App      │    │   Queue     │    │   Server    │    │  Worker     │
└──────┬──────┘    └──────┬──────┘    └──────┬──────┘    └──────┬──────┘
       │                  │                  │                  │
       │ User clicks mood │                  │                  │
       ├─────────────────►│                  │                  │
       │                  │                  │                  │
       │ POST /api/moods  │                  │                  │
       │ ─────────────────┼──────────────────┤                  │
       │                  │        ✗ Network │                  │
       │                  │          Error   │                  │
       │◄─────────────────┼──────────────────┤                  │
       │                  │                  │                  │
       │ Save to local    │                  │                  │
       │ queue ──────────►│                  │                  │
       │                  │ Store pending    │                  │
       │                  │ event            │                  │
       │                  │                  │                  │
       │ Show "Saved      │                  │                  │
       │ offline" message │                  │                  │
       │                  │                  │                  │
       │ ═══════════════ LATER ═══════════════════════════════ │
       │                  │                  │                  │
       │                  │                  │                  │
       │                  │◄─────────────────┼──────────────────┤
       │                  │   Timer fires    │  Background sync │
       │                  │                  │  every 5 min     │
       │                  │                  │                  │
       │                  │ Read pending ────┼─────────────────►│
       │                  │                  │                  │
       │                  │                  │ POST /api/moods  │
       │                  │                  │─────────────────►│
       │                  │                  │                  │
       │                  │                  │◄─────────────────│
       │                  │                  │  201 Created     │
       │                  │                  │                  │
       │                  │◄─────────────────┼──────────────────┤
       │                  │ Mark as synced   │                  │
       │                  │ or delete        │                  │
       │                  │                  │                  │
```

---

## 4. Security Architecture

### 4.1 Security Layers

```
┌─────────────────────────────────────────────────────────────────┐
│                    SECURITY LAYERS                               │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Layer 1: Transport Security (HTTPS/TLS 1.2+)                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  - Internal CA-signed certificate                       │    │
│  │  - TLS 1.2 minimum enforced                             │    │
│  │  - HTTP redirects to HTTPS                              │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Layer 2: Authentication (Windows Integrated)                   │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  - Kerberos/NTLM negotiation                            │    │
│  │  - User identity from Windows token                      │    │
│  │  - No passwords transmitted                              │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Layer 3: Authorization (Policy-based)                          │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  - All authenticated users can POST moods               │    │
│  │  - Admin group required for GET /api/moods              │    │
│  │  - Role-based access control via AD groups              │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Layer 4: Rate Limiting & Abuse Protection                      │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  - 10 requests per user per minute (sliding window)     │    │
│  │  - 1 mood submission per user per hour (business rule)  │    │
│  │  - Request size limits                                   │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Layer 5: Input Validation                                       │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  - Mood enum validation (Happy/Unhappy/Sad only)        │    │
│  │  - String length limits                                  │    │
│  │  - Server-side timestamp (don't trust client)           │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
│  Layer 6: Audit Logging                                          │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │  - All API requests logged with user identity           │    │
│  │  - No sensitive data in logs                            │    │
│  │  - Windows Event Log integration                        │    │
│  └─────────────────────────────────────────────────────────┘    │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 4.2 Authentication Options Comparison

#### Option 1: Windows Authentication (RECOMMENDED for On-Prem)

**Pros:**
- Zero credential management
- Leverages existing Active Directory
- SSO experience
- Strong security (Kerberos)
- Full audit trail

**Cons:**
- Requires domain-joined machines
- Doesn't work outside corporate network without VPN

**Implementation:**
```csharp
// Program.cs
builder.Services.AddAuthentication(NegotiateDefaults.AuthenticationScheme)
    .AddNegotiate();

// IIS web.config
<authentication mode="Windows" />
```

#### Option 2: API Key (Simple Alternative)

**Pros:**
- Works anywhere
- Simple to implement
- No domain requirement

**Cons:**
- Key management overhead
- Keys can be leaked
- No user identity automatically

**Implementation:**
```csharp
// Header: X-API-Key: {department-key}
builder.Services.AddAuthentication("ApiKey")
    .AddScheme<ApiKeyAuthOptions, ApiKeyAuthHandler>("ApiKey", null);
```

#### Option 3: JWT with Local Identity (Future State)

**Pros:**
- Industry standard
- Works anywhere
- Fine-grained claims

**Cons:**
- Requires identity server setup
- More complex deployment
- Token management

**Migration Path:**
1. Start with Windows Auth (MVP)
2. Add JWT as secondary option
3. Phase out Windows Auth for external access

### 4.3 HTTPS Configuration

For internal on-premise deployment:

**Option A: Internal CA Certificate (Recommended)**
```
1. Request certificate from internal CA
2. Subject: moodtracker.contoso.local
3. SAN: moodtracker, moodtracker.contoso.local, 10.0.1.50
4. Install in IIS
5. Deploy CA root to clients via Group Policy
```

**Option B: Self-Signed (Dev/Test Only)**
```powershell
New-SelfSignedCertificate -DnsName "moodtracker.contoso.local" `
    -CertStoreLocation "cert:\LocalMachine\My" `
    -NotAfter (Get-Date).AddYears(5)
```

**Migration to Public HTTPS:**
If API needs external access later:
1. Obtain public certificate (Let's Encrypt or commercial)
2. Configure public DNS
3. Add JWT auth alongside Windows Auth
4. Firewall rules for HTTPS only

---

## 5. Database Design

### 5.1 Entity Relationship Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                      MoodEvents                              │
├─────────────────────────────────────────────────────────────┤
│ PK │ Id               │ UNIQUEIDENTIFIER │ NOT NULL         │
├────┼──────────────────┼──────────────────┼──────────────────┤
│    │ WindowsUsername  │ NVARCHAR(256)    │ NOT NULL         │
│    │ Domain           │ NVARCHAR(256)    │ NULL             │
│    │ MachineName      │ NVARCHAR(256)    │ NOT NULL         │
│    │ UserSid          │ NVARCHAR(256)    │ NULL             │
│    │ Mood             │ NVARCHAR(50)     │ NOT NULL         │
│    │ ClientTimestampUtc│ DATETIME2(7)    │ NOT NULL         │
│    │ ServerReceivedUtc │ DATETIME2(7)    │ NOT NULL         │
│    │ AppVersion       │ NVARCHAR(50)     │ NULL             │
│    │ OSVersion        │ NVARCHAR(256)    │ NULL             │
│    │ IPAddress        │ NVARCHAR(45)     │ NULL             │
│    │ Source           │ NVARCHAR(50)     │ NULL             │
│    │ RowVersion       │ ROWVERSION       │ NOT NULL         │
├─────────────────────────────────────────────────────────────┤
│ INDEXES:                                                     │
│ - IX_MoodEvents_WindowsUsername_ServerReceivedUtc           │
│ - IX_MoodEvents_ServerReceivedUtc                           │
│ - IX_MoodEvents_Mood                                         │
│ - IX_MoodEvents_MachineName                                  │
└─────────────────────────────────────────────────────────────┘
```

### 5.2 SQL Scripts

See `/database/scripts/` for complete SQL scripts:
- `01-CreateDatabase.sql`
- `02-CreateTables.sql`
- `03-CreateIndexes.sql`
- `04-SeedData.sql` (optional test data)

### 5.3 Data Retention

Recommended retention policy:
- **Active data**: Last 2 years in main table
- **Archive**: Move older data to `MoodEvents_Archive`
- **Purge**: Delete after 7 years (adjust per compliance)

---

## 6. API Contract

### 6.1 Endpoints Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/api/moods` | Submit mood event | Yes (Any user) |
| GET | `/api/moods` | Query mood events | Yes (Admin only) |
| GET | `/health` | Health check | No |
| GET | `/health/ready` | Readiness check | No |

### 6.2 POST /api/moods

**Request:**
```http
POST /api/moods HTTP/1.1
Host: moodtracker.contoso.local
Content-Type: application/json
Authorization: Negotiate {kerberos-token}

{
  "mood": "Happy",
  "windowsUsername": "jsmith",
  "domain": "CONTOSO",
  "machineName": "WS-DEV-001",
  "userSid": "S-1-5-21-...",
  "clientTimestampUtc": "2024-01-15T09:30:00.000Z",
  "appVersion": "1.0.0",
  "osVersion": "Microsoft Windows NT 10.0.22621.0"
}
```

**Response (Success - 201 Created):**
```http
HTTP/1.1 201 Created
Content-Type: application/json
Location: /api/moods/3fa85f64-5717-4562-b3fc-2c963f66afa6

{
  "id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
  "mood": "Happy",
  "serverReceivedUtc": "2024-01-15T09:30:01.123Z",
  "message": "Mood recorded successfully"
}
```

**Response (Validation Error - 400 Bad Request):**
```http
HTTP/1.1 400 Bad Request
Content-Type: application/problem+json

{
  "type": "https://tools.ietf.org/html/rfc7231#section-6.5.1",
  "title": "One or more validation errors occurred.",
  "status": 400,
  "traceId": "00-1234567890abcdef-abcdef123456-00",
  "errors": {
    "mood": ["The mood value 'Angry' is not valid. Must be one of: Happy, Unhappy, Sad"],
    "machineName": ["The MachineName field is required."]
  }
}
```

**Response (Rate Limited - 429 Too Many Requests):**
```http
HTTP/1.1 429 Too Many Requests
Content-Type: application/problem+json
Retry-After: 60

{
  "type": "https://tools.ietf.org/html/rfc6585#section-4",
  "title": "Too Many Requests",
  "status": 429,
  "detail": "Rate limit exceeded. Try again in 60 seconds."
}
```

### 6.3 GET /api/moods (Admin Only)

**Request:**
```http
GET /api/moods?startDate=2024-01-01&endDate=2024-01-31&mood=Happy&page=1&pageSize=50 HTTP/1.1
Host: moodtracker.contoso.local
Authorization: Negotiate {kerberos-token}
```

**Response (Success - 200 OK):**
```http
HTTP/1.1 200 OK
Content-Type: application/json

{
  "items": [
    {
      "id": "3fa85f64-5717-4562-b3fc-2c963f66afa6",
      "windowsUsername": "jsmith",
      "domain": "CONTOSO",
      "machineName": "WS-DEV-001",
      "mood": "Happy",
      "clientTimestampUtc": "2024-01-15T09:30:00.000Z",
      "serverReceivedUtc": "2024-01-15T09:30:01.123Z",
      "appVersion": "1.0.0"
    }
  ],
  "page": 1,
  "pageSize": 50,
  "totalCount": 1234,
  "totalPages": 25
}
```

### 6.4 Error Response Format

All errors follow RFC 7807 Problem Details:

```json
{
  "type": "string (URI reference)",
  "title": "string (short description)",
  "status": 0,
  "detail": "string (detailed explanation)",
  "instance": "string (URI of specific occurrence)",
  "traceId": "string (correlation ID)",
  "errors": {
    "fieldName": ["error message 1", "error message 2"]
  }
}
```

---

## 7. Solution Structure

```
HappinessMeter/
├── src/
│   ├── MoodDesktopApp/                    # WPF Desktop Application
│   │   ├── App.xaml                       # Application entry point
│   │   ├── App.xaml.cs
│   │   ├── MainWindow.xaml                # Mood selection UI
│   │   ├── MainWindow.xaml.cs
│   │   ├── ViewModels/
│   │   │   └── MainViewModel.cs           # MVVM ViewModel
│   │   ├── Services/
│   │   │   ├── IMoodApiClient.cs          # API client interface
│   │   │   ├── MoodApiClient.cs           # HTTP client implementation
│   │   │   ├── ISystemInfoService.cs      # System info interface
│   │   │   ├── SystemInfoService.cs       # Windows info collector
│   │   │   ├── IOfflineQueueService.cs    # Offline queue interface
│   │   │   └── OfflineQueueService.cs     # SQLite offline storage
│   │   ├── Workers/
│   │   │   └── SyncBackgroundService.cs   # Background sync worker
│   │   ├── Data/
│   │   │   ├── OfflineDbContext.cs        # SQLite EF Core context
│   │   │   └── PendingMoodEvent.cs        # Local queue entity
│   │   ├── Converters/
│   │   │   └── MoodToColorConverter.cs    # UI converters
│   │   ├── Assets/
│   │   │   └── Icons/                     # App icons
│   │   ├── MoodDesktopApp.csproj
│   │   └── Package.appxmanifest           # MSIX manifest
│   │
│   ├── MoodTracking.Api/                  # ASP.NET Core Web API
│   │   ├── Controllers/
│   │   │   ├── MoodsController.cs         # Mood endpoints
│   │   │   └── HealthController.cs        # Health endpoints
│   │   ├── Models/
│   │   │   ├── CreateMoodRequest.cs       # POST request DTO
│   │   │   ├── MoodResponse.cs            # Response DTO
│   │   │   └── PagedResult.cs             # Pagination wrapper
│   │   ├── Validators/
│   │   │   └── CreateMoodRequestValidator.cs  # FluentValidation
│   │   ├── Middleware/
│   │   │   ├── RequestLoggingMiddleware.cs
│   │   │   └── ExceptionHandlingMiddleware.cs
│   │   ├── Extensions/
│   │   │   └── ServiceCollectionExtensions.cs
│   │   ├── Program.cs                     # Application startup
│   │   ├── appsettings.json
│   │   ├── appsettings.Development.json
│   │   ├── appsettings.Production.json
│   │   ├── web.config                     # IIS configuration
│   │   └── MoodTracking.Api.csproj
│   │
│   ├── MoodTracking.Shared/               # Shared DTOs/Contracts
│   │   ├── DTOs/
│   │   │   ├── MoodEventDto.cs
│   │   │   └── MoodType.cs                # Enum: Happy/Unhappy/Sad
│   │   ├── Constants/
│   │   │   └── ApiConstants.cs
│   │   └── MoodTracking.Shared.csproj
│   │
│   └── MoodTracking.Infrastructure/       # Data Access Layer
│       ├── Data/
│       │   ├── MoodDbContext.cs           # EF Core DbContext
│       │   └── Configurations/
│       │       └── MoodEventConfiguration.cs
│       ├── Entities/
│       │   └── MoodEvent.cs               # Database entity
│       ├── Repositories/
│       │   ├── IMoodRepository.cs
│       │   └── MoodRepository.cs
│       └── MoodTracking.Infrastructure.csproj
│
├── tests/
│   ├── MoodTracking.Api.Tests/            # API unit tests
│   ├── MoodTracking.Api.IntegrationTests/ # API integration tests
│   └── MoodDesktopApp.Tests/              # Desktop app tests
│
├── database/
│   └── scripts/
│       ├── 01-CreateDatabase.sql
│       ├── 02-CreateTables.sql
│       ├── 03-CreateIndexes.sql
│       └── 04-SeedData.sql
│
├── deploy/
│   ├── iis/
│   │   ├── install-iis-features.ps1
│   │   └── configure-site.ps1
│   ├── desktop/
│   │   └── create-msix-package.ps1
│   └── sql/
│       └── backup-database.ps1
│
├── docs/
│   ├── ARCHITECTURE.md                    # This document
│   ├── DEPLOYMENT.md                      # Deployment guide
│   ├── API.md                             # API documentation
│   └── PRIVACY.md                         # Privacy policy
│
├── HappinessMeter.sln                     # Solution file
├── Directory.Build.props                  # Shared build properties
├── .editorconfig                          # Code style
└── README.md                              # Quick start guide
```

---

## 8. Deployment Strategy

### 8.1 API Deployment (IIS)

```
┌─────────────────────────────────────────────────────────────┐
│                    IIS Deployment                            │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Prerequisites:                                           │
│     - Windows Server 2019/2022                               │
│     - IIS with ASP.NET Core Hosting Bundle                   │
│     - SQL Server access                                      │
│     - SSL Certificate                                        │
│                                                              │
│  2. Application Pool:                                        │
│     - Name: MoodTrackingAppPool                              │
│     - .NET CLR Version: No Managed Code                      │
│     - Identity: Domain service account                       │
│     - Enable 32-bit: False                                   │
│                                                              │
│  3. Site Configuration:                                      │
│     - Physical Path: C:\inetpub\MoodTracking.Api            │
│     - Binding: https://moodtracker.contoso.local:443        │
│     - SSL Certificate: Internal CA cert                      │
│     - Authentication: Windows Authentication                 │
│                                                              │
│  4. Publish Command:                                         │
│     dotnet publish -c Release -o C:\inetpub\MoodTracking.Api│
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### 8.2 Desktop Deployment (MSIX)

```
┌─────────────────────────────────────────────────────────────┐
│                    MSIX Deployment                           │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  1. Build MSIX Package:                                      │
│     - Right-click project → Publish → Create App Package    │
│     - Select Sideloading                                     │
│     - Sign with code signing certificate                     │
│                                                              │
│  2. Distribution Options:                                    │
│     a) Network Share:                                        │
│        \\fileserver\apps\MoodDesktopApp\                    │
│                                                              │
│     b) SCCM/Intune:                                          │
│        Deploy as LOB app                                     │
│                                                              │
│     c) Group Policy:                                         │
│        Assign MSIX via GPO                                   │
│                                                              │
│  3. Auto-Update:                                             │
│     - Configure .appinstaller file                           │
│     - Point to network share                                 │
│     - Updates check on launch                                │
│                                                              │
│  4. Startup Registration:                                    │
│     - MSIX StartupTask in manifest                           │
│     - Runs at user login                                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## 9. Best Practices Checklist

### 9.1 Privacy Considerations

| Data Collected | Purpose | Minimization |
|----------------|---------|--------------|
| Windows Username | Identify user | Required |
| Domain | Identify user context | Required |
| Machine Name | Track where submitted | Required |
| User SID | Unique identifier | Optional |
| OS Version | Compatibility tracking | Optional |
| IP Address | Security/audit | Optional, logged server-side |
| Mood | Core feature | Required |
| Timestamp | Audit trail | Required |

**Privacy Notice Template:**
```
This application collects:
- Your Windows username and domain
- Your computer name
- The mood you select
- Timestamp of submission

This data is used to understand employee sentiment and is stored
securely on company servers. Data is retained for [X] years.
For questions, contact: privacy@company.com
```

### 9.2 Logging Strategy

**What to Log:**
- Request start/end with duration
- User identity (from Windows auth)
- Mood submitted (not sensitive)
- Errors with stack traces
- Performance metrics

**What NOT to Log:**
- Full request bodies (unnecessary)
- Passwords/tokens (none used)
- Sensitive PII beyond username

**Log Destinations:**
- Development: Console + Debug
- Production: Windows Event Log + Structured JSON files
- Optional: Seq, ELK, Application Insights

### 9.3 Versioning Strategy

**API Versioning:**
```
/api/v1/moods  (current)
/api/v2/moods  (future breaking changes)

Header: api-version: 1.0
```

**Desktop App Versioning:**
```
Major.Minor.Patch.Build
1.0.0.0  - Initial release
1.1.0.0  - New features
1.1.1.0  - Bug fixes
2.0.0.0  - Breaking changes
```

**Version Compatibility:**
- API maintains backward compatibility within major version
- Desktop app sends version in request
- API can reject outdated client versions

### 9.4 Performance Considerations

**Desktop App:**
- Cold start target: < 2 seconds
- Memory footprint: < 50 MB
- Minimize startup impact

**API:**
- Response time target: < 100ms (P95)
- Throughput: 1000+ requests/second
- Connection pooling for SQL Server

**Database:**
- Indexed queries only
- Pagination required for GET
- Archive old data quarterly

### 9.5 Monitoring & Alerting

**Health Checks:**
- `/health` - Basic liveness
- `/health/ready` - Database connectivity

**Metrics to Track:**
- Requests per minute
- Error rate
- Response time percentiles
- Database connection pool usage

**Alerts:**
- Error rate > 5%
- Response time P95 > 500ms
- Database connection failures
- Disk space < 20%

---

## Next Steps

1. Review and approve this architecture
2. Set up development environment
3. Create database on SQL Server
4. Implement API project
5. Implement Desktop app
6. Testing (unit, integration, UAT)
7. Deploy to staging
8. Security review
9. Production deployment
10. Monitor and iterate

---

*Document Version: 1.0.0*
*Last Updated: 2024*
*Author: Solution Architecture Team*
