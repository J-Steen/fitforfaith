<?php
// Copy to config/payfast.php and fill in your PayFast credentials.
// https://developers.payfast.co.za/
define('PAYFAST_SANDBOX',      false); // true for testing, false for live
define('PAYFAST_MERCHANT_ID',  getenv('PAYFAST_MERCHANT_ID')  ?: 'YOUR_MERCHANT_ID');
define('PAYFAST_MERCHANT_KEY', getenv('PAYFAST_MERCHANT_KEY') ?: 'YOUR_MERCHANT_KEY');
define('PAYFAST_PASSPHRASE',   getenv('PAYFAST_PASSPHRASE')   ?: 'YOUR_PASSPHRASE');

define('PAYFAST_HOST', PAYFAST_SANDBOX ? 'sandbox.payfast.co.za' : 'www.payfast.co.za');
define('PAYFAST_URL',  'https://' . PAYFAST_HOST . '/eng/process');

define('PAYFAST_RETURN_URL',  APP_URL . '/donate/return');
define('PAYFAST_CANCEL_URL',  APP_URL . '/donate/cancel');
define('PAYFAST_NOTIFY_URL',  APP_URL . '/donate/itn');

define('PAYFAST_VALID_IPS', [
    '197.97.145.144','197.97.145.145','197.97.145.146','197.97.145.147',
    '41.74.179.194', '41.74.179.195', '41.74.179.196', '41.74.179.197',
]);
