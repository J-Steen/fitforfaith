# Strava Webhook Setup

## Step 1: Create a Strava App
1. Go to https://www.strava.com/settings/api
2. Create an application
3. Set the callback domain to your domain (e.g. `yourdomain.com`)
4. Copy your Client ID and Client Secret into `config/strava.php`

## Step 2: Set Your Webhook Verify Token
In `config/strava.php`, set a unique secret:
```php
define('STRAVA_VERIFY_TOKEN', 'my_unique_secret_abc123');
```

## Step 3: Register the Webhook Subscription
Run this cURL command (replace values):
```bash
curl -X POST https://www.strava.com/api/v3/push_subscriptions \
  -F client_id=YOUR_CLIENT_ID \
  -F client_secret=YOUR_CLIENT_SECRET \
  -F callback_url=https://yourdomain.com/strava/webhook \
  -F verify_token=my_unique_secret_abc123
```

Expected response:
```json
{"id": 12345, "resource_state": 2, ...}
```

## Step 4: Verify the Subscription Exists
```bash
curl -G https://www.strava.com/api/v3/push_subscriptions \
  -d client_id=YOUR_CLIENT_ID \
  -d client_secret=YOUR_CLIENT_SECRET
```

## How It Works
1. User completes an activity on Strava
2. Strava sends a webhook event to `https://yourdomain.com/strava/webhook`
3. The app queues the event in the database (responds in <200ms)
4. The `cron/process_webhook_queue.php` cron processes the queue every minute
5. The activity is fetched from Strava, points calculated, and the user's cache updated

## Deleting a Subscription (if needed)
```bash
curl -X DELETE "https://www.strava.com/api/v3/push_subscriptions/SUBSCRIPTION_ID" \
  -F client_id=YOUR_CLIENT_ID \
  -F client_secret=YOUR_CLIENT_SECRET
```

## Initial Activity Import
After a user connects Strava for the first time, their past activities are NOT
automatically imported via webhooks (webhooks only fire for new activities).

To import existing activities, run the cron manually or add an "Import Historical
Activities" feature that calls `StravaService::fetchAllActivitiesSince()`.
A manual import admin tool can be added in the admin panel as a future feature.
