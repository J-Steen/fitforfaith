# Cron Job Setup (cPanel)

## Add These Cron Jobs via cPanel > Cron Jobs

Replace `/home/username/public_html` with your actual path.

### 1. Process Strava Webhooks (every minute)
```
* * * * * php /home/username/public_html/cron/process_webhook_queue.php >> /dev/null 2>&1
```

### 2. Rebuild Leaderboard Cache (every 5 minutes)
```
*/5 * * * * php /home/username/public_html/cron/rebuild_leaderboard.php >> /dev/null 2>&1
```

### 3. Refresh Strava Tokens (every hour)
```
0 * * * * php /home/username/public_html/cron/refresh_strava_tokens.php >> /dev/null 2>&1
```

## Testing Crons Manually
You can test crons via SSH:
```bash
php /home/username/public_html/cron/rebuild_leaderboard.php
php /home/username/public_html/cron/process_webhook_queue.php
```

## Log Files
Cron output is written to `storage/logs/app.log`.
To capture cron output to a file instead of /dev/null:
```
*/5 * * * * php /home/username/public_html/cron/rebuild_leaderboard.php >> /home/username/public_html/storage/logs/cron.log 2>&1
```
