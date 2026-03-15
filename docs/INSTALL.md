# FitForFaith — Installation Guide

## Requirements
- PHP 7.4 or 8.x
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled
- cURL extension enabled
- GD extension (optional, for QR code rendering)

## Quick Start (cPanel / Shared Hosting)

### 1. Upload Files
Upload all files to your `public_html/` directory (or a subdirectory).

### 2. Configure Database
1. Create a MySQL database via cPanel > MySQL Databases
2. Copy `config/database.example.php` to `config/database.php`
3. Fill in your database credentials:

```php
define('DB_HOST',    'localhost');
define('DB_NAME',    'your_db_name');
define('DB_USER',    'your_db_user');
define('DB_PASS',    'your_db_password');
```

### 3. Configure the App
Edit `config/app.php`:
```php
define('APP_URL', 'https://yourdomain.com');
define('APP_ENV', 'production');
```

### 4. Configure Strava
1. Go to https://www.strava.com/settings/api
2. Create an application
3. Edit `config/strava.php`:
```php
define('STRAVA_CLIENT_ID',     '12345');
define('STRAVA_CLIENT_SECRET', 'your_secret');
define('STRAVA_VERIFY_TOKEN',  'a_random_secret_string');
```

### 5. Configure PayFast
Edit `config/payfast.php`:
```php
define('PAYFAST_SANDBOX',      false); // true for testing
define('PAYFAST_MERCHANT_ID',  'your_merchant_id');
define('PAYFAST_MERCHANT_KEY', 'your_merchant_key');
define('PAYFAST_PASSPHRASE',   'your_passphrase'); // if set in PayFast
```

### 6. Run the Installer
Visit: `https://yourdomain.com/install.php?token=change-this-token-before-use`

**Edit the token in install.php first!**

### 7. Delete the Installer
After successful installation, delete `install.php`.

### 8. Set Up Cron Jobs
See `docs/CRON_SETUP.md`

### 9. Register Strava Webhook
See `docs/STRAVA_WEBHOOK_SETUP.md`

---

## Environment Variables (Alternative to config files)
You can use environment variables instead of editing config files:
```
APP_URL=https://yourdomain.com
APP_ENV=production
STRAVA_CLIENT_ID=12345
STRAVA_CLIENT_SECRET=...
STRAVA_VERIFY_TOKEN=...
PAYFAST_MERCHANT_ID=...
PAYFAST_MERCHANT_KEY=...
PAYFAST_PASSPHRASE=...
```

Set these via cPanel > PHP Environment Variables or `.htaccess`:
```apache
SetEnv APP_URL https://yourdomain.com
SetEnv STRAVA_CLIENT_ID 12345
```

---

## Security Checklist
- [ ] `config/database.php` is in place (not the example file)
- [ ] `APP_ENV` is set to `production`
- [ ] `install.php` is deleted
- [ ] Admin password changed from default
- [ ] PayFast is set to live mode (`PAYFAST_SANDBOX = false`)
- [ ] All storage directories are not web-accessible (verified by .htaccess)
- [ ] HTTPS is enabled on your domain
