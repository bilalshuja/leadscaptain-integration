# Leadscaptain Enterprise Laravel Integration

High-throughput, resilient Laravel 12 integration package for the Leadscaptain API built according to Domain-Driven Design (Onion Architecture) principles.

## 🚀 Architectural Overview

```
                        [ Leadscaptain API ]
                                 ▲
                                 │ HTTP Pool (Adaptive Backoff & Rate Limit)
                                 ▼
┌─────────────────────────────────────────────────────────────────┐
│                    Infrastructure Layer                         │
│  LeadCaptainClient ◄──► Horizon Batched Jobs (FetchPageJob)    │
│  EloquentLeadRepository (MySQL Bulk Upsert: 500 records/chunk)  │
└────────────────────────────────┬────────────────────────────────┘
                                 ▲
                                 │
┌────────────────────────────────┴────────────────────────────────┐
│                     Application Layer                           │
│  LeadService ◄──► FetchAllLeadsUseCase ◄──► LeadData DTO        │
└────────────────────────────────┬────────────────────────────────┘
                                 ▲
                                 │
┌────────────────────────────────┴────────────────────────────────┐
│                        Domain Layer                             │
│  Lead Entity (Readonly, PHP 8.4) ◄──► LeadRepositoryInterface  │
│  Domain Event: LeadsFetched                                     │
└─────────────────────────────────────────────────────────────────┘
```

## 🛠 Local Development Setup

Prerequisites: **Docker** & **Docker Compose**.

```bash
# 1. Clone the repository
git clone [https://github.com/bilalshuja/leadscaptain-integration.git](https://github.com/bilalshuja/leadscaptain-integration.git)
cd leadscaptain-integration

# 2. Setup environment configuration
cp .env.example .env

# 3. Start Docker services (PHP 8.4, MySQL 8.0, Redis 7)
docker compose up -d

# 4. Install dependencies
docker compose exec app composer install

# 5. Generate application key
docker compose exec app php artisan key:generate

# 6. Run database migrations
docker compose exec app php artisan migrate
```

## ⚙️ Configuration Reference

Add these variables to your `.env` file to configure package behavior:

| Variable | Type | Default | Description |
| :--- | :---: | :---: | :--- |
| `LEADSCAPTAIN_BASE_URL` | string | `https://api.leadscaptain.com` | Base URL of the remote API |
| `LEADSCAPTAIN_API_KEY` | string | `null` | API authentication key |
| `LEADSCAPTAIN_TIMEOUT` | int | `15` | Request timeout in seconds |
| `LEADSCAPTAIN_RETRY_TIMES` | int | `3` | Maximum retry attempts for 429/5xx |
| `LEADSCAPTAIN_CONCURRENCY` | int | `10` | Concurrent request pool limit |
| `LEADSCAPTAIN_PER_PAGE` | int | `100` | Number of leads fetched per page |
| `LEADSCAPTAIN_RATE_LIMIT` | int | `60` | Webhook / API throttle per minute |

## 💻 Usage Examples

### 1. Artisan CLI Command
Run lead ingestion in background queue:
```bash
docker compose exec app php artisan leadscaptain:fetch-leads
```

### 2. Programmatic Job Dispatch
Trigger synchronization from your own code or scheduler:
```php
use Leadscaptain\Infrastructure\Jobs\FetchLeadsJob;

// Dispatches the master orchestrator to the dedicated Horizon queue
FetchLeadsJob::dispatch();
```

Or invoke the use case directly:
```php
use Leadscaptain\Application\UseCases\FetchAllLeadsUseCase;

app(FetchAllLeadsUseCase::class)->execute();
```

### 3. REST API & Webhook Endpoints

**Trigger Background Sync (Asynchronous):**
```bash
curl -X POST http://localhost:8000/api/leadscaptain/sync \
  -H "Accept: application/json"
```
*Response (202 Accepted):*
```json
{
  "status": "success",
  "message": "Sync queued successfully on [leads] queue."
}
```

**Real-Time Webhook Ingestion:**
```bash
curl -X POST http://localhost:8000/api/leadscaptain/webhook \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "event": "lead.created",
    "data": {
      "id": "lead_999",
      "email": "developer@leadscaptain.com",
      "name": "Jane Doe",
      "metadata": {"source": "adwords", "score": 85},
      "created_at": "2026-09-11T12:00:00+00:00"
    }
  }'
```

## 🧪 Testing Suite

Execute the test suite inside the container:
```bash
docker compose exec app php artisan test packages/leadscaptain/Tests
```