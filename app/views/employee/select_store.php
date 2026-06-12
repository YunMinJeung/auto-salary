<?php /* app/views/employee/select_store.php */ ?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>매장 선택</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:420px">
  <div class="text-center mb-4">
    <i class="bi bi-shop fs-1 text-primary"></i>
    <h5 class="mt-2 fw-bold">오늘 어느 매장 근무인가요?</h5>
    <p class="text-muted small">로그인한 계정이 여러 매장에 등록되어 있습니다.</p>
  </div>

  <?php if ($msg = flash('error')): ?>
  <div class="alert alert-danger py-2 small"><?= h($msg) ?></div>
  <?php endif; ?>

  <div class="d-grid gap-2">
    <?php foreach ($members as $m): ?>
    <form method="POST" action="<?= url('employee', 'select_store') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="store_member_id" value="<?= (int)$m['id'] ?>">
      <input type="hidden" name="store_id" value="<?= (int)$m['store_id'] ?>">
      <button type="submit" class="btn btn-outline-primary w-100 text-start py-3">
        <i class="bi bi-shop me-2"></i>
        <strong><?= h($m['store_name']) ?></strong>
        <small class="d-block text-muted mt-1">시급 <?= number_format((int)$m['hourly_wage']) ?>원</small>
      </button>
    </form>
    <?php endforeach; ?>
  </div>

  <div class="text-center mt-4">
    <a href="<?= url('auth', 'logout') ?>" class="text-muted small">로그아웃</a>
  </div>
</div>
</body>
</html>
