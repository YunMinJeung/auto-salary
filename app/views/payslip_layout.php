<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title ?? '급여명세서') ?></title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<style>
  :root {
    --c-dark:  #003844;
    --c-teal:  #006C67;
    --c-cream: #FFEBC6;
  }
  body { background:#f4f6f8; font-size:14px; }
  .payslip-wrap { max-width:780px; margin:0 auto; padding:1.5rem 1rem; }
  .payslip-doc  { background:#fff; border-radius:8px; box-shadow:0 2px 16px rgba(0,0,0,.08); padding:48px 52px; }
  .slip-head    { border-bottom:3px solid var(--c-dark); padding-bottom:16px; margin-bottom:24px; }
  .slip-title   { font-size:1.8rem; font-weight:800; letter-spacing:.1em; color:var(--c-dark); }
  .slip-table   { width:100%; border-collapse:collapse; font-size:.9rem; }
  .slip-table th,
  .slip-table td { padding:10px 12px; border-bottom:1px solid #e9ecef; }
  .slip-table thead th { background:var(--c-cream); font-weight:700; }
  .slip-table tfoot td { font-weight:700; border-top:2px solid var(--c-dark); background:#f8f9fa; }
  .sign-box     { border-top:2px solid var(--c-dark); margin-top:40px; padding-top:20px; }
  .sign-line    { border-bottom:1px solid #999; display:inline-block; width:180px; margin-top:24px; }
  .notice       { font-size:.78rem; color:#888; margin-top:28px; border-top:1px solid #eee; padding-top:12px; }
  @media print {
    body               { background:#fff !important; }
    .payslip-wrap      { padding:0 !important; }
    .payslip-doc       { box-shadow:none; border-radius:0; padding:24px 28px; }
    .no-print          { display:none !important; }
    a                  { text-decoration:none !important; }
  }
</style>
</head>
<body>

<div class="no-print py-2 px-3 d-flex align-items-center gap-2"
     style="background:var(--c-dark);position:sticky;top:0;z-index:100">
  <button onclick="window.print()" class="btn btn-sm btn-warning">
    <i class="bi bi-printer me-1"></i>인쇄 / PDF 저장
  </button>
  <a href="javascript:history.back()" class="btn btn-sm btn-outline-light">
    <i class="bi bi-arrow-left me-1"></i>돌아가기
  </a>
  <span class="text-white-50 small ms-auto"><?= h($title ?? '') ?></span>
</div>

<div class="payslip-wrap">
  <div class="payslip-doc">
    <?= $content ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
