<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? '급여계산기') ?> — <?= h(Setting::get()['business_name']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
  <div class="container">
    <a class="navbar-brand fw-bold" href="<?= url() ?>">
      <i class="bi bi-calculator-fill me-1"></i>급여계산기
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto gap-1">
        <li class="nav-item">
          <a class="nav-link <?= ($_GET['c'] ?? '') === 'employees' ? 'active' : '' ?>"
             href="<?= url('employees') ?>">
            <i class="bi bi-people-fill"></i> 직원 관리
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($_GET['c'] ?? '') === 'work_logs' ? 'active' : '' ?>"
             href="<?= url('work_logs') ?>">
            <i class="bi bi-calendar3"></i> 근무 기록
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($_GET['c'] ?? '') === 'payroll' && ($_GET['a'] ?? '') !== 'monthly' ? 'active' : '' ?>"
             href="<?= url('payroll') ?>">
            <i class="bi bi-cash-coin"></i> 주간 급여
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($_GET['a'] ?? '') === 'monthly' ? 'active' : '' ?>"
             href="<?= url('payroll', 'monthly') ?>">
            <i class="bi bi-bar-chart-fill"></i> 월간 요약
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($_GET['c'] ?? '') === 'settings' ? 'active' : '' ?>"
             href="<?= url('settings') ?>">
            <i class="bi bi-gear-fill"></i> 설정
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
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

</main>

<footer class="bg-light border-top py-3 mt-5">
  <div class="container text-center text-muted small">
    <p class="mb-0">
      본 계산 결과는 참고용 예상 금액입니다.
      실제 임금 지급 전 근로계약서·최신 법령·전문가 확인을 권장합니다.
    </p>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
</body>
</html>
