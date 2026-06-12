<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? 'Admin') ?> — 페이클락 관리자</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+KR:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
:root {
  --admin-sidebar: #003844;
  --admin-sidebar-active: #006C67;
  --admin-accent: #006C67;
  --admin-bg: #f0f2f5;
}
body { background: var(--admin-bg); font-family: 'Noto Sans KR', sans-serif; }
.admin-wrap { display: flex; min-height: 100vh; }
.admin-sidebar {
  width: 240px; min-width: 240px; background: var(--admin-sidebar);
  display: flex; flex-direction: column; position: fixed; top: 0; left: 0; bottom: 0; overflow-y: auto; z-index: 100;
}
.admin-sidebar-brand {
  padding: 1.25rem 1.5rem; color: #fff; font-size: 1.1rem; font-weight: 700;
  border-bottom: 1px solid rgba(255,255,255,.1); text-decoration: none;
  display: flex; align-items: center; gap: .5rem;
}
.admin-sidebar-brand small { font-size: .68rem; font-weight: 400; opacity: .6; display: block; }
.admin-nav { flex: 1; padding: 1rem 0; }
.admin-nav-section {
  font-size: .68rem; text-transform: uppercase; letter-spacing: .08em;
  color: rgba(255,255,255,.4); padding: .5rem 1.5rem .25rem;
}
.admin-nav-link {
  display: flex; align-items: center; gap: .6rem;
  padding: .55rem 1.5rem; color: rgba(255,255,255,.75); text-decoration: none;
  font-size: .875rem; transition: background .15s;
}
.admin-nav-link:hover, .admin-nav-link.active {
  background: var(--admin-sidebar-active); color: #fff;
}
.admin-sidebar-footer {
  padding: 1rem 1.5rem; border-top: 1px solid rgba(255,255,255,.1);
  color: rgba(255,255,255,.5); font-size: .78rem;
}
.admin-main { margin-left: 240px; flex: 1; }
.admin-topbar {
  background: #fff; border-bottom: 1px solid #e5e7eb;
  padding: .75rem 2rem; display: flex; justify-content: space-between; align-items: center;
  position: sticky; top: 0; z-index: 50;
}
.admin-topbar-title { font-weight: 600; color: var(--admin-sidebar); font-size: 1rem; }
.admin-content { padding: 2rem; }
.stat-card {
  background: #fff; border-radius: 12px; padding: 1.25rem 1.5rem;
  box-shadow: 0 1px 3px rgba(0,0,0,.06); border: 1px solid #f0f0f0;
}
.stat-card-value { font-size: 2rem; font-weight: 700; color: var(--admin-sidebar); line-height: 1; }
.stat-card-label { font-size: .8rem; color: #6b7280; margin-top: .3rem; }
.stat-card-icon { font-size: 1.5rem; }
.admin-card {
  background: #fff; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.06);
  border: 1px solid #f0f0f0;
}
.admin-card-header {
  padding: 1rem 1.5rem; border-bottom: 1px solid #f0f0f0;
  font-weight: 600; color: var(--admin-sidebar); display: flex; justify-content: space-between; align-items: center;
}
.admin-card-body { padding: 1.25rem 1.5rem; }
.admin-table { width: 100%; font-size: .875rem; }
.admin-table th { background: #f8f9fa; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; padding: .75rem 1rem; }
.admin-table td { padding: .7rem 1rem; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
.admin-table tr:last-child td { border-bottom: 0; }
.admin-table tr:hover td { background: #fafafa; }
.badge { font-weight: 600; }
/* Status badges */
.badge-ACTIVE    { background: #dcfce7; color: #166534; }
.badge-TRIAL     { background: #dbeafe; color: #1d4ed8; }
.badge-PAYMENT_PENDING { background: #fef9c3; color: #854d0e; }
.badge-SUSPENDED { background: #fee2e2; color: #991b1b; }
.badge-CANCEL_REQUESTED { background: #fce7f3; color: #9d174d; }
.badge-CANCEL_SCHEDULED { background: #fce7f3; color: #9d174d; }
.badge-INACTIVE  { background: #f3f4f6; color: #6b7280; }
.badge-FREE      { background: #f3f4f6; color: #374151; }
.badge-STARTER   { background: #e0f2fe; color: #075985; }
.badge-BUSINESS  { background: #ede9fe; color: #5b21b6; }
.badge-PRO       { background: #fef3c7; color: #92400e; }
.badge-PAID      { background: #dcfce7; color: #166534; }
.badge-FAILED    { background: #fee2e2; color: #991b1b; }
.badge-CANCELLED { background: #f3f4f6; color: #6b7280; }
.badge-OPEN      { background: #dbeafe; color: #1d4ed8; }
.badge-IN_PROGRESS { background: #fef9c3; color: #854d0e; }
.badge-HOLD      { background: #f3f4f6; color: #6b7280; }
.badge-ANSWERED  { background: #dcfce7; color: #166534; }
.badge-CLOSED    { background: #f3f4f6; color: #6b7280; }
.badge-super_admin { background: #ede9fe; color: #5b21b6; }
.badge-admin     { background: #fef3c7; color: #92400e; }
.badge-owner     { background: #dbeafe; color: #1d4ed8; }
.badge-employee  { background: #f0fdf4; color: #166534; }
.badge-danger-sev  { background: #fee2e2; color: #991b1b; }
.badge-warning-sev { background: #fef9c3; color: #854d0e; }
.badge-info-sev    { background: #dbeafe; color: #1d4ed8; }
</style>
</head>
<body>
<div class="admin-wrap">

<!-- 사이드바 -->
<aside class="admin-sidebar">
  <a href="<?= url('admin') ?>" class="admin-sidebar-brand">
    <i class="bi bi-shield-fill-check"></i>
    <div>페이클락 <small>SUPER ADMIN</small></div>
  </a>
  <nav class="admin-nav">
    <?php $ac = $_GET['a'] ?? 'index'; ?>
    <div class="admin-nav-section">메인</div>
    <a class="admin-nav-link <?= ($ac === 'index' || $ac === '') ? 'active' : '' ?>" href="<?= url('admin') ?>">
      <i class="bi bi-grid-1x2-fill"></i>관리자 홈
    </a>

    <div class="admin-nav-section mt-2">사업장/사용자</div>
    <a class="admin-nav-link <?= in_array($ac, ['businesses','business_detail'], true) ? 'active' : '' ?>" href="<?= url('admin', 'businesses') ?>">
      <i class="bi bi-building"></i>사업장 관리
    </a>
    <a class="admin-nav-link <?= in_array($ac, ['users','user_detail'], true) ? 'active' : '' ?>" href="<?= url('admin', 'users') ?>">
      <i class="bi bi-people-fill"></i>사용자 관리
    </a>

    <div class="admin-nav-section mt-2">운영</div>
    <a class="admin-nav-link <?= in_array($ac, ['tickets','ticket_detail'], true) ? 'active' : '' ?>" href="<?= url('admin', 'tickets') ?>">
      <i class="bi bi-chat-left-text-fill"></i>문의/피드백
    </a>
    <a class="admin-nav-link <?= $ac === 'billing' ? 'active' : '' ?>" href="<?= url('admin', 'billing') ?>">
      <i class="bi bi-credit-card-fill"></i>구독/결제
    </a>

    <div class="admin-nav-section mt-2">시스템</div>
    <a class="admin-nav-link <?= $ac === 'logs' ? 'active' : '' ?>" href="<?= url('admin', 'logs') ?>">
      <i class="bi bi-list-ul"></i>시스템 로그
    </a>
    <a class="admin-nav-link <?= $ac === 'audit' ? 'active' : '' ?>" href="<?= url('admin', 'audit') ?>">
      <i class="bi bi-journal-check"></i>Audit Log
    </a>
    <a class="admin-nav-link <?= in_array($ac, ['standards','standard_form'], true) ? 'active' : '' ?>" href="<?= url('admin', 'standards') ?>">
      <i class="bi bi-sliders"></i>계산 기준
    </a>
  </nav>
  <div class="admin-sidebar-footer">
    <?= h(Auth::user()['name'] ?? '') ?><br>
    <a href="<?= url('auth', 'logout') ?>" class="text-danger text-decoration-none small">로그아웃</a>
    &nbsp;·&nbsp;
    <a href="<?= url() ?>" class="text-decoration-none small" style="color:rgba(255,255,255,.4)">앱으로</a>
  </div>
</aside>

<!-- 메인 -->
<div class="admin-main">
  <div class="admin-topbar">
    <div class="admin-topbar-title"><?= h($title ?? 'Admin') ?></div>
    <div class="small text-muted"><?= h(Auth::user()['email'] ?? '') ?></div>
  </div>
  <div class="admin-content">
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

</div><!-- .admin-wrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
