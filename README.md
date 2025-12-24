# HappinessMeter - Employee Mood Tracking System

A Windows desktop mood-tracking solution for enterprise environments, consisting of a WPF desktop application and an ASP.NET Core Web API with SQL Server backend.

## Architecture

```
┌─────────────────┐     HTTPS      ┌─────────────────┐     SQL      ┌─────────────────┐
│  Desktop App    │ ──────────────►│  ASP.NET Core   │ ───────────► │   SQL Server    │
│  (WPF/.NET 8)   │   Windows Auth │  Web API        │  Int. Auth   │   (On-Premise)  │
└─────────────────┘                └─────────────────┘              └─────────────────┘
```

## Key Features

- **Minimal UI**: Quick mood selection (Happy/Unhappy/Sad) at Windows login
- **Offline Support**: SQLite queue for network failures, auto-sync when online
- **Windows Authentication**: SSO experience with Active Directory
- **Secure**: HTTPS, rate limiting, no database credentials on clients
- **Lightweight**: Fast startup, minimal resource usage

## Projects

| Project | Description |
|---------|-------------|
| `MoodDesktopApp` | WPF desktop application (.NET 8) |
| `MoodTracking.Api` | ASP.NET Core 8 Web API |
| `MoodTracking.Shared` | Shared DTOs and contracts |
| `MoodTracking.Infrastructure` | EF Core data access layer |

## Quick Start

### Prerequisites

- .NET 8.0 SDK
- SQL Server 2019+ (LocalDB for development)
- Visual Studio 2022 or VS Code

### Development Setup

```bash
# Clone repository
git clone https://github.com/yourorg/happinessmeter.git
cd happinessmeter

# Restore packages
dotnet restore

# Create database (LocalDB)
sqlcmd -S "(localdb)\MSSQLLocalDB" -i database/scripts/01-CreateDatabase.sql
sqlcmd -S "(localdb)\MSSQLLocalDB" -i database/scripts/02-CreateTables.sql
sqlcmd -S "(localdb)\MSSQLLocalDB" -i database/scripts/03-CreateIndexes.sql

# Run API
cd src/MoodTracking.Api
dotnet run

# Run Desktop App (in another terminal)
cd src/MoodDesktopApp
dotnet run
```

### Configuration

**API** (`src/MoodTracking.Api/appsettings.Development.json`):
```json
{
  "ConnectionStrings": {
    "MoodTrackingDb": "Server=(localdb)\\MSSQLLocalDB;Database=MoodTracking_Dev;Integrated Security=true"
  }
}
```

**Desktop App** (`src/MoodDesktopApp/appsettings.json`):
```json
{
  "Api": {
    "BaseUrl": "https://localhost:5001"
  }
}
```

## API Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/moods` | Submit mood event | Required |
| GET | `/api/moods` | Query moods (paginated) | Admin only |
| GET | `/api/moods/{id}` | Get specific mood | Admin only |
| GET | `/api/moods/stats` | Get mood statistics | Admin only |
| GET | `/health` | Health check | None |

### Example Request

```http
POST /api/moods HTTP/1.1
Content-Type: application/json
Authorization: Negotiate {token}

{
  "mood": "Happy",
  "windowsUsername": "jsmith",
  "domain": "CONTOSO",
  "machineName": "WS-001",
  "clientTimestampUtc": "2024-01-15T09:30:00Z",
  "appVersion": "1.0.0.0"
}
```

## Documentation

- [Architecture Design](docs/ARCHITECTURE.md) - Complete system design
- [Deployment Guide](docs/DEPLOYMENT.md) - IIS and MSIX deployment
- [API Reference](docs/API.md) - API contract details

## Technology Stack

- **.NET 8.0** - Latest LTS framework
- **WPF** - Windows desktop UI
- **ASP.NET Core** - Web API
- **Entity Framework Core** - ORM
- **SQL Server** - Database
- **SQLite** - Offline queue
- **MSIX** - Desktop packaging

## Security

- Windows Integrated Authentication (Kerberos/NTLM)
- HTTPS with internal CA certificates
- Rate limiting per user
- Input validation
- No database credentials on clients
- Audit logging

## License

Proprietary - Your Organization

## Support

Contact: it-support@yourorganization.com
