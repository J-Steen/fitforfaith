<!DOCTYPE html>
<html lang="<?= \App\Core\Lang::get() ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <meta name="theme-color" content="#0b0b18">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="description" content="<?= APP_TAGLINE ?>">

  <title><?= h($pageTitle ?? APP_NAME) ?></title>

  <!-- PWA -->
  <link rel="manifest" href="<?= url('public/manifest.json') ?>">
  <link rel="apple-touch-icon" href="<?= asset('icons/icon-192.png') ?>">

  <!-- Styles -->
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="bg-orbs"></div>
<div class="bg-orb-3"></div>

<div class="page-wrapper">
  <!-- Navigation -->
  <nav class="nav" aria-label="Main navigation">
    <div class="nav-inner">
      <a href="<?= url() ?>" class="nav-logo"><?= h(APP_NAME) ?></a>

      <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
        <i class="fa-solid fa-bars"></i>
      </button>

      <ul class="nav-links" id="navLinks" role="list">
        <li><a href="<?= url('leaderboard') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'], 'leaderboard') ? 'active' : '' ?>"><?= t('nav.leaderboard') ?></a></li>
        <?php if (auth_check()): ?>
          <li><a href="<?= url('dashboard') ?>" class="<?= str_contains($_SERVER['REQUEST_URI'], 'dashboard') ? 'active' : '' ?>"><?= t('nav.dashboard') ?></a></li>
          <li><a href="<?= url('profile/edit') ?>"><?= t('nav.profile') ?></a></li>
          <li><a href="<?= url('logout') ?>"><?= t('nav.logout') ?></a></li>
        <?php else: ?>
          <li><a href="<?= url('login') ?>"><?= t('nav.login') ?></a></li>
          <li><a href="<?= url('register') ?>" class="btn-nav"><?= t('nav.register') ?></a></li>
        <?php endif; ?>
        <li>
          <?php $lang = \App\Core\Lang::get(); ?>
          <div class="lang-toggle" role="group" aria-label="Language">
            <a href="<?= url('lang/en') ?>" class="lang-btn<?= $lang === 'en' ? ' lang-active' : '' ?>">EN</a>
            <a href="<?= url('lang/af') ?>" class="lang-btn<?= $lang === 'af' ? ' lang-active' : '' ?>">AF</a>
          </div>
        </li>
      </ul>
    </div>
  </nav>

  <main>
    <?php
    // Flash messages
    $flashSuccess = get_flash('success');
    $flashError   = get_flash('error');
    $flashInfo    = get_flash('info');
    if ($flashSuccess || $flashError || $flashInfo):
    ?>
    <div class="container" style="padding-top:24px;">
      <?php if ($flashSuccess): ?>
        <div class="alert alert-success"><i class="fa-solid fa-check"></i> <?= h($flashSuccess) ?></div>
      <?php endif; ?>
      <?php if ($flashError): ?>
        <div class="alert alert-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($flashError) ?></div>
      <?php endif; ?>
      <?php if ($flashInfo): ?>
        <div class="alert alert-info"><i class="fa-solid fa-circle-info"></i> <?= h($flashInfo) ?></div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
