<?php
/**
 * QR 스캔 확인 화면 (GET).
 * GET 요청만으로는 출퇴근이 처리되지 않으며, 이 화면에서 POST 확인을 거쳐야 한다.
 */
$storeName = h($store['store_name'] ?? '');
$userName  = h(Auth::user()['name'] ?? '');
$isWorking = ($action === 'clock_out');
$clockInAt = $isWorking ? ($working['effective_clock_in_at'] ?? $working['original_clock_in_at'] ?? '') : '';
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
          근무 중
        </span>
      </div>
      <p class="text-muted small mb-0 mt-2">
        출근 시각: <strong><?= h(substr((string)$clockInAt, 11, 5)) ?></strong>
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

<!-- 확인 폼 (POST) -->
<form method="POST" action="<?= BASE_URL ?>index.php?c=clock&a=scan&token=<?= h(urlencode($token)) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= h($token) ?>">
  <input type="hidden" name="latitude"  id="hidLat">
  <input type="hidden" name="longitude" id="hidLng">
  <input type="hidden" name="accuracy"  id="hidAcc">
  <input type="hidden" name="geo_error" id="hidGeoErr">
  <?php if ($isWorking): ?>
    <p class="text-center fw-bold mb-3" style="color:var(--c-dark)">퇴근하시겠습니까?</p>
    <button type="submit" id="clockBtn"
            class="btn btn-lg w-100 fw-bold py-3 mb-3"
            style="background:var(--c-pink);color:#212529;font-size:1.15rem">
      <i class="bi bi-box-arrow-right me-2"></i>퇴근하기
    </button>
  <?php else: ?>
    <p class="text-center fw-bold mb-3" style="color:var(--c-dark)">출근하시겠습니까?</p>
    <button type="submit" id="clockBtn"
            class="btn btn-lg w-100 fw-bold py-3 mb-3"
            style="background:var(--c-teal);color:#fff;font-size:1.15rem">
      <i class="bi bi-box-arrow-in-right me-2"></i>출근하기
    </button>
  <?php endif; ?>
</form>

<script>
(function() {
  var btn = document.getElementById('clockBtn');
  if (!btn) return;
  btn.addEventListener('click', function(e) {
    e.preventDefault();
    var form = btn.closest('form');
    if (!navigator.geolocation) {
      document.getElementById('hidGeoErr').value = 'unsupported';
      form.submit(); return;
    }
    btn.disabled = true;
    btn.textContent = '위치 확인 중...';
    navigator.geolocation.getCurrentPosition(
      function(pos) {
        document.getElementById('hidLat').value = pos.coords.latitude;
        document.getElementById('hidLng').value = pos.coords.longitude;
        document.getElementById('hidAcc').value = pos.coords.accuracy;
        form.submit();
      },
      function(err) {
        document.getElementById('hidGeoErr').value = err.code;
        form.submit();
      },
      { enableHighAccuracy: true, timeout: 8000 }
    );
  });
})();
</script>

<a href="<?= url('employee') ?>" class="btn btn-outline-secondary w-100">
  <i class="bi bi-house me-1"></i>내 출퇴근 현황 보기
</a>
