<?php $storeName = h($store['store_name'] ?? ''); ?>

<style>
.qr-print-wrap { text-align:center; padding:20px 0; }
.qr-print-wrap h2 { font-size:1.6rem; font-weight:800; color:var(--c-dark); margin-bottom:4px; }
.qr-print-wrap .subtitle { color:#666; font-size:.95rem; margin-bottom:24px; }
#qr-print-canvas { display:inline-block; padding:16px; border:3px solid var(--c-dark); border-radius:8px; background:#fff; }
.qr-instructions { margin:24px auto 0; max-width:320px; text-align:left; font-size:.9rem; color:#555; }
.qr-instructions li { margin-bottom:6px; }
</style>

<div class="qr-print-wrap">
  <h2><?= $storeName ?></h2>
  <p class="subtitle">QR 출퇴근 코드 &mdash; 카메라로 스캔하세요</p>

  <div id="qr-print-canvas"></div>

  <ol class="qr-instructions">
    <li>스마트폰 카메라 앱으로 QR 코드를 스캔합니다.</li>
    <li>앱 계정으로 <strong>로그인</strong>합니다.</li>
    <li><strong>출근하기</strong> 또는 <strong>퇴근하기</strong> 버튼을 누릅니다.</li>
  </ol>
</div>

<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
new QRCode(document.getElementById('qr-print-canvas'), {
  text:         <?= json_encode($scanUrl) ?>,
  width:        280,
  height:       280,
  correctLevel: QRCode.CorrectLevel.M,
});
</script>
