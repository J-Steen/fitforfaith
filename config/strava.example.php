<?php
// Copy to config/strava.php and fill in your Strava app credentials.
// Register at https://www.strava.com/settings/api
define('STRAVA_CLIENT_ID',     getenv('STRAVA_CLIENT_ID')     ?: 'YOUR_STRAVA_CLIENT_ID');
define('STRAVA_CLIENT_SECRET', getenv('STRAVA_CLIENT_SECRET') ?: 'YOUR_STRAVA_CLIENT_SECRET');
define('STRAVA_VERIFY_TOKEN',  getenv('STRAVA_VERIFY_TOKEN')  ?: 'choose_a_random_secret_string');
define('STRAVA_WEBHOOK_URL',   APP_URL . '/strava/webhook');
define('STRAVA_REDIRECT_URI',  APP_URL . '/strava/callback');
define('STRAVA_SCOPE',    'activity:read_all');
define('STRAVA_API_BASE', 'https://www.strava.com/api/v3');
define('STRAVA_AUTH_URL', 'https://www.strava.com/oauth/authorize');
define('STRAVA_TOKEN_URL','https://www.strava.com/oauth/token');
