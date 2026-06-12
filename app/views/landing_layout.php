<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? '페이클락') ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link rel="stylesheet" href="<?= BASE_URL ?>css/landing.css">
</head>
<body class="landing">

<header class="landing-header">
  <div class="container">
    <a href="<?= url('home') ?>" class="landing-logo">
      <i class="bi bi-calculator-fill"></i>페이클락
    </a>

    <nav class="landing-nav d-none d-lg-flex">
      <a href="<?= url('home') ?>#features">기능 소개</a>
      <a href="<?= url('home') ?>#how">이용 방법</a>
      <a href="<?= url('home') ?>#pricing">요금제</a>
      <a href="<?= url('home') ?>#faq">FAQ</a>
    </nav>

    <div class="d-flex align-items-center gap-2">
      <a href="<?= url('auth', 'login') ?>" class="landing-login">로그인</a>
      <div class="dropdown">
        <button class="btn-cta dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
          무료로 시작하기
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3 py-2 mt-1">
          <li><a class="dropdown-item py-2 px-3" href="<?= url('signup', 'owner') ?>">
            <div class="d-flex align-items-center gap-2">
              <div style="width:36px;height:36px;border-radius:8px;background:#DDF7F2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-shop text-teal"></i>
              </div>
              <div>
                <div class="fw-700" style="font-weight:700;font-size:.875rem">사장님으로 시작</div>
                <div class="text-muted" style="font-size:.775rem">사업장 만들고 직원 관리</div>
              </div>
            </div>
          </a></li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item py-2 px-3" href="<?= url('signup', 'employee') ?>">
            <div class="d-flex align-items-center gap-2">
              <div style="width:36px;height:36px;border-radius:8px;background:#FCE7F3;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <i class="bi bi-person-badge text-pink"></i>
              </div>
              <div>
                <div class="fw-700" style="font-weight:700;font-size:.875rem">알바로 시작</div>
                <div class="text-muted" style="font-size:.775rem">초대받은 사업장에 연결</div>
              </div>
            </div>
          </a></li>
        </ul>
      </div>
    </div>
  </div>
</header>

<?php if ($msg = flash('success')): ?>
<div class="container mt-3">
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-1"></i><?= h($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>
<?php if ($msg = flash('error')): ?>
<div class="container mt-3">
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-1"></i><?= h($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
</div>
<?php endif; ?>

<main>
  <?= $content ?>
</main>

<footer class="landing-footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-brand">
        <div class="logo"><i class="bi bi-calculator-fill"></i>페이클락</div>
        <p>알바 근태·급여 관리를 자동화해<br>사장님과 알바님 모두 편하게.</p>
      </div>
      <div class="footer-links">
        <a href="<?= url('terms') ?>">이용약관</a>
        <a href="<?= url('privacy') ?>">개인정보처리방침</a>
        <a href="mailto:cymmcymm@gmail.com">고객문의</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> 페이클락. All rights reserved.</span>
      <span>cymmcymm@gmail.com</span>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
