# Export Engine

A Laravel 8 (PHP 8) JSON API that ingests game/campaign telemetry — players,
events, transactions, answers, rewards — scoped under a **Version**, and produces
**asynchronous, configurable XLSX exports** of that data.

The client decides what each export contains: which sheets, which columns (including
fields inside the JSON `payload`), filters, sorting, aggregations and a time range.
Heavy exports run on a queue; the client polls for status and downloads the file when ready.

---

## Stack

- PHP 8 / Laravel 8
- MySQL 8 (data) + Redis (queue/cache)
- Queue worker for async export generation
- [openspout/openspout](https://github.com/openspout/openspout) for streamed XLSX writing
- PHPUnit
- Docker / Docker Compose

---

## Architecture at a glance

```
POST /versions/{v}/exports ──► ExportController ──► GenerateExportJob (queue)
                                                          │
                                                          ▼
                                              ExportWorkbookWriter (orchestrator)
                                                          │
                          ┌───────────────────────────────┼───────────────────────────┐
                          ▼                                ▼                            ▼
                  Metadata sheets                  Detail sheets                Summary sheets
              (README, Configurazione_         (players, events, …             (events_summary:
               Richiesta — auto-injected)       columns/filters/sort)           group_by/metrics)
```

- **Ingestion** is synchronous and bulk-inserted via the query builder for throughput.
- **Export generation** is asynchronous (Redis queue). Status lifecycle:
  `pending → processing → completed | failed`.
- Every export file always starts with the **metadata sheets** (`README`,
  `Configurazione_Richiesta`), followed by the **requested sheets**.
- Rows are streamed (`cursor()` + OpenSpout) to keep memory constant on large exports.

---

## Setup

```bash
# 1. Environment
cp .env.example .env

# 2. Build the images
docker compose build

# 3. Install PHP dependencies.
#    Run in a one-off container BEFORE starting the stack: the app/worker CMD
#    needs vendor/ to boot, and vendor/ is not committed. (--no-deps avoids
#    spinning up mysql/redis just for this.)
docker compose run --rm --no-deps app composer install

# 4. Start the stack (app, worker, mysql, redis)
docker compose up -d

# 5. App key
docker compose exec app php artisan key:generate

# 6. Migrate and seed demo data (one version with players/events/…)
docker compose exec app php artisan migrate --seed
```

The API is now available at **http://localhost:8000** (`APP_PORT`, default `8000`).

> **Note on the worker:** the `worker` container is a long-running queue worker that
> holds the code in memory. After changing any job/export code, restart it so queued
> jobs pick up the new code:
> ```bash
> docker compose restart worker      # or: docker compose exec app php artisan queue:restart
> ```

### Demo dataset

`migrate --seed` runs `DemoSeeder`, tunable via env vars (defaults shown):

| Var | Default | Meaning |
|-----|---------|---------|
| `DEMO_PLAYERS` | 200 | players created |
| `DEMO_EVENTS_PER_PLAYER` | 50 | events per player |
| `DEMO_TRANSACTIONS_PER_PLAYER` | 3 | transactions per player |
| `DEMO_ANSWERS_PER_PLAYER` | 2 | answers per player |
| `DEMO_REWARDS_PER_PLAYER` | 1 | rewards per player |

Re-seed from scratch:

```bash
docker compose exec app php artisan migrate:fresh --seed
```

---

## Running the tests

Tests use a **dedicated database** so they never touch your dev data (they run
`migrate:fresh`). Create it once:

```bash
docker compose exec mysql mysql -uroot -proot \
  -e "CREATE DATABASE IF NOT EXISTS export_engine_testing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
      GRANT ALL ON export_engine_testing.* TO 'laravel'@'%'; FLUSH PRIVILEGES;"
```

Then run the suite inside the container (where `DB_HOST=mysql` resolves):

```bash
docker compose exec app php artisan test
# single test class / filter:
docker compose exec app php artisan test --filter=ExportGenerationTest
```

`phpunit.xml` forces `QUEUE_CONNECTION=sync`, so dispatched jobs run inline during tests.

---

## API reference

All routes are under the `/api/v1` prefix. Ingestion is nested under a version;
export status/download are global by export id.

### Ingestion

| Method & path | Body | Notes |
|---|---|---|
| `POST /versions` | `{ "name": "..." }` | creates a version → `201 {id,name,...}` |
| `POST /versions/{v}/players` | `{ "items": [ {email?, registered_at?} ] }` | bulk insert → `201 {count, items}` |
| `POST /versions/{v}/events` | `{ "items": [ {player_id, type, occurred_at, payload?} ] }` | `201 {count}` |
| `POST /versions/{v}/events/stream` | NDJSON body, one event per line | streaming ingest → `201 {count, batches}` |
| `POST /versions/{v}/transactions` | `{ "items": [ {player_id, amount, currency, occurred_at, payload?} ] }` | `currency` = 3 letters |
| `POST /versions/{v}/answers` | `{ "items": [ {player_id, question, answer, occurred_at} ] }` | `201 {count}` |
| `POST /versions/{v}/rewards` | `{ "items": [ {player_id, name, value?, occurred_at} ] }` | `201 {count}` |

Child records (events/transactions/answers/rewards) must reference players that belong
to the **same version**, otherwise the request fails with `422`. Batch size is capped by
`INGESTION_MAX_ITEMS_PER_BATCH` (default `1000`).

### Export

| Method & path | Purpose |
|---|---|
| `POST /versions/{v}/exports` | request an export → `202 {id, status:"pending", ...}` |
| `GET /exports/{id}` | status + `download_url` (null until completed) |
| `GET /exports/{id}/download` | stream the file (`409` if not completed, `404` if missing) |

---

## Export request format

```jsonc
{
  "format": "xlsx",                 // optional, default "xlsx"
  "date_from": "2026-01-01",        // optional time range, applied to each sheet's time column
  "date_to": "2026-01-31",
  "sheets": [                        // required, one or more sheets
    {
      "name": "players",            // detail sheet
      "columns": ["player_id", "email", "registered_at"],
      "filters": { "email": "alice@example.test" },
      "sort": ["registered_at:desc"]
    },
    {
      "name": "events_summary",     // summary sheet
      "group_by": ["type", "payload.language"],
      "metrics": ["count", "unique_players"]
    }
  ]
}
```

The request is **fully validated up front** (`422` on any unknown sheet/column/metric, etc.).

### Available sheets

**Detail sheets** (one row per record) accept `columns`, `filters`, `sort`. Sheets with a
JSON `payload` also accept `payload.<path>` fields in any of those.

| Sheet | Columns | Time column | `payload.*` |
|---|---|---|---|
| `players` | `player_id, email, registered_at` | `registered_at` | – |
| `events` | `event_id, player_id, type, occurred_at` | `occurred_at` | ✅ |
| `transactions` | `transaction_id, player_id, amount, currency, occurred_at` | `occurred_at` | ✅ |
| `answers` | `answer_id, player_id, question, answer, occurred_at` | `occurred_at` | – |
| `rewards` | `reward_id, player_id, name, value, occurred_at` | `occurred_at` | – |

**Summary sheets** (one row per group) accept `group_by` + `metrics`.

| Sheet | `group_by` | `metrics` | Time column |
|---|---|---|---|
| `events_summary` | `type`, `payload.*` | `count`, `unique_players` | `occurred_at` |

- `filters` are equality matches (`{ "field": "value" }`).
- `sort` entries are `"column:asc"` or `"column:desc"` (default `asc`).
- The metadata sheets (`README`, `Configurazione_Richiesta`) are always added automatically.
