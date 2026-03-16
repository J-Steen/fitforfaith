<?php
/**
 * FitForFaith — Front Controller
 * All HTTP requests are routed through this file via .htaccess
 */

// Load configuration — app.php first (defines APP_URL, autoloader, etc.)
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/strava.php';
require_once __DIR__ . '/config/payfast.php';
require_once __DIR__ . '/config/mail.php';

// ---------------------------------------------------------------
// Router setup
// ---------------------------------------------------------------
use App\Core\Router;
use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\LeaderboardController;
use App\Controllers\StravaController;
use App\Controllers\DonationController;
use App\Controllers\ProfileController;
use App\Controllers\QRController;
use App\Controllers\Admin\AdminController;
use App\Controllers\Admin\UsersController;
use App\Controllers\Admin\ChurchesController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\ActivitiesController;
use App\Controllers\Admin\DonationsController;
use App\Controllers\LangController;

// Detect base path (useful if app is in a subdirectory).
// On PHP built-in server, SCRIPT_NAME equals the request URI — not the script file.
// In that case fall back to empty base path (app at root).
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
if (substr($scriptName, -4) !== '.php') {
    $scriptName = '/index.php';
}
$basePath = rtrim(dirname($scriptName), '/');
$router   = new Router($basePath);

// ── Language switch ────────────────────────────────────────────
$router->get('/lang/:locale', [LangController::class, 'set']);

// ── Public routes ──────────────────────────────────────────────
$router->get('/',            [HomeController::class, 'index']);
$router->get('/leaderboard', [LeaderboardController::class, 'index']);

// ── Auth routes ────────────────────────────────────────────────
$router->get('/register',             [AuthController::class, 'showRegister']);
$router->post('/register',            [AuthController::class, 'register']);
$router->get('/login',                [AuthController::class, 'showLogin']);
$router->post('/login',               [AuthController::class, 'login']);
$router->get('/logout',               [AuthController::class, 'logout']);
$router->get('/forgot-password',      [AuthController::class, 'showForgot']);
$router->post('/forgot-password',     [AuthController::class, 'forgot']);
$router->get('/reset-password/:token',[AuthController::class, 'showReset']);
$router->post('/reset-password/:token',[AuthController::class, 'reset']);
$router->get('/verify-email/:token',  [AuthController::class, 'verifyEmail']);
$router->get('/resend-verification',  [AuthController::class, 'resendVerification'], ['auth']);

// ── User routes ────────────────────────────────────────────────
$router->get('/dashboard',     [DashboardController::class, 'index'],         ['auth']);
$router->get('/profile/edit',  [ProfileController::class, 'edit'],            ['auth']);
$router->post('/profile/edit', [ProfileController::class, 'update'],          ['auth']);
$router->post('/profile/password', [ProfileController::class, 'changePassword'], ['auth']);

// ── Strava routes ──────────────────────────────────────────────
$router->get('/strava/connect-page', [StravaController::class, 'connectPage'],       ['auth']);
$router->get('/strava/connect',      [StravaController::class, 'connect'],           ['auth']);
$router->get('/strava/callback',     [StravaController::class, 'callback'],          ['auth']);
$router->post('/strava/disconnect',  [StravaController::class, 'disconnect'],        ['auth']);
$router->get('/strava/webhook',      [StravaController::class, 'webhookChallenge'],  ['no-csrf']);
$router->post('/strava/webhook',     [StravaController::class, 'webhookReceive'],    ['no-csrf']);

// ── Donation / payment routes ──────────────────────────────────
$router->get('/donate',         [DonationController::class, 'form'],     ['auth']);
$router->post('/donate',        [DonationController::class, 'initiate'], ['auth']);
$router->get('/donate/return',  [DonationController::class, 'return']);
$router->get('/donate/cancel',  [DonationController::class, 'cancel']);
$router->post('/donate/itn',    [DonationController::class, 'itn'],      ['no-csrf']);

// ── QR Code routes ─────────────────────────────────────────────
$router->get('/qr/:token',       [QRController::class, 'serveImage']);
$router->get('/register/:token', [AuthController::class, 'showRegister']); // QR registration landing

// ── Admin routes ───────────────────────────────────────────────
$router->get('/admin',           [AdminController::class,    'dashboard'],  ['admin']);

$router->get('/admin/users',           [UsersController::class, 'index'],   ['admin']);
$router->get('/admin/users/:id/edit',  [UsersController::class, 'edit'],    ['admin']);
$router->post('/admin/users/:id/update', [UsersController::class, 'update'], ['admin']);
$router->post('/admin/users/:id/delete',        [UsersController::class, 'delete'],       ['admin']);
$router->post('/admin/users/:id/toggle-active',      [UsersController::class, 'toggleActive'],      ['admin']);
$router->post('/admin/users/:id/strava-disconnect', [UsersController::class, 'stravaDisconnect'], ['admin']);
$router->post('/admin/users/:id/strava-sync',       [UsersController::class, 'stravaSync'],       ['admin']);

$router->get('/admin/churches',             [ChurchesController::class, 'index'],  ['admin']);
$router->get('/admin/churches/new',         [ChurchesController::class, 'create'], ['admin']);
$router->post('/admin/churches',            [ChurchesController::class, 'store'],  ['admin']);
$router->get('/admin/churches/:id/edit',    [ChurchesController::class, 'edit'],   ['admin']);
$router->post('/admin/churches/:id/update', [ChurchesController::class, 'update'], ['admin']);
$router->post('/admin/churches/:id/delete', [ChurchesController::class, 'delete'], ['admin']);
$router->post('/admin/churches/qr',         [ChurchesController::class, 'createQR'], ['admin']);

$router->get('/admin/settings',         [SettingsController::class, 'index'],          ['admin']);
$router->post('/admin/settings',        [SettingsController::class, 'update'],         ['admin']);
$router->post('/admin/change-password', [SettingsController::class, 'changePassword'], ['admin']);

$router->get('/admin/activities',            [ActivitiesController::class, 'index'],  ['admin']);
$router->post('/admin/activities/:id/flag',   [ActivitiesController::class, 'flag'],   ['admin']);
$router->post('/admin/activities/:id/unflag', [ActivitiesController::class, 'unflag'], ['admin']);

$router->get('/admin/donations', [DonationsController::class, 'index'], ['admin']);

// ── Dispatch ───────────────────────────────────────────────────
$router->dispatch();
