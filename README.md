# Event Management SaaS

Multi-tenant event management platform (ticketing, RSVP, check-in, conferences, and more).

**Stack:** Laravel 11 (API) + Next.js 15 (frontend) + MySQL or SQLite.

---

## Table of contents

1. [Prerequisites](#1-prerequisites)
2. [First-time setup (local)](#2-first-time-setup-local)
3. [Run the app locally](#3-run-the-app-locally)
4. [Demo login & sample URLs](#4-demo-login--sample-urls)
5. [Optional: email testing (Mailpit)](#5-optional-email-testing-mailpit)
6. [Optional: Stripe payments](#6-optional-stripe-payments)
7. [Share on your office network (LAN)](#7-share-on-your-office-network-lan)
   - [Option A — Docker (recommended)](#option-a--docker-recommended)
   - [Option B — Manual (without Docker)](#option-b--manual-without-docker)
8. [Deploy on an internal server (always-on)](#8-deploy-on-an-internal-server-always-on)
9. [Troubleshooting](#9-troubleshooting)
10. [Further documentation](#10-further-documentation)

---

## 1. Prerequisites

Install these **before** cloning the repo.

| Tool | Version | Purpose |
|------|---------|---------|
| **PHP** | 8.3+ | Laravel backend |
| **Composer** | 2.x | PHP dependencies |
| **Node.js** | 20 LTS or newer | Next.js frontend |
| **npm** | 10+ (bundled with Node) | Frontend dependencies |
| **Git** | any recent | Clone the repository |

**PHP extensions** (enable in `php.ini` if missing):

`openssl`, `pdo`, `pdo_sqlite` (local) or `pdo_mysql` (server), `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `curl`

Verify:

```powershell
php -v
composer -V
node -v
npm -v
```

**For production / team server (recommended):**

| Tool | Version | Purpose |
|------|---------|---------|
| **MySQL** | 8.0+ | Production database |
| **Nginx** (or Apache) | any recent | Reverse proxy (optional but recommended) |

**Optional:**

- **Docker Desktop** — run the full stack with one command for [LAN sharing (Section 7)](#7-share-on-your-office-network-lan) without installing PHP/Node locally
- **Mailpit** — catch outbound email locally ([https://mailpit.axllent.org](https://mailpit.axllent.org)) — included automatically in the Docker stack
- **Stripe CLI** — test card payments locally ([https://stripe.com/docs/stripe-cli](https://stripe.com/docs/stripe-cli))

---

## 2. First-time setup (local)

Run every step in order. Do not skip steps.

### Step 2.1 — Clone the repository

```powershell
git clone <your-repo-url> event-saas
cd event-saas
```

Replace `<your-repo-url>` with your actual Git remote URL.

### Step 2.2 — Install backend dependencies

```powershell
composer install
```

### Step 2.3 — Create the backend environment file

```powershell
copy .env.example .env
```

On Linux/macOS:

```bash
cp .env.example .env
```

### Step 2.4 — Generate the application key

```powershell
php artisan key:generate
```

### Step 2.5 — Create the SQLite database file

The default `.env.example` uses SQLite (simplest for local dev).

**Windows (PowerShell):**

```powershell
New-Item -ItemType File -Force database\database.sqlite
```

**Linux / macOS:**

```bash
touch database/database.sqlite
```

### Step 2.6 — Run migrations and seed demo data

```powershell
php artisan migrate --seed
```

This creates all tables and loads demo organizations, events, tickets, orders, and guests.

> **Warning:** `php artisan migrate:fresh --seed` wipes the database completely. Use only when you want a clean slate.

### Step 2.7 — Link public storage (required for uploads)

Logos, hero images, and file uploads are served from `storage/app/public`.

```powershell
php artisan storage:link
```

### Step 2.8 — Install frontend dependencies

```powershell
cd frontend
npm install
cd ..
```

### Step 2.9 — Create the frontend environment file

```powershell
copy frontend\.env.local.example frontend\.env.local
```

On Linux/macOS:

```bash
cp frontend/.env.local.example frontend/.env.local
```

Default values point to `localhost` and work for local-only development:

```
NEXT_PUBLIC_API_URL=http://localhost:8001/api/v1
NEXT_PUBLIC_APP_URL=http://localhost:3000
NEXT_PUBLIC_SITE_URL=http://localhost:3000
```

First-time setup is complete.

---

## 3. Run the app locally

The app needs **three processes** running at the same time. Open **three separate terminal windows** (or tabs), all starting in the project root `event-saas`.

### Terminal 1 — Laravel API (port 8001)

```powershell
php artisan serve --port=8001
```

Verify: open [http://localhost:8001/api/v1/health](http://localhost:8001/api/v1/health) — you should see a JSON health response.

### Terminal 2 — Queue worker (emails, webhooks, exports, reminders)

```powershell
php artisan queue:work --tries=3
```

Keep this running. Without it, confirmation emails, RSVP reminders, and Stripe webhook jobs will not process.

### Terminal 3 — Next.js frontend (port 3000)

```powershell
cd frontend
npm run dev
```

Verify: open [http://localhost:3000](http://localhost:3000)

### Terminal 4 (optional) — Task scheduler

Some features run on a schedule (ticket reservation cleanup, certificate issuance, RSVP reminders).

**Option A — long-running scheduler (easiest for dev):**

```powershell
php artisan schedule:work
```

**Option B — run once manually when testing:**

```powershell
php artisan schedule:run
```

### Quick reference — local URLs

| Service | URL |
|---------|-----|
| Frontend (dashboard) | http://localhost:3000 |
| Backend API | http://localhost:8001/api/v1 |
| Health check | http://localhost:8001/api/v1/health |
| Login page | http://localhost:3000/login |

---

## 4. Demo login & sample URLs

After seeding, use these credentials:

| Field | Value |
|-------|-------|
| **Email** | `demo@event-saas.test` |
| **Password** | `password` |

The demo account owns **Summit Events Co** with 20 seeded events (ticketing, RSVP, conferences, check-in data, and more).

**Sample public event page** (after seeding):

http://localhost:3000/e/global-innovation-summit-1-1

---

## 5. Optional: email testing (Mailpit)

Outbound email (confirmations, invitations, reminders) uses SMTP settings in `.env`.

For local testing, run **Mailpit** and use these `.env` values:

```env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

- **Mailpit web UI:** http://localhost:8025 (view captured emails)
- **SMTP port:** 1025

Restart the queue worker after changing `.env`.

---

## 6. Optional: Stripe payments

Card checkout requires Stripe keys in the **backend** `.env`:

```env
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Get test keys from the [Stripe Dashboard](https://dashboard.stripe.com/test/apikeys).

### Forward webhooks locally

In a **fourth terminal**, run the Stripe CLI:

**Windows (if installed via winget):**

```powershell
& "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Stripe.StripeCli_Microsoft.Winget.Source_8wekyb3d8bbwe\stripe.exe" listen --forward-to localhost:8001/api/v1/webhooks/stripe
```

**macOS / Linux (if `stripe` is on your PATH):**

```bash
stripe listen --forward-to localhost:8001/api/v1/webhooks/stripe
```

Copy the `whsec_...` signing secret printed by the CLI into `STRIPE_WEBHOOK_SECRET` in `.env`, then restart the queue worker.

Use [Stripe test cards](https://docs.stripe.com/testing#cards) (e.g. `4242 4242 4242 4242`) on the public registration checkout flow.

---

## 7. Share on your office network (LAN)

Use this when **your PC** runs the app and colleagues on the same Wi‑Fi or office LAN need to open it in their browser to test.

**Recommended:** [Option A — Docker](#option-a--docker-recommended) starts everything in one command (API, frontend, queue, scheduler, MySQL, Mailpit).

---

### Option A — Docker (recommended)

Docker runs all services for you and binds ports on your PC so colleagues on the same network can connect.

#### What you need

| Tool | Notes |
|------|-------|
| **Docker Desktop** | [https://www.docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop) — Windows, macOS, or Linux |
| **Git** | To clone the repository |

You do **not** need PHP, Composer, or Node installed on your PC when using Docker.

#### Step 7A.1 — Clone the repository

```powershell
git clone <your-repo-url> event-saas
cd event-saas
```

#### Step 7A.2 — Create the Docker environment file

**Windows (automated — detects your LAN IP and generates APP_KEY):**

```powershell
.\docker\setup.ps1
```

**Manual (all platforms):**

```powershell
copy docker\.env.example .env.docker
```

On Linux/macOS:

```bash
cp docker/.env.example .env.docker
```

Open `.env.docker` and set **`LAN_HOST`** to your PC's LAN IP address.

**Find your IP:**

```powershell
ipconfig
```

Look for **IPv4 Address** (e.g. `192.168.1.42`).

Set in `.env.docker`:

```env
LAN_HOST=192.168.1.42
```

**Generate `APP_KEY`** (if empty) — PowerShell:

```powershell
$bytes = New-Object byte[] 32
[System.Security.Cryptography.RandomNumberGenerator]::Create().GetBytes($bytes)
"base64:$([Convert]::ToBase64String($bytes))"
```

Paste the output into `APP_KEY=` in `.env.docker`.

#### Step 7A.3 — Start the Docker stack

From the project root:

```powershell
docker compose --env-file .env.docker up -d --build
```

This starts:

| Container | Purpose | Port on your PC |
|-----------|---------|-----------------|
| **backend** | Laravel API | 8001 |
| **frontend** | Next.js app | 3000 |
| **queue** | Background jobs (emails, reminders) | — |
| **scheduler** | Scheduled tasks (RSVP reminders, etc.) | — |
| **mysql** | Database | 3306 |
| **mailpit** | Catches outbound email (optional to view) | 8025 (web UI) |

Wait until all containers are healthy:

```powershell
docker compose --env-file .env.docker ps
```

#### Step 7A.4 — Load demo data (first time only)

```powershell
docker compose --env-file .env.docker exec backend php artisan db:seed
```

Login after seeding: `demo@event-saas.test` / `password`

#### Step 7A.5 — Open Windows Firewall ports

Colleagues cannot connect if the firewall blocks ports **3000** and **8001**.

**Windows (PowerShell as Administrator):**

```powershell
New-NetFirewallRule -DisplayName "Event SaaS API" -Direction Inbound -LocalPort 8001 -Protocol TCP -Action Allow
New-NetFirewallRule -DisplayName "Event SaaS Frontend" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow
```

#### Step 7A.6 — Share the URL with colleagues

Replace `192.168.1.42` with your `LAN_HOST` value:

| Page | URL |
|------|-----|
| **App (login)** | http://192.168.1.42:3000 |
| **Login** | http://192.168.1.42:3000/login |
| **Mailpit (view emails)** | http://192.168.1.42:8025 |

Credentials: `demo@event-saas.test` / `password`

#### Step 7A.7 — Verify from another device

On a colleague's laptop or phone (same Wi‑Fi/LAN):

1. Open http://192.168.1.42:3000 — login page loads.
2. Log in — dashboard loads without API errors.
3. If the page loads but API calls fail, confirm `LAN_HOST` in `.env.docker` matches your PC's IP, then rebuild the frontend:

```powershell
docker compose --env-file .env.docker up -d --build frontend
```

#### Useful Docker commands

```powershell
# View logs
docker compose --env-file .env.docker logs -f

# Stop everything
docker compose --env-file .env.docker down

# Stop and delete database (fresh start)
docker compose --env-file .env.docker down -v

# Re-seed after wiping database
docker compose --env-file .env.docker exec backend php artisan migrate:fresh --seed

# Run Stripe webhooks on your PC (host terminal, not Docker)
& "$env:LOCALAPPDATA\Microsoft\WinGet\Packages\Stripe.StripeCli_Microsoft.Winget.Source_8wekyb3d8bbwe\stripe.exe" listen --forward-to localhost:8001/api/v1/webhooks/stripe
```

Add your Stripe keys to `.env.docker`, then restart backend and queue:

```powershell
docker compose --env-file .env.docker up -d backend queue
```

#### Docker LAN limitations

- Docker Desktop must be running and your PC must stay on.
- If your LAN IP changes (different Wi‑Fi), update `LAN_HOST` in `.env.docker` and run `docker compose --env-file .env.docker up -d --build`.
- Stripe webhooks from the internet still need Stripe CLI on your host forwarding to `localhost:8001`.
- For a permanent always-on server, see [Section 8](#8-deploy-on-an-internal-server-always-on).

---

### Option B — Manual (without Docker)

Use this if you prefer running PHP and Node directly on your PC (see [Section 3](#3-run-the-app-locally) for local setup first).

#### Step 7B.1 — Find your computer's LAN IP address

**Windows:**

```powershell
ipconfig
```

Look for **IPv4 Address** on your active adapter (Wi‑Fi or Ethernet), e.g. `192.168.1.42`.

**Linux / macOS:**

```bash
hostname -I
# or
ip addr show
```

Write down this IP. Examples below use `192.168.1.42` — replace with yours.

### Step 7B.2 — Update backend `.env`

Open `.env` in the project root and set:

```env
APP_URL=http://192.168.1.42:8001
FRONTEND_URL=http://192.168.1.42:3000
```

Save the file, then clear config cache:

```powershell
php artisan config:clear
```

### Step 7B.3 — Update frontend `frontend/.env.local`

```env
NEXT_PUBLIC_API_URL=http://192.168.1.42:8001/api/v1
NEXT_PUBLIC_APP_URL=http://192.168.1.42:3000
NEXT_PUBLIC_SITE_URL=http://192.168.1.42:3000
```

> **Important:** `NEXT_PUBLIC_*` values are baked in at build time for production builds. For `npm run dev`, restart the dev server after changing this file.

### Step 7B.4 — Bind servers to all network interfaces

Default `localhost` binding only accepts connections from your own machine. Bind to `0.0.0.0` so colleagues can connect.

**Terminal 1 — API:**

```powershell
php artisan serve --host=0.0.0.0 --port=8001
```

**Terminal 3 — Frontend:**

```powershell
cd frontend
npm run dev -- --hostname 0.0.0.0
```

Keep the queue worker (Terminal 2) running as usual.

### Step 7B.5 — Open firewall ports on the host machine

Colleagues cannot connect if the firewall blocks ports **3000** and **8001**.

**Windows (PowerShell as Administrator):**

```powershell
New-NetFirewallRule -DisplayName "Event SaaS API" -Direction Inbound -LocalPort 8001 -Protocol TCP -Action Allow
New-NetFirewallRule -DisplayName "Event SaaS Frontend" -Direction Inbound -LocalPort 3000 -Protocol TCP -Action Allow
```

**Linux (ufw example):**

```bash
sudo ufw allow 3000/tcp
sudo ufw allow 8001/tcp
sudo ufw reload
```

### Step 7B.6 — Share the URL with colleagues

Tell colleagues to open:

| Page | URL |
|------|-----|
| **App (login)** | http://192.168.1.42:3000 |
| **Login** | http://192.168.1.42:3000/login |

They use the same demo credentials: `demo@event-saas.test` / `password`.

### Step 7B.7 — Verify from another device

On a colleague's laptop or phone (same network):

1. Open http://192.168.1.42:3000 — the login page loads.
2. Log in — dashboard loads without API errors.
3. If the page loads but login fails with network errors, double-check `NEXT_PUBLIC_API_URL` matches your LAN IP and port `8001`.

### Manual LAN limitations

- Your computer must stay on and all three terminals running.
- Stripe webhooks from the internet cannot reach your laptop unless you use Stripe CLI or expose the machine publicly (not recommended).
- For a permanent team environment, use [Section 8](#8-deploy-on-an-internal-server-always-on) instead.

---

## 8. Deploy on an internal server (always-on)

Use this when a **dedicated machine** on your office network should host the app 24/7 for the team. These steps assume **Linux** (Ubuntu/Debian). Adapt package manager commands for your distro.

### Step 8.1 — Server prerequisites

On the server, install:

- PHP 8.3 + required extensions (including `pdo_mysql`)
- Composer
- Node.js 20 LTS
- MySQL 8
- Nginx (recommended)
- Git

Create a MySQL database and user:

```sql
CREATE DATABASE event_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'event_saas'@'localhost' IDENTIFIED BY 'choose-a-strong-password';
GRANT ALL PRIVILEGES ON event_saas.* TO 'event_saas'@'localhost';
FLUSH PRIVILEGES;
```

### Step 8.2 — Clone and install on the server

```bash
cd /var/www
sudo git clone <your-repo-url> event-saas
sudo chown -R $USER:www-data event-saas
cd event-saas

composer install --no-dev --optimize-autoloader
cd frontend && npm ci && cd ..
```

### Step 8.3 — Backend environment (production)

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` for production. Example (replace values):

```env
APP_NAME="Event SaaS"
APP_ENV=production
APP_DEBUG=false
APP_URL=http://192.168.1.100
FRONTEND_URL=http://192.168.1.100

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=event_saas
DB_USERNAME=event_saas
DB_PASSWORD=choose-a-strong-password

SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-user
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS=noreply@yourcompany.local
MAIL_FROM_NAME="${APP_NAME}"

STRIPE_PUBLISHABLE_KEY=pk_live_or_test_...
STRIPE_SECRET_KEY=sk_live_or_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Run migrations and seed (first deploy only):

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Optimize Laravel:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Set filesystem permissions:

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### Step 8.4 — Frontend environment and production build

Create `frontend/.env.local` (use your server's LAN IP or internal hostname):

```env
NEXT_PUBLIC_API_URL=http://192.168.1.100/api/v1
NEXT_PUBLIC_APP_URL=http://192.168.1.100
NEXT_PUBLIC_SITE_URL=http://192.168.1.100
```

Build and start:

```bash
cd frontend
npm run build
```

For a quick internal deploy without Nginx, you can run:

```bash
npm run start -- --hostname 0.0.0.0 --port 3000
```

For production, prefer Nginx (Step 8.6) proxying to `127.0.0.1:3000`.

### Step 8.5 — Long-running processes (systemd)

Create three systemd services so API, queue, and scheduler restart automatically.

**`/etc/systemd/system/event-saas-api.service`**

```ini
[Unit]
Description=Event SaaS Laravel API
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/event-saas
ExecStart=/usr/bin/php artisan serve --host=127.0.0.1 --port=8001
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

**`/etc/systemd/system/event-saas-queue.service`**

```ini
[Unit]
Description=Event SaaS Queue Worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/event-saas
ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

**`/etc/systemd/system/event-saas-scheduler.service`**

```ini
[Unit]
Description=Event SaaS Scheduler
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/event-saas
ExecStart=/usr/bin/php artisan schedule:work
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

**`/etc/systemd/system/event-saas-frontend.service`**

```ini
[Unit]
Description=Event SaaS Next.js Frontend
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/event-saas/frontend
Environment=NODE_ENV=production
ExecStart=/usr/bin/npm run start -- --hostname 127.0.0.1 --port 3000
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable event-saas-api event-saas-queue event-saas-scheduler event-saas-frontend
sudo systemctl start event-saas-api event-saas-queue event-saas-scheduler event-saas-frontend
sudo systemctl status event-saas-api event-saas-queue event-saas-scheduler event-saas-frontend
```

### Step 8.6 — Nginx reverse proxy (recommended)

Nginx serves everything on **port 80** so colleagues use `http://192.168.1.100` without port numbers.

**`/etc/nginx/sites-available/event-saas`**

```nginx
server {
    listen 80;
    server_name 192.168.1.100;   # or events.yourcompany.local

    client_max_body_size 20M;

    # Next.js frontend
    location / {
        proxy_pass http://127.0.0.1:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # Laravel API
    location /api/ {
        proxy_pass http://127.0.0.1:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Public storage (uploads)
    location /storage/ {
        alias /var/www/event-saas/storage/app/public/;
    }
}
```

If using Nginx on port 80, update `.env` and `frontend/.env.local` to drop port numbers:

```env
APP_URL=http://192.168.1.100
FRONTEND_URL=http://192.168.1.100
```

```env
NEXT_PUBLIC_API_URL=http://192.168.1.100/api/v1
NEXT_PUBLIC_APP_URL=http://192.168.1.100
NEXT_PUBLIC_SITE_URL=http://192.168.1.100
```

Then rebuild the frontend (`npm run build`) and reload services:

```bash
sudo ln -s /etc/nginx/sites-available/event-saas /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
php artisan config:cache
sudo systemctl restart event-saas-frontend
```

### Step 8.7 — Firewall on the server

```bash
sudo ufw allow 80/tcp
sudo ufw allow OpenSSH
sudo ufw enable
```

Ports 3000 and 8001 stay internal (`127.0.0.1` only) when Nginx is used.

### Step 8.8 — Tell colleagues how to access

| Item | Value |
|------|-------|
| **URL** | http://192.168.1.100 (or your internal hostname) |
| **Login** | `demo@event-saas.test` / `password` (change after go-live) |

### Step 8.9 — Deploying updates

When new code is merged:

```bash
cd /var/www/event-saas
git pull

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache

cd frontend
npm ci
npm run build

sudo systemctl restart event-saas-api event-saas-queue event-saas-frontend
```

> Never run `migrate:fresh` on a server with real data.

---

## 9. Troubleshooting

| Problem | Fix |
|---------|-----|
| **"No application encryption key"** | Run `php artisan key:generate` |
| **Database errors on first run** | Ensure `database/database.sqlite` exists (local) or MySQL credentials are correct (server), then `php artisan migrate` |
| **Uploads/images 404** | Run `php artisan storage:link` |
| **Emails never arrive** | Confirm queue worker is running; check Mailpit (local) or SMTP settings (server) |
| **Jobs stuck after `migrate:fresh`** | Stop and restart `php artisan queue:work` |
| **Frontend cannot reach API** | Check `NEXT_PUBLIC_API_URL` matches backend URL; restart `npm run dev` |
| **Colleagues cannot connect on LAN** | Bind to `0.0.0.0`, open firewall ports, use LAN IP not `localhost` |
| **Stripe payment stuck on "processing"** | Queue worker must run; webhook secret must match Stripe CLI or Dashboard endpoint |
| **403 / CORS on LAN** | Ensure `APP_URL` and `FRONTEND_URL` use the same host/IP colleagues use in the browser |

**Clear caches after `.env` changes:**

```powershell
php artisan config:clear
php artisan cache:clear
```

---

## 10. Further documentation

| Topic | Location |
|-------|----------|
| Product roadmap | `ai-development-kit/01_Project_Overview/ROADMAP.md` |
| API standards | `ai-development-kit/02_Backend/API_STANDARDS.md` |
| Tenancy | `ai-development-kit/02_Backend/TENANCY.md` |
| Security | `ai-development-kit/02_Backend/SECURITY.md` |
| Environment variables | `ai-development-kit/04_Deployment/ENVIRONMENT.md` |
| Production deployment notes | `ai-development-kit/04_Deployment/DEPLOYMENT.md` |
| Queue strategy | `QUEUE_STRATEGY.md` |

---

## License

MIT
