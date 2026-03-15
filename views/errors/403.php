<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>403 Forbidden</title>
  <link rel="stylesheet" href="<?= APP_URL ?>/public/css/app.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="bg-orbs"></div>
<div class="page-wrapper">
  <div style="min-height:100vh; display:flex; align-items:center; justify-content:center; text-align:center; padding:40px 20px;">
    <div>
      <div style="font-size:6rem; margin-bottom:16px;"><i class="fa-solid fa-lock" style="color:var(--text-muted);"></i></div>
      <h1 style="font-size:4rem; font-weight:900; color:var(--text-muted);">403</h1>
      <h2 class="section-title mt-2">Access Denied</h2>
      <p class="text-muted mt-2 mb-4">You don't have permission to access this area.</p>
      <a href="<?= APP_URL ?>/" class="btn btn-primary">← Back Home</a>
    </div>
  </div>
</div>
</body>
</html>
