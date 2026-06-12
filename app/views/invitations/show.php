<div class="d-flex align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-check-circle-fill me-2 text-success"></i>초대 링크 생성 완료</h1>
</div>

<div style="max-width:600px">
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-4">
      <p class="fw-semibold mb-2">
        <i class="bi bi-person me-1"></i><?= h($invName) ?>님 초대 링크
      </p>
      <input type="text" class="form-control font-monospace mb-2"
             value="<?= h($invLink) ?>" readonly onclick="this.select()" id="invLink">
      <div class="d-flex gap-2 mb-3">
        <button class="btn btn-outline-primary btn-sm" onclick="copyLink()">
          <i class="bi bi-clipboard me-1"></i>링크 복사
        </button>
      </div>
      <div class="text-muted small">
        <i class="bi bi-clock me-1"></i>7일 이내 유효합니다. 직원에게 링크를 직접 공유하세요.
      </div>
    </div>
  </div>

  <div class="mb-4">
    <h6 class="fw-semibold mb-2">QR 코드</h6>
    <div class="border rounded p-3 text-center bg-white" style="max-width:220px">
      <canvas id="qrCanvas"></canvas>
    </div>
    <div class="text-muted small mt-2">직원이 휴대폰으로 QR을 스캔할 수 있습니다.</div>
  </div>

  <div class="alert alert-warning small mb-4">
    <i class="bi bi-exclamation-triangle me-1"></i>
    <strong>안내:</strong> 직원이 링크에 접속해 본인 계정을 직접 만들거나 로그인해야 합니다.
    사장님이 대신 가입하거나 비밀번호를 설정하면 안 됩니다.
  </div>

  <div class="d-flex gap-2">
    <a href="<?= url('members') ?>" class="btn btn-primary">직원 목록으로</a>
    <a href="<?= url('invite', 'form') ?>" class="btn btn-outline-secondary">새 초대 만들기</a>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
(function() {
  var link = <?= json_encode($invLink) ?>;
  new QRCode(document.getElementById('qrCanvas'), {
    text: link, width: 180, height: 180,
    colorDark: '#003844', colorLight: '#ffffff',
    correctLevel: QRCode.CorrectLevel.M
  });
})();
function copyLink() {
  var el = document.getElementById('invLink');
  el.select();
  document.execCommand('copy');
  var btn = event.currentTarget;
  var orig = btn.innerHTML;
  btn.innerHTML = '<i class="bi bi-check me-1"></i>복사됨';
  setTimeout(function(){ btn.innerHTML = orig; }, 1800);
}
</script>
