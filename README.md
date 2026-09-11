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

## 🛠 Tech Stack
- **PHP 8.4-FPM** with strict types and readonly classes
- **Laravel 12**
- **Laravel Horizon** on Redis 7
- **MySQL 8.0** with chunked idempotent bulk upserts
- **Roadrunner** application runtime
- **Docker Compose** containerized environment

## 📦 Key Implementation Features
- **Concurrent Ingestion:** Uses Laravel `Http::pool()` and dynamic chunking to maximize throughput without exceeding API rate limits.
- **Resilient Retry & Backoff:** Implements exponential backoff (1s, 2s, 4s) targeting HTTP 429 and 5xx responses.
- **Idempotency:** Unique indexing on `external_id` combined with bulk `ON DUPLICATE KEY UPDATE` queries eliminates duplicate entries.
- **Zero Event-Loop Blocking:** CLI (`php artisan leadscaptain:fetch-leads`) and REST trigger (`POST /api/leadscaptain/sync`) dispatch asynchronously to Horizon's dedicated `leads` queue.

## ⚡ Quickstart

```bash
# 1. Spin up the containers
docker compose up -d

# 2. Run database migrations
docker compose exec app php artisan migrate

# 3. Execute the comprehensive test suite
docker compose exec app php artisan test

# 4. Trigger lead ingestion via CLI
docker compose exec app php artisan leadscaptain:fetch-leads
```

## 🧪 Testing Suite
Run all unit and integration tests:
```bash
docker compose exec app php artisan test packages/leadscaptain/Tests
```