<div class="container-fluid py-4" style="max-width:860px">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-qr-code me-2"></i>QR 출퇴근 관리</h4>
    <span class="text-muted small"><?= h($store['store_name'] ?? '') ?></span>
  </div>

  <?php if ($msg = flash('success')): ?>
  <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-1"></i><?= h($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>
  <?php if ($msg = flash('error')): ?>
  <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle me-1"></i><?= h($msg) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <div class="row g-4">

    <!-- QR 상태 카드 -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-header fw-semibold" style="background:var(--c-dark);color:#fff">
          <i class="bi bi-qr-code me-2"></i>현재 QR 코드
        </div>
        <div class="card-body text-center py-4">

          <?php if ($plainToken && $scanUrl): ?>
            <!-- QR 이미지 -->
            <div id="qr-container" class="mb-3" style="display:inline-block;padding:12px;background:#fff;border:2px solid var(--c-dark);border-radius:8px">
              <div id="qr-canvas"></div>
            </div>
            <div class="mb-3">
              <span class="badge bg-success fs-6 px-3 py-2">
                <i class="bi bi-check-circle me-1"></i>QR 활성
              </span>
            </div>
            <p class="text-muted small mb-4">
              <i class="bi bi-clock me-1"></i>발급 후 <strong>1시간</strong> 내에 저장하세요.
              이 페이지를 벗어나거나 만료되면 QR 이미지를 다시 볼 수 없습니다.
            </p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
              <a href="<?= url('qr','pdf') ?>" target="_blank" class="btn btn-outline-dark">
                <i class="bi bi-printer me-1"></i>인쇄용 PDF
              </a>
              <button id="btnDownload" class="btn btn-outline-secondary">
                <i class="bi bi-download me-1"></i>이미지 저장
              </button>
              <form method="POST" action="<?= url('qr','revoke') ?>"
                    onsubmit="return confirm('이 QR을 폐기하면 직원들이 해당 QR로 출퇴근할 수 없습니다.\n계속하시겠습니까?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger">
                  <i class="bi bi-trash me-1"></i>QR 폐기
                </button>
              </form>
            </div>

          <?php elseif ($activeToken): ?>
            <!-- 활성 토큰 있으나 세션 만료 -->
            <div class="py-3">
              <i class="bi bi-qr-code-scan" style="font-size:4rem;color:#ccc"></i>
              <p class="mt-3 text-muted">QR 코드가 발급되어 있으나<br>브라우저 세션이 만료되어 이미지를 표시할 수 없습니다.</p>
              <p class="small text-muted mb-4">새로 발급하거나 기존 QR을 계속 사용하세요.<br>직원들이 가진 기존 QR은 아직 유효합니다.</p>
              <div class="d-flex gap-2 justify-content-center">
                <form method="POST" action="<?= url('qr','generate') ?>">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn text-white" style="background:var(--c-teal)">
                    <i class="bi bi-arrow-clockwise me-1"></i>새 QR 발급 (기존 폐기)
                  </button>
                </form>
                <form method="POST" action="<?= url('qr','revoke') ?>"
                      onsubmit="return confirm('기존 QR을 폐기하시겠습니까?')">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-outline-danger">
                    <i class="bi bi-trash me-1"></i>폐기
                  </button>
                </form>
              </div>
            </div>

          <?php else: ?>
            <!-- 발급된 QR 없음 -->
            <div class="py-3">
              <i class="bi bi-qr-code" style="font-size:4rem;color:#ccc"></i>
              <p class="mt-3 text-muted">발급된 QR 코드가 없습니다.</p>
              <p class="small text-muted mb-4">QR 코드를 발급하면 직원들이 스마트폰으로<br>출퇴근을 기록할 수 있습니다.</p>
              <form method="POST" action="<?= url('qr','generate') ?>">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-lg text-white px-4" style="background:var(--c-teal)">
                  <i class="bi bi-qr-code me-2"></i>QR 발급하기
                </button>
              </form>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>

    <!-- 안내 카드 -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm mb-4">
        <div class="card-header fw-semibold bg-light">
          <i class="bi bi-info-circle me-2"></i>사용 방법
        </div>
        <div class="card-body small">
          <ol class="mb-0 ps-3" style="line-height:2">
            <li>QR 코드를 발급하고 <strong>인쇄</strong>하거나 이미지로 저장합니다.</li>
            <li>매장 카운터 등 잘 보이는 곳에 <strong>부착</strong>합니다.</li>
            <li>직원이 스마트폰 카메라로 QR을 <strong>스캔</strong>합니다.</li>
            <li>로그인 후 현재 상태 확인 → <strong>출근하기 / 퇴근하기</strong> 버튼 클릭.</li>
          </ol>
          <hr class="my-3">
          <ul class="mb-0 ps-3 text-muted" style="line-height:1.9">
            <li>QR은 <strong>매장 단위</strong>로 발급됩니다.</li>
            <li>QR 스캔 후 <strong>반드시 로그인</strong>이 필요합니다.</li>
            <li>이 매장에 등록되지 않은 직원은 출퇴근할 수 없습니다.</li>
            <li>출퇴근 시각은 <strong>서버 시간</strong>으로 기록됩니다.</li>
          </ul>
        </div>
      </div>

      <!-- QR 보안 -->
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold bg-light">
          <i class="bi bi-shield-check me-2"></i>보안
        </div>
        <div class="card-body small text-muted">
          <ul class="mb-0 ps-3" style="line-height:1.9">
            <li>QR이 유출되더라도 직원 로그인 없이는 출퇴근 처리가 <strong>불가</strong>합니다.</li>
            <li>QR을 폐기하면 즉시 모든 스캔이 차단됩니다.</li>
            <li>DB에는 QR 토큰 해시값만 저장됩니다 (평문 저장 없음).</li>
            <li>모든 스캔 시도는 로그에 기록됩니다.</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- 발급 이력 -->
    <?php if ($history): ?>
    <div class="col-12">
      <div class="card border-0 shadow-sm">
        <div class="card-header fw-semibold bg-light">
          <i class="bi bi-clock-history me-2"></i>발급 이력 (최근 10건)
        </div>
        <div class="card-body p-0">
          <table class="table table-sm mb-0">
            <thead class="table-light">
              <tr><th>발급일시</th><th>상태</th><th>폐기일시</th></tr>
            </thead>
            <tbody>
              <?php foreach ($history as $h): ?>
              <tr>
                <td class="small"><?= h(substr($h['created_at'], 0, 16)) ?></td>
                <td>
                  <?php if ($h['is_active']): ?>
                  <span class="badge bg-success">활성</span>
                  <?php else: ?>
                  <span class="badge bg-secondary">폐기됨</span>
                  <?php endif; ?>
                </td>
                <td class="small text-muted"><?= $h['revoked_at'] ? h(substr($h['revoked_at'], 0, 16)) : '—' ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </div><!-- /row -->
</div>

<?php if ($plainToken && $scanUrl): ?>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function () {
  var scanUrl = <?= json_encode($scanUrl) ?>;

  var qr = new QRCode(document.getElementById('qr-canvas'), {
    text:         scanUrl,
    width:        240,
    height:       240,
    correctLevel: QRCode.CorrectLevel.M,
  });

  // 이미지 다운로드
  document.getElementById('btnDownload').addEventListener('click', function () {
    var canvas = document.querySelector('#qr-canvas canvas');
    if (!canvas) {
      // QRCode.js가 img 태그로 그릴 수도 있음
      var img = document.querySelector('#qr-canvas img');
      if (img) {
        var a = document.createElement('a');
        a.href = img.src;
        a.download = 'qr_attendance.png';
        a.click();
      }
      return;
    }
    var a = document.createElement('a');
    a.href = canvas.toDataURL('image/png');
    a.download = 'qr_attendance.png';
    a.click();
  });
})();
</script>
<?php endif; ?>
