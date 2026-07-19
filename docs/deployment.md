# Deployment Guide

## Requirements

- PHP 8.2+
- MySQL 8.0+ or PostgreSQL 16+ (for pgvector/RAG)
- Redis 6+
- Nginx or Apache web server
- Composer 2.x
- Node.js 18+ (for Vite asset compilation)
- Supervisor (for queue workers + Reverb WebSocket server)

## Environment Setup

### 1. Clone and install

```bash
git clone <repo> /var/www/laravel-cms-v4
cd /var/www/laravel-cms-v4
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 2. Configure environment

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://cms.example.com
APP_CENTRAL_DOMAIN=cms.example.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=laravel_cms_v4
DB_USERNAME=cms_user
DB_PASSWORD=strong-password

REDIS_HOST=127.0.0.1
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
CACHE_STORE=redis

# SSL Automation
SSL_PROVIDER=letsencrypt
SSL_ENV=production
SSL_RELOAD_CMD="sudo systemctl reload nginx"
CLOUDFLARE_API_TOKEN=your-token

# AI Providers
AI_PROVIDER=openai
OPENAI_API_KEY=your-key

# Reverb (Collab)
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_APP_ID=your-id
REVERB_APP_KEY=your-key
REVERB_APP_SECRET=your-secret
```

### 3. Run migrations and seed

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

### 4. Optimize for production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

## Web Server Configuration

### Nginx

```nginx
server {
    listen 80;
    server_name cms.example.com *.cms.example.com;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name cms.example.com *.cms.example.com;

    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    root /var/www/laravel-cms-v4/public;
    index index.php index.html;

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
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Sudoers for SSL reload

Allow the web server user to reload nginx without password:

```bash
echo "www-data ALL=(ALL) NOPASSWD: /bin/systemctl reload nginx" | sudo tee /etc/sudoers.d/cms-ssl
```

## Supervisor Configuration

### Queue worker

```ini
[program:cms-queue]
command=php /var/www/laravel-cms-v4/artisan queue:work --queue=default,cms-sync,cms-events,audit-streaming --tries=3
numprocs=2
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/cms-queue.log
```

### Reverb WebSocket server

```ini
[program:cms-reverb]
command=php /var/www/laravel-cms-v4/artisan reverb:start --host=127.0.0.1 --port=8080
numprocs=1
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/log/cms-reverb.log
```

## Cron Setup

Add to crontab:

```cron
* * * * * cd /var/www/laravel-cms-v4 && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled commands:
- `ssl:renew` — daily at 02:00
- `dns:retry-failed` — hourly
- `audit:verify-chain` — weekly
- `workflow:check-sla-breaches` — daily at 08:00
- `experiments:check-winners` — hourly
- `rag:reindex-stale` — daily at 03:00
- `collab:cleanup-stale-sessions` — every 15 minutes
- `audit:retry-failed-deliveries` — every 5 minutes
- `scheduled:make` — every minute (publish scheduled entries)

## Deployment Script

```bash
#!/bin/bash
set -e

cd /var/www/laravel-cms-v4

# Pull latest code
git pull origin main

# Install dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build

# Run migrations
php artisan migrate --force

# Clear and rebuild caches
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Restart workers
sudo supervisorctl restart cms-queue
sudo supervisorctl restart cms-reverb

# Reload web server (for new SSL certs)
sudo systemctl reload nginx

# Warmup
php artisan scheduled:make
php artisan workflow:check-sla-breaches
php artisan experiments:check-winners

echo "Deployment complete!"
```

## Backup Strategy

### Database backup

```bash
# Daily database backup
mysqldump -u cms_user -p laravel_cms_v4 | gzip > /backups/db-$(date +%Y%m%d).sql.gz

# Keep 30 days of backups
find /backups -name "db-*.sql.gz" -mtime +30 -delete
```

### Automated backup command

```bash
php artisan cms:backup --path=/backups --keep=30
```

### Restore

```bash
gunzip < /backups/db-20240101.sql.gz | mysql -u cms_user -p laravel_cms_v4
```

## SSL Certificate Setup

SSL is automated via Let's Encrypt ACME:

1. Add a domain in the admin panel
2. Publish the DNS TXT record shown
3. Platform verifies DNS ownership
4. Platform requests SSL cert via ACME
5. Cert is stored and nginx is reloaded

For wildcard certs (`*.example.com`), DNS-01 challenge is used via Cloudflare/Route53/DigitalOcean API.

## New Tenant Onboarding

1. Platform admin creates tenant in `/platform/tenants`
2. Adds domain(s) to tenant
3. Tenant admin publishes DNS TXT record
4. Platform verifies DNS and issues SSL
5. Tenant admin configures theme, creates content

## Monitoring

- **Laravel Telescope** (dev): `/telescope`
- **Laravel Horizon** (production): `/horizon`
- **Sentry** (production error tracking): configure `SENTRY_DSN`
- **Audit streaming**: forward activity logs to Splunk/Datadog/Elastic

## Performance Optimization

- Enable OPCache: `opcache.enable=1`
- Enable Redis for sessions, cache, queue
- Use `php artisan config:cache` in production
- Full-page Redis cache for public content (keyed by tenant_id + theme_id + path)
- Eager-load relationships to avoid N+1 queries
- Use Telescope in dev only, disable in production
