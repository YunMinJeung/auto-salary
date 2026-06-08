<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? '급여계산기') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body class="bg-light">

<div class="min-vh-100 d-flex align-items-center justify-content-center py-5">
  <div class="w-100" style="max-width:420px">

    <div class="text-center mb-4">
      <a href="<?= url() ?>" class="text-decoration-none">
        <i class="bi bi-calculator-fill fs-2" style="color:var(--c-teal)"></i>
        <div class="fw-bold fs-5 mt-1" style="color:var(--c-dark)">급여계산기</div>
      </a>
    </div>

    <?php if ($msg = flash('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <i class="bi bi-check-circle-fill me-1"></i><?= h($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <i class="bi bi-exclamation-triangle-fill me-1"></i><?= h($msg) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?= $content ?>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
