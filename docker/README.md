# Docker Deployment Notes

This image is intended for EasyPanel or any VPS panel that builds from a Git repository.

## EasyPanel

Use these basics:

- Build type: Dockerfile
- Port: 80
- Database: create/use a MySQL service, then point the Laravel env values to it

## Required Environment Variables

Set these in EasyPanel:

```env
APP_NAME="K-Maint"
APP_ENV=production
APP_KEY=base64:change-this-with-a-real-laravel-key
APP_DEBUG=false
APP_URL=https://your-domain.com
FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=your-mysql-host
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password

LOG_CHANNEL=stderr
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

Optional:

```env
RUN_MIGRATIONS=true
```

Use `RUN_MIGRATIONS=true` only when you want the container to run `php artisan migrate --force` during startup. After the first successful deploy, it is usually safer to set it back to `false`.

## APP_KEY

Generate a Laravel key once and keep it stable. Do not change it on every redeploy.

If you have PHP locally:

```bash
php artisan key:generate --show
```

Then paste the output into EasyPanel as `APP_KEY`.
