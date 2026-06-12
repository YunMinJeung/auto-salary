<?php
$icon  = $action === 'clock_in' ? '✅' : '🏁';
$label = $action === 'clock_in' ? '출근' : '퇴근';
$color = $action === 'clock_in' ? 'var(--c-teal)' : 'var(--c-pink)';
?>
<div class="d-flex flex-column align-items-center justify-content-center"
     style="min-height:80vh; padding:2rem;">

  <?php if ($success): ?>
    <div style="font-size:5rem; line-height:1;"><?= $icon ?></div>
    <h2 class="mt-3 mb-1" style="color:<?= $color ?>; font-weight:800;">
        <?= $label ?> 완료
    </h2>
    <p class="text-muted mb-0"><?= h($store['store_name'] ?? '') ?></p>
    <p style="font-size:2.5rem; font-weight:700; color:var(--c-dark); margin-top:.5rem;">
        <?= h($now) ?>
    </p>
  <?php else: ?>
    <div style="font-size:5rem; line-height:1;">⚠️</div>
    <h2 class="mt-3 mb-1 text-danger">처리 실패</h2>
    <p class="text-muted">잠시 후 다시 시도해 주세요.</p>
  <?php endif; ?>

  <a href="<?= url('employee') ?>"
     class="btn btn-outline-secondary mt-4">
    홈으로
  </a>
</div>
