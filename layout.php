<?php $user = current_user(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title><?= e($pageTitle !== '' ? $pageTitle . ' | ' : '') . e(config('app.name')) ?></title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/app.js" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to main content</a>
<header class="site-header">
  <div class="container nav">
    <a class="brand" href="<?= e(url('home/index')) ?>">🍔 FoodHub</a>
    <nav aria-label="Main navigation">
      <a href="<?= e(url('menu/index')) ?>">Menu</a>
      <a href="<?= e(url('cart/index')) ?>">Cart <span class="badge" id="cart-count"><?= Cart::count() ?></span></a>
      <?php if ($user): ?>
        <a href="<?= e(url('order/index')) ?>">My orders</a>
        <?php if ($user['role'] === 'admin'): ?>
          <a href="<?= e(url('admin/dashboard')) ?>">Admin</a>
        <?php endif; ?>
        <span class="who">Hi, <?= e($user['name']) ?></span>
        <form method="post" action="<?= e(url('auth/logout')) ?>" class="inline">
          <?= csrf_field() ?>
          <button type="submit" class="link-btn">Log out</button>
        </form>
      <?php else: ?>
        <a href="<?= e(url('auth/login')) ?>">Log in</a>
        <a href="<?= e(url('auth/register')) ?>">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main id="main" class="container">
  <div id="flash-area" aria-live="polite">
  <?php foreach (get_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>" role="status"><?= e($f['message']) ?></div>
  <?php endforeach; ?>
  </div>
  <?= $content ?>
</main>
<footer class="site-footer"><div class="container">© <?= date('Y') ?> FoodHub · CIS324 Web Engineering project (payments are simulated)</div></footer>
</body>
</html>
