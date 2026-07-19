# CMS V6 — Complete Setup Guide
## From Zero to Running in 30 Minutes

**Version:** 6.1 (includes all code review fixes)
**Platform:** Laravel 12, PHP 8.2+, MySQL/PostgreSQL, Redis
**Audience:** Developers setting up the CMS for the first time

---

## Table of Contents

1. [Prerequisites Checklist](#1-prerequisites-checklist)
2. [Step-by-Step Installation](#2-step-by-step-installation)
3. [Environment Configuration](#3-environment-configuration)
4. [Database Setup](#4-database-setup)
5. [Redis Setup](#5-redis-setup)
6. [Running the CMS](#6-running-the-cms)
7. [Test Tenants & Login](#7-test-tenants--login)
8. [V4 Feature Setup](#8-v4-feature-setup)
9. [Production Deployment](#9-production-deployment)
10. [Troubleshooting](#10-troubleshooting)
11. [Daily Operations](#11-daily-operations)

---

## 1. Prerequisites Checklist

### Required Software

| Software | Version | Check Command | Install (Ubuntu) |
|---|---|---|---|
| PHP | 8.2+ | `php -v` | `sudo apt install php8.2 php8.2-cli php8.2-fpm` |
| Composer | 2.x | `composer -V` | `curl -sS https://getcomposer.org/installer \| php && sudo mv composer.phar /usr/local/bin/composer` |
| MySQL | 8.0+ | `mysql --version` | `sudo apt install mysql-server` |
| OR PostgreSQL | 16+ | `psql --version` | `sudo apt install postgresql postgresql-contrib` |
| Redis | 6+ | `redis-cli --version` | `sudo apt install redis-server` |
| Node.js | 18+ | `node -v` | `curl -fsSL https://deb.nodesource.com/setup_18.x \| sudo -E bash - && sudo apt install nodejs` |
| npm | 9+ | `npm -v` | (comes with Node.js) |
| Git | 2.x | `git --version` | `sudo apt install git` |
| Zip | any | `zip --version` | `sudo apt install zip` |

### Required PHP Extensions

```bash
# Check installed extensions
php -m
```

**Must have:**
- `pdo`, `pdo_mysql` (or `pdo_pgsql`)
- `mbstring`, `xml`, `ctype`, `json`, `tokenizer`
- `bcmath`, `openssl`, `curl`, `fileinfo`
- `gd` (for image manipulation)
- `zip` (for backups, theme uploads)
- `redis` (for Redis cache/queue)

**Install missing extensions (Ubuntu):**
```bash
sudo apt install php8.2-mysql php8.2-pgsql php8.2-redis php8.2-gd php8.2-zip \
                 php8.2-curl php8.2-mbstring php8.2-xml php8.2-bcmath php8.2-intl
# Restart PHP-FPM
sudo systemctl restart php8.2-fpm
```

### Optional (for V4 features)

| Software | Purpose | When Needed |
|---|---|---|
| MaxMind GeoLite2 DB | Personalization geo-targeting | If using geo-based segments |
| Cloudflare API token | Wildcard SSL (DNS-01) | If using `*.example.com` domains |
| AWS Route53 keys | Wildcard SSL (DNS-01) | Alternative to Cloudflare |
| OpenAI API key | AI RAG, content generation | If enabling AI features |
| Let's Encrypt | Automated SSL | V4 multi-domain SSL |

---

## 2. Step-by-Step Installation

### Step 1: Get the project

```bash
# If you have the zip
unzip laravel-cms-v4.zip
cd laravel-cms-v4

# Or if cloning from git
git clone <your-repo-url> laravel-cms-v4
cd laravel-cms-v4
```

### Step 2: Install PHP dependencies

```bash
composer install
```

**If you get memory limit errors:**
```bash
COMPOSER_MEMORY_LIMIT=-1 composer install
```

**If you get ext-missing errors:**
```bash
# Check what's missing
composer check-platform-reqs

# Install missing extensions (see Prerequisites table above)
```

### Step 3: Install JavaScript dependencies

```bash
npm install
```

### Step 4: Create environment file

```bash
cp .env.example .env
php artisan key:generate
```

Verify the key was set:
```bash
grep APP_KEY .env
# Should show: APP_KEY=base64:xxxxxx...
```

### Step 5: Create the database

**MySQL:**
```bash
mysql -u root -p
```
```sql
CREATE DATABASE laravel_cms_v6 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cms_user'@'localhost' IDENTIFIED BY 'your_strong_password';
GRANT ALL ON laravel_cms_v6.* TO 'cms_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

**PostgreSQL (for pgvector/RAG):**
```bash
sudo -u postgres psql
```
```sql
CREATE DATABASE laravel_cms_v6;
CREATE USER cms_user WITH PASSWORD 'your_strong_password';
GRANT ALL ON DATABASE laravel_cms_v6 TO cms_user;
\c laravel_cms_v6
CREATE EXTENSION IF NOT EXISTS vector;
\q
```

### Step 6: Configure `.env`

Open `.env` in your editor and set these critical values:

```env
APP_NAME="Laravel CMS V6"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://platform.test
APP_CENTRAL_DOMAIN=platform.test

# Database
DB_CONNECTION=mysql          # or pgsql
DB_HOST=127.0.0.1
DB_PORT=3306                 # or 5432 for pgsql
DB_DATABASE=laravel_cms_v6
DB_USERNAME=cms_user
DB_PASSWORD=your_strong_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session, Cache, Queue (use Redis)
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
FILESYSTEM_DISK=local

# V6: Dual ID Support
CMS_ID_TYPE=uuid_v7           # uuid_v7 | uuid_v4 | bigint
CMS_MACHINE_ID=1              # 0-1023 (for snowflake bigint)

# V4: AI (optional — get key from https://platform.openai.com/api-keys)
AI_PROVIDER=openai
OPENAI_API_KEY=sk-your-key-here
OPENAI_MODEL=gpt-4o

# V4: Reverb (collaborative editing)
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=
REVERB_APP_KEY=
REVERB_APP_SECRET=
```

### Step 7: Generate Reverb keys

```bash
php artisan reverb:install
```

This auto-fills `REVERB_APP_ID`, `REVERB_APP_KEY`, `REVERB_APP_SECRET` in `.env`.

### Step 8: Run migrations

```bash
php artisan migrate
```

**Expected output:**
```
✓ migrations table created
✓ 8 central migrations ran (tenants, domains, users, billing, themes, ssl, etc.)
✓ 10 tenant migrations ran (entries, collections, blueprints, workflows, etc.)
✓ ~77 tables created total
```

**If you get errors:**
- `SQLSTATE[HY000] [2002] Connection refused` → MySQL isn't running: `sudo systemctl start mysql`
- `Access denied for user` → Check DB_USERNAME/DB_PASSWORD in `.env`
- `Unknown database` → You skipped Step 5, create the database first

### Step 9: Run seeders

```bash
php artisan db:seed
```

**This creates:**
- 3 billing plans (Standard $29, Pro $99, Enterprise $299)
- 5 test tenants (AdvMedi, BitDreamIT, Shopland, EnterpriseCorp, Multilingual Co.)
- 1 platform super admin
- 1 admin user per tenant
- Roles & permissions per tenant
- Sample collections, entries, navigation, forms, globals per tenant
- Foundation theme registered

### Step 10: Create storage symlink

```bash
php artisan storage:link
```

### Step 11: Configure local hosts file

**Linux/Mac:** Edit `/etc/hosts`:
```bash
sudo nano /etc/hosts
```

Add these lines:
```
127.0.0.1   platform.test
127.0.0.1   advmedi.test
127.0.0.1   shop.advmedi.test
127.0.0.1   blog.advmedi.test
127.0.0.1   bitdreamit.test
127.0.0.1   shopland.test
127.0.0.1   enterprise.test
127.0.0.1   multilingual.fr
127.0.0.1   multilingual.de
127.0.0.1   multilingual.bn
127.0.0.1   paris.multilingual.test
127.0.0.1   berlin.multilingual.test
```

**Windows:** Edit `C:\Windows\System32\drivers\etc\hosts` (as Administrator) with the same lines.

### Step 12: Clear caches (important!)

```bash
php artisan optimize:clear
```

---

## 3. Environment Configuration

### Minimal `.env` (for local dev)

```env
APP_NAME="Laravel CMS V6"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://platform.test
APP_CENTRAL_DOMAIN=platform.test
APP_KEY=base64:xxxxx

CMS_ID_TYPE=uuid_v7
CMS_MACHINE_ID=1

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_cms_v6
DB_USERNAME=cms_user
DB_PASSWORD=your_password

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
FILESYSTEM_DISK=local

LOG_CHANNEL=stack
LOG_LEVEL=debug
```

### Full `.env` (with all V4 features)

```env
# App
APP_NAME="Laravel CMS V6"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://platform.test
APP_CENTRAL_DOMAIN=platform.test

# V6: Dual ID
CMS_ID_TYPE=uuid_v7
CMS_MACHINE_ID=1

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=laravel_cms_v6
DB_USERNAME=cms_user
DB_PASSWORD=your_password

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session/Cache/Queue
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis
FILESYSTEM_DISK=local

# V4: SSL Automation
SSL_PROVIDER=letsencrypt
SSL_ENV=staging
SSL_RELOAD_CMD="sudo systemctl reload nginx"
# DNS provider for wildcard certs (pick one):
# CLOUDFLARE_API_TOKEN=your-token
# AWS_ACCESS_KEY_ID=your-key
# AWS_SECRET_ACCESS_KEY=your-secret
# DIGITALOCEAN_API_TOKEN=your-token

# V4: AI
AI_PROVIDER=openai
OPENAI_API_KEY=sk-your-key
OPENAI_MODEL=gpt-4o
OPENAI_EMBEDDING_MODEL=text-embedding-3-small

# V4: RAG
RAG_ENABLED=true
RAG_VECTOR_STORE=json

# V4: Reverb (collab editing)
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http
REVERB_APP_ID=your-id
REVERB_APP_KEY=your-key
REVERB_APP_SECRET=your-secret

# V4: SAML (optional)
SAML_ENABLED=false

# V4: SCIM (optional)
SCIM_ENABLED=false

# V4: Connector (optional)
CMS_BASE_URL=
CMS_TENANT_ID=
CMS_API_TOKEN=
CMS_SHARED_SECRET=
```

---

## 4. Database Setup

### Verify the database connection

```bash
php artisan db:show
```

Should show:
```
Database name ............ laravel_cms_v6
Configuration ............ mysql
Host ..................... 127.0.0.1
Port ..................... 3306
Tables ................... 77
```

### Check all migrations ran

```bash
php artisan migrate:status
```

All rows should show "Ran" (green checkmark). If any show "Pending":

```bash
php artisan migrate
```

### If you need to reset everything

```bash
# Drop all tables and re-run migrations + seeders
php artisan migrate:fresh --seed

# Just re-run seeders
php artisan db:seed --force
```

---

## 5. Redis Setup

### Verify Redis is running

```bash
redis-cli ping
# Should return: PONG
```

### If Redis isn't running

```bash
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### Test the Redis connection from Laravel

```bash
php artisan tinker
>>> Redis::ping()
# Should return: "+PONG"
>>> exit
```

---

## 6. Running the CMS

### Quick start (recommended)

Create a `start.sh` script:

```bash
cat > start.sh << 'EOF'
#!/bin/bash
echo "Starting CMS V6 services..."

# Kill any existing processes
pkill -f "artisan serve" 2>/dev/null
pkill -f "artisan queue:work" 2>/dev/null
pkill -f "artisan reverb:start" 2>/dev/null

sleep 1

# Start web server
php artisan serve --host=0.0.0.0 --port=8000 &
echo "✓ Web server started on :8000"

# Start queue worker
php artisan queue:work --queue=default,cms-sync,cms-events,audit-streaming --tries=3 &
echo "✓ Queue worker started"

# Start Reverb WebSocket server (for collab editing)
php artisan reverb:start --host=127.0.0.1 --port=8080 &
echo "✓ Reverb WebSocket server started on :8080"

echo ""
echo "=== CMS V6 is running ==="
echo "  Central:    http://platform.test:8000"
echo "  AdvMedi:    http://advmedi.test:8000"
echo "  Admin:      http://advmedi.test:8000/admin"
echo "  Login:      admin@advmedi.test / password"
echo ""
echo "Press Ctrl+C to stop all services"
wait
EOF

chmod +x start.sh
./start.sh
```

### Manual start (4 terminals)

**Terminal 1 — Web server:**
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

**Terminal 2 — Queue worker:**
```bash
php artisan queue:work --queue=default,cms-sync,cms-events,audit-streaming --tries=3
```

**Terminal 3 — Reverb (WebSocket):**
```bash
php artisan reverb:start --host=127.0.0.1 --port=8080
```

**Terminal 4 — (optional) Vite dev server:**
```bash
npm run dev
```

### Verify it's running

| URL | Expected |
|---|---|
| `http://platform.test:8000` | Welcome page listing all tenants |
| `http://platform.test:8000/up` | "OK" health check |
| `http://platform.test:8000/health` | JSON health check with DB/Redis/Queue status |
| `http://advmedi.test:8000` | AdvMedi tenant home page |
| `http://advmedi.test:8000/admin` | Admin login page |

---

## 7. Test Tenants & Login

### Platform Super Admin

| | |
|---|---|
| **URL** | `http://platform.test:8000/admin` |
| **Email** | `admin@platform.test` |
| **Password** | `password` |

### Tenant Admins

| Tenant | URL | Email | Password | V4 Features |
|---|---|---|---|---|
| AdvMedi | `advmedi.test:8000/admin` | `admin@advmedi.test` | `password` | multi_domain, workflow, rag, personalization |
| BitDreamIT | `bitdreamit.test:8000/admin` | `admin@bitdreamit.test` | `password` | multi_domain |
| Shopland | `shopland.test:8000/admin` | `admin@shopland.test` | `password` | connector, multi_domain |
| EnterpriseCorp | `enterprise.test:8000/admin` | `admin@enterprisecorp.test` | `password` | saml_sso, scim, audit_streaming, workflow |
| Multilingual Co. | `multilingual.fr:8000/admin` | `admin@multilingual.test` | `password` | multi_domain, rag, personalization |

### First login checklist

1. ✅ Go to `http://platform.test:8000` — see welcome page
2. ✅ Go to `http://advmedi.test:8000/admin` — see login form
3. ✅ Login with `admin@advmedi.test` / `password`
4. ✅ See dashboard with stats
5. ✅ Click "Feature Flags" in sidebar — see V4 features enabled for AdvMedi
6. ✅ Click "Entries" — see 5 sample entries
7. ✅ Click "Domains" — see advmedi.test, shop.advmedi.test, blog.advmedi.test

---

## 8. V4 Feature Setup

### Enable V4 features for a tenant

1. Login as tenant admin (Owner role)
2. Go to `/admin/feature-flags`
3. Toggle features on/off
4. Click "Save Feature Flags"

Available features:
- `multi_domain` — Per-domain theme/locale/collection routing
- `workflow_engine` — Content approval workflows
- `ab_testing` — A/B variant testing
- `collab_editing` — Real-time co-editing (requires Reverb)
- `ai_rag` — AI Q&A over published content
- `personalization` — Visitor segments + rules
- `saml_sso` — SAML 2.0 SSO
- `scim_provisioning` — SCIM 2.0 user provisioning
- `audit_streaming` — Activity log to SIEM
- `form_analytics` — Form conversion + lead scoring
- `connector` — External Laravel app connector

### Multi-Domain setup

1. Login to AdvMedi admin
2. Go to `/admin/domains`
3. Edit `shop.advmedi.test`
4. Set **Routing → default_collection_handle** = `products`
5. Save
6. Visit `http://shop.advmedi.test:8000/` — shows products collection

### RAG (AI Q&A) setup

1. Ensure `OPENAI_API_KEY` is set in `.env`
2. Enable `ai_rag` feature flag for the tenant
3. Publish some entries (RAG only indexes published entries)
4. Wait 30 seconds for indexing (or run `php artisan rag:reindex-stale`)
5. Go to `/admin/rag/playground`
6. Ask: "What services does this company offer?"

### Workflow setup

1. Enable `workflow_engine` feature flag
2. Go to `/admin/workflows` → "New Workflow"
3. Name: "Blog Approval"
4. Trigger: `entry.submitted_for_review`
5. Trigger Collections: `blog`
6. Build: Start → Editor Review → Admin Review → Publish Entry
7. Save + Activate

### Collab editing setup

1. Ensure Reverb is running: `php artisan reverb:start`
2. Enable `collab_editing` feature flag
3. Edit an entry with a Bard field
4. Open the same entry in another browser — see real-time cursors

### SAML SSO setup

1. Enable `saml_sso` feature flag
2. Go to `/admin/saml-idps` → "New IdP"
3. Use https://samltest.id/ for testing (free public IdP)
4. Get SP metadata: `http://enterprise.test:8000/saml/metadata/{idp_id}`
5. Upload SP metadata to your IdP
6. Test: `http://enterprise.test:8000/saml/login/{idp_id}`

---

## 9. Production Deployment

### 9.1 Server setup

```bash
# Clone to production
cd /var/www
git clone <repo> laravel-cms-v6
cd laravel-cms-v6

# Install dependencies (no dev)
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Configure
cp .env.example .env
php artisan key:generate
nano .env  # Set production values
```

### 9.2 Production `.env` settings

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cms.yourdomain.com
APP_CENTRAL_DOMAIN=cms.yourdomain.com

# Use production Let's Encrypt (not staging)
SSL_ENV=production

# Disable Telescope in production
TELESCOPE_ENABLED=false
```

### 9.3 Run migrations

```bash
php artisan migrate --force
php artisan db:seed --force  # Only on first deploy
php artisan storage:link
```

### 9.4 Optimize

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 9.5 Nginx config

Create `/etc/nginx/sites-available/cms`:

```nginx
server {
    listen 80;
    server_name cms.yourdomain.com *.cms.yourdomain.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cms.yourdomain.com *.cms.yourdomain.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/laravel-cms-v6/public;
    index index.php;

    # WebSocket proxy for Reverb (collab editing)
    location /app {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 86400;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/cms /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 9.6 Supervisor config

Create `/etc/supervisor/conf.d/cms.conf`:

```ini
[program:cms-queue]
command=php /var/www/laravel-cms-v6/artisan queue:work --queue=default,cms-sync,cms-events,audit-streaming --tries=3
numprocs=2
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/cms-queue.log

[program:cms-reverb]
command=php /var/www/laravel-cms-v6/artisan reverb:start --host=127.0.0.1 --port=8080
numprocs=1
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/cms-reverb.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cms-queue cms-reverb
```

### 9.7 Cron

```bash
crontab -e
```

Add:
```cron
* * * * * cd /var/www/laravel-cms-v6 && php artisan schedule:run >> /dev/null 2>&1
```

### 9.8 SSL automation (sudoers)

Allow web server to reload nginx for SSL renewals:

```bash
echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx" | sudo tee /etc/sudoers.d/cms-ssl
```

### 9.9 Deployment script

Create `deploy.sh`:

```bash
#!/bin/bash
set -e
cd /var/www/laravel-cms-v6

echo "Pulling latest code..."
git pull origin main

echo "Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm install && npm run build

echo "Running migrations..."
php artisan migrate --force

echo "Clearing and rebuilding caches..."
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "Restarting workers..."
sudo supervisorctl restart cms-queue
sudo supervisorctl restart cms-reverb

echo "Reloading nginx..."
sudo systemctl reload nginx

echo "✓ Deployment complete!"
```

```bash
chmod +x deploy.sh
```

---

## 10. Troubleshooting

### "No application encryption key has been specified"

```bash
php artisan key:generate
```

### "SQLSTATE[HY000] [2002] Connection refused"

```bash
# MySQL not running
sudo systemctl start mysql

# Check credentials in .env
DB_HOST=127.0.0.1
DB_PORT=3306
DB_USERNAME=cms_user
DB_PASSWORD=your_password
```

### "Class 'App\Http\Middleware\X' not found"

```bash
composer dump-autoload
php artisan optimize:clear
```

### Tenancy not working (all domains show same content)

1. Check the domain exists in the database:
```bash
php artisan tinker
>>> \App\Models\Central\Domain::where('domain', 'advmedi.test')->first()
```

2. If null, the seeder didn't run:
```bash
php artisan db:seed --force
```

3. If domain exists but `dns_verification_status` is not `verified`:
```bash
php artisan tinker
>>> \App\Models\Central\Domain::where('domain', 'advmedi.test')->update(['dns_verification_status' => 'verified', 'dns_verified_at' => now()])
```

4. Verify the domain is NOT in central_domains config:
```bash
php artisan tinker
>>> config('tenancy.central_domains')
# Should show ['platform.test', 'localhost', '127.0.0.1'] — NOT advmedi.test
```

### "Permission denied" on storage/

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Queue jobs not processing

1. Verify queue worker is running:
```bash
php artisan queue:work
```

2. Check failed jobs:
```bash
php artisan queue:failed
```

3. Retry all failed:
```bash
php artisan queue:retry all
```

4. Check Redis:
```bash
redis-cli ping
# Should return PONG
```

### Reverb (WebSocket) not connecting

1. Verify Reverb is running:
```bash
php artisan reverb:start --debug
```

2. Check `.env` has Reverb keys:
```bash
grep REVERB .env
```

3. Test WebSocket in browser console:
```javascript
let ws = new WebSocket('ws://localhost:8080/app/collab/test');
ws.onopen = () => console.log('Connected!');
ws.onerror = (e) => console.error('Failed:', e);
```

### RAG returns no results

1. Verify entries are published (RAG only indexes published):
```bash
php artisan tinker
>>> \App\Models\Tenant\Entry::where('status', 'published')->count()
```

2. Run reindex:
```bash
php artisan rag:reindex-stale
```

3. Check `rag_documents` table:
```bash
php artisan tinker
>>> \App\Models\Tenant\RagDocument::count()
# Should be > 0
```

4. Verify OpenAI API key is set:
```bash
grep OPENAI_API_KEY .env
```

### Billing commands not running

Check the scheduler is configured (cron):
```bash
crontab -l
# Should show: * * * * * cd /path && php artisan schedule:run
```

List scheduled commands:
```bash
php artisan schedule:list
```

Run manually to test:
```bash
php artisan billing:generate-invoices
php artisan billing:send-reminders
php artisan billing:suspend-overdue
php artisan billing:reactivate-paid
```

---

## 11. Daily Operations

### Common Artisan commands

```bash
# === Migrations ===
php artisan migrate                    # Run pending migrations
php artisan migrate:rollback           # Rollback last batch
php artisan migrate:fresh --seed       # Reset everything (DESTRUCTIVE!)

# === Cache ===
php artisan optimize:clear             # Clear ALL caches
php artisan config:cache               # Cache config (production)
php artisan route:cache                # Cache routes (production)

# === Queue ===
php artisan queue:work                 # Start worker
php artisan queue:failed               # List failed jobs
php artisan queue:retry all            # Retry all failed
php artisan queue:flush                # Delete all failed

# === Billing ===
php artisan billing:generate-invoices  # Generate recurring invoices
php artisan billing:send-reminders     # Send payment reminders
php artisan billing:suspend-overdue    # Suspend overdue tenants
php artisan billing:reactivate-paid    # Reactivate paid tenants

# === V4 SSL ===
php artisan ssl:renew                  # Renew certs expiring in 30 days
php artisan dns:retry-failed           # Retry DNS verification

# === V4 RAG ===
php artisan rag:reindex-stale          # Reindex published entries

# === V4 Audit ===
php artisan audit:verify-chain         # Verify activity log chain
php artisan audit:retry-failed-deliveries

# === V4 Collab ===
php artisan collab:cleanup-stale-sessions

# === V4 Workflow ===
php artisan workflow:check-sla-breaches

# === V6 Backup ===
php artisan cms:backup                 # Create full backup
php artisan cms:backup --tenant={id}   # Per-tenant backup
php artisan cms:backup --list          # List all backups
php artisan cms:backup --prune=30      # Delete old backups
php artisan cms:restore {path} --force # Restore from backup

# === CMS utilities ===
php artisan cms:install                # Full installation
php artisan cms:create-tenant {name} {slug} --domain={domain}
php artisan scheduled:make             # Publish scheduled entries
php artisan site:export --tenant={id}  # Export site as static HTML
```

### Creating a new tenant

```bash
php artisan cms:create-tenant "Acme Corp" acme --domain=acme.test --feature=multi_domain --feature=workflow_engine
```

Or via the platform admin:
1. Login to `http://platform.test/admin`
2. Go to "Tenants" → "Create"
3. Fill in name, slug, plan, domain
4. Save

### Backups

**Daily backup (automatic via cron):**
```bash
# Already scheduled — runs daily at 04:00
# Backups stored in storage/app/backups/
```

**Manual backup:**
```bash
php artisan cms:backup                    # Full platform
php artisan cms:backup --tenant=acme-id   # Per-tenant
```

**List backups:**
```bash
php artisan cms:backup --list
```

**Restore:**
```bash
php artisan cms:restore /path/to/backup.zip --force
```

### Health monitoring

```bash
# Check all systems
curl http://platform.test:8000/health | jq

# Expected response:
{
  "status": "healthy",
  "checks": {
    "database": {"status": "healthy"},
    "redis": {"status": "healthy"},
    "storage": {"status": "healthy"},
    "queue": {"status": "healthy", "failed_jobs": 0}
  }
}
```

### Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Queue worker logs
tail -f storage/logs/laravel.log | grep -i queue

# Collab (Reverb) logs
tail -f /var/log/cms-reverb.log  # production

# View in Telescope (dev only)
open http://platform.test:8000/telescope
```

---

## Quick Reference Card

| What | Command / URL |
|---|---|
| Start (local) | `./start.sh` |
| Stop | `Ctrl+C` or `pkill -f "artisan"` |
| Admin login | `http://{tenant}:8000/admin` |
| Platform admin | `http://platform.test:8000/admin` |
| Default password | `password` |
| Health check | `http://platform.test:8000/health` |
| Telescope (dev) | `http://platform.test:8000/telescope` |
| Clear cache | `php artisan optimize:clear` |
| Run migrations | `php artisan migrate` |
| Run seeders | `php artisan db:seed` |
| Create backup | `php artisan cms:backup` |
| Queue worker | `php artisan queue:work` |
| Reverb | `php artisan reverb:start` |
| Tinker | `php artisan tinker` |
| Schedule list | `php artisan schedule:list` |
| Route list | `php artisan route:list` |
| Run tests | `php artisan test` |

---

## Need Help?

1. Check the **Troubleshooting** section above
2. Read `IMPLEMENTATION-AND-USER-GUIDE.md` for feature-specific guides
3. Read `CODE-REVIEW-FIXES.md` for details on security fixes
4. Read `05-V5-UNIFIED-BUILD-PLAN.md` for phase-by-phase status
5. Read `06-V6-ADVANCED-FEATURES.md` for V6 feature comparison vs Statamic
6. Read `docs/deployment.md` for production deployment details
7. Read `docs/architecture.md` for system architecture overview

**You're now ready to build a world-class multi-tenant CMS! 🚀**
