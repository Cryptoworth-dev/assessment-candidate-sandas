# Expense Tracker — Cryptoworth Assessment

## Overview

## Tech Stack
- Backend: Laravel 11 (PHP 8.4), REST JSON API
- Frontend: Angular (standalone, CSS)
- Database: MySQL 8.4
- Containerization: Docker Compose (api, db, web)

## Features

## Architecture

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

The `api` service automatically installs dependencies, generates the app key, runs migrations + seeders, and starts the Laravel dev server — no manual steps needed inside the container.

### Connecting to the database (optional)

To inspect data with a DB client (e.g. DBeaver, TablePlus):
- Host: `localhost`
- Port: `3306` (or `3307`, whichever is set in `docker-compose.yml`)
- Database: `expense_tracker`
- Username: `expense`
- Password: `secret`

> Note (MySQL 8 + JDBC clients): if you see "Public Key Retrieval is not allowed", add `allowPublicKeyRetrieval=true&useSSL=false` to the connection's driver properties/URL. This is a known MySQL 8 authentication-plugin quirk, not a config error.


### Prerequisites

### Backend
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

### Frontend
```bash
cd frontend
npm install
ng serve
```

## API Endpoints

## Testing

## Notes / Bonus Features