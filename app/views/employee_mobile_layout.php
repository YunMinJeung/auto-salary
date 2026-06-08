<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<meta name="theme-color" content="#003844">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="출퇴근">
<title><?= h($title ?? '출퇴근') ?></title>
<link rel="manifest" href="<?= BASE_URL ?>manifest.json">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  :root {
    --c-dark:  #003844;
    --c-teal:  #006C67;
    --c-pink:  #F194B4;
    --c-amber: #FFB100;
    --c-cream: #FFEBC6;
  }
  body {
    background: #f4f6f8;
    font-size: 16px;
    min-height: 100dvh;
    display: flex;
    flex-direction: column;
  }
  .emp-header {
    background: var(--c-dark);
    color: #fff;
    padding: 14px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: sticky;
    top: 0;
    z-index: 100;
  }
  .emp-header .store-name { font-size:.85rem; opacity:.7; }
  .emp-header .emp-name   { font-weight:700; font-size:1rem; }
  main { flex:1; padding:16px; max-width:520px; margin:0 auto; width:100%; }
</style>
</head>
<body>

<header class="emp-header">
  <div>
    <div class="store-name"><?= h($member['store_name'] ?? '사업장') ?></div>
    <div class="emp-name"><?= h(Auth::user()['name'] ?? '') ?></div>
  </div>
  <a href="<?= url('auth', 'logout') ?>" class="btn btn-sm btn-outline-light py-1 px-2">
    <i class="bi bi-box-arrow-right"></i>
  </a>
</header>

<main>

<?php if ($msg = flash('success')): ?>
<div class="alert alert-success alert-dismissible fade show py-2" role="alert">
  <i class="bi bi-check-circle-fill me-1"></i><?= h($msg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
<div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
  <i class="bi bi-exclamation-triangle-fill me-1"></i><?= h($msg) ?>
  <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<?= $content ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('/service-worker.js').catch(function(){});
}
</script>
<?php if (isset($extraJs)): echo $extraJs; endif; ?>
</body>
</html>
