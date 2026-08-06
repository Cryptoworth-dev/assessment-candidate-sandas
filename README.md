# Expense Tracker — Cryptoworth Assessment

## Overview

A small full-stack application for recording personal expenses and viewing a
spending summary, built as a take-home technical assessment for Cryptoworth's
Junior Full-Stack Engineer role.

A user can add, list, edit and delete expenses, and see the total spend
overall and per category. Summary totals are always computed from the stored
expenses — nothing is stored pre-aggregated.

**Current status:** fully functional. Back-end API and front-end UI are both complete and wired together. Full CRUD for expenses, add/edit form with client and server-side validation, delete with confirmation, spending summary with per-category breakdown.

## Tech Stack
- Backend: Laravel 11 (PHP 8.4), REST JSON API
- Frontend: Angular 22 (standalone components, signals, reactive forms, CSS)
- Database: MySQL 8.4
- Containerization: Docker Compose (api, db, web)

## Features

- **Add/edit expense** — single reactive form that reuses for both create and edit (pre-fills on edit, shows "Update" vs. "Add" button accordingly).
- **List expenses** — table view with loading, error, and empty states; most recent first; actions per row (Edit, Delete).
- **Delete with confirmation** — modal dialog prevents accidental removal; shows "Deleting…" state during request.
- **Spending summary** — displays overall total and per-category totals with a proportional bar breakdown showing each category's share.
- **Validation** — client-side (required, length, min amount, date not in future) with inline error messages; server-side (422) validation errors displayed per field in the form.

## Architecture

```
.
├── backend/                 Laravel API
│   ├── app/
│   │   ├── Enums/ExpenseCategory.php
│   │   ├── Http/Controllers/Api/ExpenseController.php
│   │   ├── Http/Requests/          # StoreExpenseRequest, UpdateExpenseRequest
│   │   ├── Http/Resources/ExpenseResource.php
│   │   ├── Models/Expense.php
│   │   └── Services/ExpenseSummaryService.php
│   ├── database/migrations/
│   ├── database/factories/ExpenseFactory.php
│   └── database/seeders/ExpenseSeeder.php
├── frontend/                Angular application
│   ├── src/app/
│   │   ├── app.ts / app.html / app.css     # root shell — composes feature components & delete modal
│   │   ├── components/
│   │   │   ├── expense-list/               # table with loading/error/empty states
│   │   │   ├── expense-form/               # add/edit reactive form
│   │   │   └── expense-summary/            # totals & per-category breakdown with bar chart
│   │   ├── services/expense.ts             # ExpenseService — API calls & signals
│   │   ├── models/expense.ts               # Expense, InputExpense, Summary interfaces
│   │   └── environments/                   # apiUrl config
├── docker/php/Dockerfile
├── docker-compose.yml
└── README.md
```

**Layering, back-end:** controller → form request (validation) → model /
`ExpenseSummaryService` (aggregation) → `ExpenseResource` (response shape).
Each piece has one job — the controller stays thin, the summary calculation
is reusable and independently testable, and the API's JSON shape is decoupled
from the model's internal representation.

**Architecture, front-end:** The root `App` component owns cross-cutting state
(currently-editing expense, delete-confirmation modal state) and composes three
feature components (`ExpenseForm`, `ExpenseList`, `ExpenseSummary`) around a
single `ExpenseService`. The service holds reactive signals (`expenses`,
`summary`, `isLoading`, `loadError`) and orchestrates HTTP calls; components
subscribe to those signals and emit output events for user actions. Reactive
forms with client-side validation; server-side 422 errors displayed inline.

**Data model — `expenses`:**

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint unsigned` | Primary key |
| `description` | `varchar(255)` | Required, non-empty |
| `amount` | `decimal(12,2)` | Required, greater than zero |
| `category` | `varchar(50)` | Fixed enum, validated |
| `expense_date` | `date` | The date the expense occurred |
| `created_at` / `updated_at` | `timestamp` | Managed by Eloquent |

Indexed on `expense_date` (list ordering) and `category` (summary grouping).
Categories: `Food`, `Transport`, `Rent`, `Utilities`, `Entertainment`,
`Other` — a backed PHP enum in `app/Enums/ExpenseCategory.php`.

## Setup Instructions

### Prerequisites
- Docker Desktop installed and running

### Running with Docker (recommended)

1. Copy the environment file and set the DB password to match `docker-compose.yml`:
   ```bash
   cp backend/.env.example backend/.env
   # set DB_PASSWORD=secret in backend/.env
   ```

2. Start the stack:
   ```bash
   docker compose up --build
   ```

3. Once running:
   - API: http://localhost:8000/api
   - Frontend: http://localhost:4200

The `api` service automatically installs dependencies, generates the app key,
runs migrations + seeders, and starts the Laravel dev server — no manual
steps needed inside the container.

### Connecting to the database (optional)

To inspect data with a DB client (e.g. DBeaver, TablePlus):
- Host: `localhost`
- Port: `3306` (or `3307`, whichever is set in `docker-compose.yml`)
- Database: `expense_tracker`
- Username: `expense`
- Password: `secret`

> Note (MySQL 8 + JDBC clients): if you see "Public Key Retrieval is not
> allowed", add `allowPublicKeyRetrieval=true&useSSL=false` to the
> connection's driver properties/URL. This is a known MySQL 8
> authentication-plugin quirk, not a config error.

### Running without Docker

**Prerequisites:** PHP 8.4, Composer 2.x, MySQL 8.4, Node.js 22, npm 10.

Create an empty database first:
```bash
mysql -u root -p -e "CREATE DATABASE expense_tracker CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

#### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
```
Set `DB_HOST=127.0.0.1` and your local credentials in `backend/.env`, then:
```bash
php artisan migrate --seed
php artisan serve
```

#### Frontend
```bash
cd frontend
npm install
ng serve
```

The app is served at http://localhost:4200 and calls the API at
http://localhost:8000/api. CORS is configured in `backend/config/cors.php`
to allow `http://localhost:4200`; update `allowed_origins` there if you serve
the front-end from a different port.

## API Endpoints

Base URL: `http://localhost:8000/api`. Send `Accept: application/json` on
every request.

| Method | Endpoint | Description | Success |
|---|---|---|---|
| `GET` | `/expenses` | List all expenses, most recent first | `200` |
| `POST` | `/expenses` | Create an expense | `201` |
| `GET` | `/expenses/{id}` | Retrieve a single expense | `200` |
| `PUT` | `/expenses/{id}` | Replace an expense | `200` |
| `DELETE` | `/expenses/{id}` | Delete an expense | `204` |
| `GET` | `/expenses/summary` | Total and per-category spending | `200` |

**Expense payload:**
```json
{
  "description": "Weekly groceries",
  "amount": 12500.00,
  "category": "Food",
  "expense_date": "2026-08-01"
}
```

**Expense response:**
```json
{
  "data": {
    "id": 1,
    "description": "Weekly groceries",
    "amount": "12500.00",
    "category": "Food",
    "expense_date": "2026-08-01",
    "created_at": "2026-08-05T09:14:22+00:00",
    "updated_at": "2026-08-05T09:14:22+00:00"
  }
}
```

**Summary response:**
```json
{
  "data": {
    "total": "69900.00",
    "by_category": [
      { "category": "Rent", "total": "45000.00" },
      { "category": "Food", "total": "12500.00" },
      { "category": "Utilities", "total": "6800.00" }
    ]
  }
}
```

> Replace the figures above with the output of
> `curl http://localhost:8000/api/expenses/summary` once seeded, so the
> README matches the actual seeded data.

**Validation rules:**

| Field | Rules |
|---|---|
| `description` | required, string, max 255 characters |
| `amount` | required, numeric, greater than 0, max `9999999999.99` |
| `category` | required, must be one of the defined categories |
| `expense_date` | required, format `YYYY-MM-DD`, not in the future |

**Error responses:**

`422 Unprocessable Content` — validation failure:
```json
{
  "message": "The amount must be greater than zero.",
  "errors": {
    "amount": ["The amount must be greater than zero."],
    "category": ["The selected category is invalid."]
  }
}
```

`404 Not Found`:
```json
{ "message": "Resource not found." }
```

## Testing

**Back-end:** Pest tests not yet implemented. Planned: coverage of the summary
calculation (`ExpenseSummaryService`) and validation rules in the form requests.

**Front-end:** Vitest spec files exist for each component and service
(`app.spec.ts`, `expense-list.spec.ts`, `expense-form.spec.ts`,
`expense-summary.spec.ts`, `expense.spec.ts`), currently at Angular CLI
boilerplate (basic imports/renders, no meaningful assertions). Run via `npm test`
in the `frontend/` directory.

## Notes / Bonus Features

**Assumptions:**

1. **Categories are a fixed enum, not a database table.** The brief lists
   categories as examples rather than user-managed data, so a backed enum
   keeps the schema simple. A `categories` table would be the first change if
   users needed custom categories.
2. **Amounts are `decimal(12,2)`, serialised as strings**, not floats — no
   precision loss in JSON. The client parses the string for display
   arithmetic.
3. **Single currency**, no currency field — out of scope per the brief.
4. **Expenses cannot be dated in the future.**
5. **`PUT` replaces the whole resource**, so every field is required on
   update; `PATCH` semantics were not implemented.
6. **No authentication.** Expenses are global, matching the core scope.
7. **List ordering is `expense_date DESC, id DESC`** — the id tiebreaker
   keeps the order deterministic when expenses share a date.

**With more time:**

- Automated tests (Pest) around summary totals and validation.
- Filtering by category/date range, and pagination on the list endpoint.
- Soft deletes, so a removed expense can be recovered.
- A `categories` table if categories need to become user-managed.
- Structured logging / request-id header for traceability.

**Commit convention:** [Conventional Commits](https://www.conventionalcommits.org/) —
`type(scope): subject`, where `type` is one of `feat`, `fix`, `refactor`,
`test`, `docs`, `chore`, and `scope` is `api`, `web`, `db` or `docker`.