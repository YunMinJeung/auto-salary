<?php
$storeName  = h($store['store_name'] ?? '');
$userName   = h(Auth::user()['name'] ?? '');
$isWorking  = !empty($working);
$clockInAt  = $isWorking ? ($working['effective_clock_in_at'] ?? $working['original_clock_in_at'] ?? '') : '';
?>

<!-- 매장 헤더 -->
<div class="text-center mb-4 pt-2">
  <div style="font-size:2.2rem;line-height:1">🏪</div>
  <h5 class="fw-bold mt-2 mb-0" style="color:var(--c-dark)"><?= $storeName ?></h5>
  <p class="text-muted small mt-1 mb-0"><?= $userName ?> 님</p>
</div>

<!-- 현재 상태 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body text-center py-4">
    <?php if ($isWorking): ?>
      <div class="mb-2">
        <span class="badge rounded-pill px-3 py-2 fs-6" style="background:var(--c-teal);color:#fff">
          <i class="bi bi-circle-fill me-1" style="font-size:.55rem;vertical-align:middle;animation:blink 1.2s infinite"></i>
          근무 중
        </span>
      </div>
      <p class="text-muted small mb-0 mt-2">
        출근 시각: <strong><?= h(substr($clockInAt, 11, 5)) ?></strong>
      </p>
      <?php
        // 경과 시간
        $start   = strtotime($clockInAt);
        $elapsed = $start ? (time() - $start) : 0;
        $elH     = floor($elapsed / 3600);
        $elM     = floor(($elapsed % 3600) / 60);
      ?>
      <p class="text-muted small mt-1">
        경과: <strong><?= $elH ?>시간 <?= $elM ?>분</strong>
      </p>
    <?php else: ?>
      <div class="mb-2">
        <span class="badge rounded-pill px-3 py-2 fs-6 bg-secondary text-white">
          <i class="bi bi-moon-fill me-1"></i>미출근
        </span>
      </div>
      <p class="text-muted small mb-0 mt-2">아직 출근 기록이 없습니다.</p>
    <?php endif; ?>
  </div>
</div>

<!-- 출근 / 퇴근 버튼 -->
<?php if ($isWorking): ?>
<form method="POST" action="<?= BASE_URL ?>index.php?c=clock&a=clock_out">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= h($token) ?>">
  <button type="submit"
          class="btn btn-lg w-100 fw-bold py-3 mb-3"
          style="background:var(--c-pink);color:#212529;font-size:1.15rem"
          onclick="this.disabled=true;this.form.submit()">
    <i class="bi bi-box-arrow-right me-2"></i>퇴근하기
  </button>
</form>
<?php else: ?>
<form method="POST" action="<?= BASE_URL ?>index.php?c=clock&a=clock_in">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= h($token) ?>">
  <button type="submit"
          class="btn btn-lg w-100 fw-bold py-3 mb-3"
          style="background:var(--c-teal);color:#fff;font-size:1.15rem"
          onclick="this.disabled=true;this.form.submit()">
    <i class="bi bi-box-arrow-in-right me-2"></i>출근하기
  </button>
</form>
<?php endif; ?>

<a href="<?= url('employee') ?>" class="btn btn-outline-secondary w-100">
  <i class="bi bi-house me-1"></i>내 출퇴근 현황 보기
</a>

<style>
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }
</style>
