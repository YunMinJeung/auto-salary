<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? '페이클락') ?> — <?= h(Setting::get()['business_name']) ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>assets/css/app.css?v=2">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-white border-bottom main-navbar">
  <div class="container">
    <a class="navbar-brand" href="<?= url() ?>">
      <span class="brand-mark">
        <svg width="15" height="15" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
          <circle cx="8" cy="8" r="6.5" stroke="white" stroke-width="1.5"/>
          <line x1="8" y1="4.5" x2="8" y2="8" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
          <line x1="8" y1="8" x2="10.5" y2="9.5" stroke="white" stroke-width="1.5" stroke-linecap="round"/>
          <circle cx="8" cy="8" r="1" fill="white"/>
        </svg>
      </span>페이클락
    </a>
    <button class="navbar-toggler border-0" type="button"
            data-bs-toggle="collapse" data-bs-target="#navMenu">
      <i class="bi bi-list fs-4"></i>
    </button>
    <div class="collapse navbar-collapse" id="navMenu">
      <?php $c = $_GET['c'] ?? ''; $a = $_GET['a'] ?? ''; ?>
      <?php $isWork = in_array($c, ['work_logs', 'attendance', 'qr', 'attendance_change', 'schedules', 'leaves'], true); ?>
      <?php $isPay  = in_array($c, ['payroll', 'severance'], true); ?>
      <ul class="navbar-nav ms-auto gap-1 align-items-lg-center">
        <li class="nav-item">
          <a class="nav-link <?= ($c === '' || $c === 'dashboard') ? 'active' : '' ?>"
             href="<?= url() ?>">
            <i class="bi bi-grid-1x2 me-1"></i>대시보드
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= ($c === 'members' || $c === 'employees') ? 'active' : '' ?>"
             href="<?= url('members') ?>">
            <i class="bi bi-people me-1"></i>직원
          </a>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $isWork ? 'active' : '' ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-calendar3 me-1"></i>근무
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('work_logs') ?>"><i class="bi bi-calendar3 me-2 text-primary"></i>근무 기록</a></li>
            <li><a class="dropdown-item" href="<?= url('schedules') ?>"><i class="bi bi-calendar-week me-2 text-primary"></i>근무표</a></li>
            <li><a class="dropdown-item" href="<?= url('leaves') ?>"><i class="bi bi-calendar-heart me-2 text-primary"></i>연차/휴가</a></li>
            <li><a class="dropdown-item" href="<?= url('attendance') ?>"><i class="bi bi-door-open me-2 text-primary"></i>출퇴근 현황</a></li>
            <li><a class="dropdown-item" href="<?= url('qr') ?>"><i class="bi bi-qr-code me-2 text-primary"></i>QR 관리</a></li>
            <li><a class="dropdown-item" href="<?= url('attendance_change') ?>"><i class="bi bi-clock-history me-2 text-primary"></i>수정 요청</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle <?= $isPay ? 'active' : '' ?>"
             href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-cash-coin me-1"></i>급여
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="<?= url('payroll') ?>"><i class="bi bi-cash-coin me-2 text-primary"></i>주간 급여</a></li>
            <li><a class="dropdown-item" href="<?= url('payroll', 'monthly') ?>"><i class="bi bi-bar-chart-fill me-2 text-primary"></i>월간 요약</a></li>
            <li><a class="dropdown-item" href="<?= url('severance') ?>"><i class="bi bi-bank me-2 text-primary"></i>퇴직금</a></li>
          </ul>
        </li>
        <li class="nav-item">
          <a class="nav-link <?= $c === 'labor_risk' ? 'active' : '' ?>"
             href="<?= url('labor_risk') ?>">
            <i class="bi bi-shield-exclamation me-1"></i>리스크
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link nav-settings <?= $c === 'settings' ? 'active' : '' ?>"
             href="<?= url('settings') ?>" title="설정">
            <i class="bi bi-gear"></i>
          </a>
        </li>
        <?php if ($authUser = Auth::user()): ?>
        <?php if (Auth::isOwner()): ?>
        <?php
          $allStores      = Store::allByOwner(Auth::ownerId());
          $currentStoreId = Auth::storeId();
          $currentStore   = null;
          $otherStores    = [];
          foreach ($allStores as $s) {
              if ((int)$s['id'] === $currentStoreId) $currentStore = $s;
              else $otherStores[] = $s;
          }
        ?>
        <li class="nav-item ms-lg-1">
          <div class="dropdown">
            <button class="btn btn-store-switch dropdown-toggle" type="button"
                    data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-shop me-1"></i><?= h($currentStore['store_name'] ?? '매장') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <?php foreach ($otherStores as $s): ?>
              <li>
                <form method="POST" action="<?= url('store', 'switch') ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="store_id" value="<?= (int)$s['id'] ?>">
                  <button type="submit" class="dropdown-item">
                    <i class="bi bi-shop me-2 text-muted"></i><?= h($s['store_name']) ?>
                  </button>
                </form>
              </li>
              <?php endforeach; ?>
              <?php if (!empty($otherStores)): ?>
              <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li>
                <a class="dropdown-item" href="<?= url('store', 'create') ?>">
                  <i class="bi bi-plus-circle me-2 text-primary"></i>매장 추가
                </a>
              </li>
            </ul>
          </div>
        </li>
        <?php endif; ?>
        <li class="nav-item ms-lg-1 d-flex align-items-center gap-2">
          <span class="nav-username d-none d-lg-inline"><?= h($authUser['name']) ?></span>
          <a href="<?= url('auth', 'logout') ?>" class="btn btn-nav-logout">
            <i class="bi bi-box-arrow-right me-1"></i>로그아웃
          </a>
        </li>
        <?php endif; ?>
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
    본 계산 결과는 참고용 예상 금액입니다.
    실제 임금 지급 전 근로계약서·최신 법령·전문가 확인을 권장합니다.
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form').forEach(function (form) {
    form.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'BUTTON') {
        e.preventDefault();
      }
    });
  });
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el);
  });
});
</script>
</body>
</html>
