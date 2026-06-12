<div class="text-center py-5">
  <i class="bi bi-qr-code-scan" style="font-size:4rem;color:#ccc"></i>
  <h5 class="mt-4 mb-2 fw-bold text-danger">QR 출퇴근 오류</h5>
  <p class="text-muted"><?= h($message ?? '알 수 없는 오류가 발생했습니다.') ?></p>
  <?php if (Auth::check()): ?>
  <a href="<?= url('employee') ?>" class="btn btn-outline-secondary mt-3">
    <i class="bi bi-house me-1"></i>내 대시보드로
  </a>
  <?php else: ?>
  <a href="<?= url('auth','login') ?>" class="btn btn-outline-primary mt-3">
    <i class="bi bi-box-arrow-in-right me-1"></i>로그인
  </a>
  <?php endif; ?>
</div>
