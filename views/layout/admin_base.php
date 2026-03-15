<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#0b0b18">
  <title><?= h($pageTitle ?? 'Admin — ' . APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
  <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="bg-orbs"></div>
<div class="bg-orb-3"></div>

<div class="page-wrapper">
  <!-- Top Nav -->
  <nav class="nav">
    <div class="nav-inner">
      <a href="<?= url('admin') ?>" class="nav-logo"><?= h(APP_NAME) ?> Admin</a>
      <ul class="nav-links" style="display:flex;">
        <li><a href="<?= url() ?>" style="font-size:0.8rem;">← Public Site</a></li>
        <li><a href="<?= url('logout') ?>">Logout</a></li>
      </ul>
    </div>
  </nav>

  <div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar" id="adminSidebar">
      <div class="sidebar-section">
        <div class="sidebar-section-label">Overview</div>
        <a href="<?= url('admin') ?>" class="sidebar-link <?= rtrim($_SERVER['REQUEST_URI'],'/')===rtrim(parse_url(url('admin'),PHP_URL_PATH),'/') ? 'active' : '' ?>">
          <span class="icon"><i class="fa-solid fa-chart-line"></i></span> Dashboard
        </a>
      </div>
      <div class="sidebar-section">
        <div class="sidebar-section-label">People</div>
        <a href="<?= url('admin/users') ?>" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'admin/users') ? 'active' : '' ?>">
          <span class="icon"><i class="fa-solid fa-users"></i></span> Users
        </a>
        <a href="<?= url('admin/churches') ?>" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'admin/churches') ? 'active' : '' ?>">
          <span class="icon"><i class="fa-solid fa-church"></i></span> Churches
        </a>
      </div>
      <div class="sidebar-section">
        <div class="sidebar-section-label">Data</div>
        <a href="<?= url('admin/activities') ?>" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'admin/activities') ? 'active' : '' ?>">
          <span class="icon"><i class="fa-solid fa-person-running"></i></span> Activities
        </a>
        <a href="<?= url('admin/donations') ?>" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'admin/donations') ? 'active' : '' ?>">
          <span class="icon"><i class="fa-solid fa-credit-card"></i></span> Payments
        </a>
      </div>
      <div class="sidebar-section">
        <div class="sidebar-section-label">Config</div>
        <a href="<?= url('admin/settings') ?>" class="sidebar-link <?= str_contains($_SERVER['REQUEST_URI'],'admin/settings') ? 'active' : '' ?>">
          <span class="icon"><i class="fa-solid fa-gear"></i></span> Settings
        </a>
      </div>
    </aside>

    <main class="admin-main">
      <?php
      $flashSuccess = get_flash('success');
      $flashError   = get_flash('error');
      if ($flashSuccess): ?>
        <div class="alert alert-success mb-4"><i class="fa-solid fa-check"></i> <?= h($flashSuccess) ?></div>
      <?php endif;
      if ($flashError): ?>
        <div class="alert alert-error mb-4"><i class="fa-solid fa-triangle-exclamation"></i> <?= h($flashError) ?></div>
      <?php endif; ?>
