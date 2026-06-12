<?php /* 내 수입 — 알바 전용 */ ?>

<!-- 상단 헤더 카드: 이번 달 예상 수입 합계 -->
<div class="card border-0 shadow-sm mb-3"
     style="background:var(--c-dark);color:#fff;border-radius:16px;">
  <div class="card-body p-4">
    <div class="small opacity-75 mb-1">
      <?= $nowY ?>년 <?= $nowM ?>월 예상 수입 합계
      <span class="ms-1 badge bg-light text-dark small">공개 사업장 기준</span>
    </div>
    <div style="font-size:2.2rem;font-weight:800;letter-spacing:-1px">
      <?= formatWon($visibleTotal) ?>
    </div>
    <?php if ($hasHidden): ?>
    <div class="small mt-2 opacity-75">
      <i class="bi bi-eye-slash me-1"></i>일부 사업장 예상 급여 미포함
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- 사업장별 카드 -->
<?php foreach ($storeCards as $card): ?>
<div class="card border-0 shadow-sm mb-3" style="border-radius:12px;">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div>
        <div class="fw-bold" style="color:var(--c-dark)"><?= h($card['store_name']) ?></div>
        <div class="text-muted small"><?= h($card['member']['name']) ?></div>
      </div>
      <div class="text-end">
        <div class="small text-muted">이번 달 근무</div>
        <div class="fw-semibold"><?= minutesToHoursStr($card['total_work_minutes']) ?></div>
      </div>
    </div>

    <?php if (!empty($card['ins_needs_check'])): ?>
    <div class="text-muted small mb-2">
      <i class="bi bi-exclamation-triangle me-1 text-warning"></i>고용보험 공제 여부: 확인 중<br>
      예상 실지급액은 사업장 확인 후 달라질 수 있습니다.
    </div>
    <?php endif; ?>

    <?php if ($card['visibility'] === 'HOURS_ONLY'): ?>
    <div class="alert alert-light border small py-2 mb-0">
      <i class="bi bi-lock-fill me-1 text-muted"></i>
      예상 급여는 사업장 설정에 따라 표시되지 않습니다.
      확정 급여는 급여명세서에서 확인할 수 있어요.
    </div>

    <?php elseif ($card['visibility'] === 'ESTIMATED_TOTAL_ONLY'): ?>
    <div class="d-flex justify-content-between align-items-center py-2 border-top">
      <span class="text-muted small">이번 달 예상 급여</span>
      <span class="fw-bold fs-5" style="color:var(--c-teal)">
        <?= $card['estimated_pay'] !== null ? formatWon($card['estimated_pay']) : '-' ?>
      </span>
    </div>
    <div class="small text-muted mt-1">
      <i class="bi bi-info-circle me-1"></i>
      예상 급여는 현재 등록된 근무기록 기준이며, 실제 지급액과 다를 수 있습니다.
    </div>

    <?php elseif ($card['visibility'] === 'ESTIMATED_WITH_BREAKDOWN'): ?>
    <?php $bd = $card['pay_breakdown']; ?>
    <div class="border-top pt-2">
      <?php if ($bd): ?>
      <div class="d-flex justify-content-between small py-1">
        <span class="text-muted">기본급</span>
        <span><?= formatWon($bd['base_pay']) ?></span>
      </div>
      <?php if ($bd['weekly_holiday_pay'] > 0): ?>
      <div class="d-flex justify-content-between small py-1">
        <span class="text-muted">주휴수당</span>
        <span><?= formatWon($bd['weekly_holiday_pay']) ?></span>
      </div>
      <?php endif; ?>
      <?php $premTotal = ($bd['night_premium'] ?? 0) + ($bd['overtime_premium'] ?? 0) + ($bd['holiday_premium'] ?? 0); ?>
      <?php if ($premTotal > 0): ?>
      <div class="d-flex justify-content-between small py-1">
        <span class="text-muted">야간·연장·휴일수당</span>
        <span><?= formatWon($premTotal) ?></span>
      </div>
      <?php endif; ?>
      <div class="d-flex justify-content-between small py-1 border-top mt-1">
        <span class="fw-semibold">세전 합계</span>
        <span class="fw-semibold"><?= formatWon($card['estimated_pay']) ?></span>
      </div>
      <?php if ($bd['deductions_total'] > 0): ?>
      <div class="d-flex justify-content-between small py-1 text-danger">
        <span>4대보험 공제(예상)</span>
        <span>-<?= formatWon($bd['deductions_total']) ?></span>
      </div>
      <div class="d-flex justify-content-between py-1 border-top mt-1">
        <span class="fw-bold">예상 실수령액</span>
        <span class="fw-bold fs-5" style="color:var(--c-teal)"><?= formatWon($bd['net_pay']) ?></span>
      </div>
      <?php else: ?>
      <div class="d-flex justify-content-between py-1 border-top mt-1">
        <span class="fw-bold">예상 급여</span>
        <span class="fw-bold fs-5" style="color:var(--c-teal)"><?= formatWon($card['estimated_pay']) ?></span>
      </div>
      <?php endif; ?>
      <?php else: ?>
      <div class="text-muted small py-2">이번 달 근무기록이 없습니다.</div>
      <?php endif; ?>
      <div class="small text-muted mt-2">
        <i class="bi bi-info-circle me-1"></i>현재 등록된 근무기록 기준 예상액이며, 실제 지급액과 다를 수 있습니다.
      </div>
    </div>
    <?php endif; ?>

    <?php if ($card['last_month_pay'] > 0): ?>
    <div class="d-flex justify-content-between align-items-center pt-2 mt-2 border-top">
      <span class="text-muted small">지난달 확정 급여</span>
      <span class="fw-semibold"><?= formatWon($card['last_month_pay']) ?></span>
    </div>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>

<?php if ($hasHidden): ?>
<div class="small text-muted text-center mb-3">
  <i class="bi bi-eye-slash me-1"></i>
  일부 사업장은 예상 급여를 공개하지 않아 합계에서 제외되었습니다.
</div>
<?php endif; ?>

<!-- 월별 수입 추이 차트 -->
<?php if (!empty($monthlyTrend)): ?>
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
  <div class="card-body">
    <div class="fw-semibold mb-3" style="color:var(--c-dark)">
      <i class="bi bi-bar-chart-fill me-1"></i>월별 확정 수입 추이
    </div>
    <canvas id="incomeTrendChart" height="180"></canvas>
  </div>
</div>

<!-- 급여명세서 바로가기 -->
<div class="card border-0 shadow-sm mb-4" style="border-radius:12px;">
  <div class="card-body">
    <div class="fw-semibold mb-2" style="color:var(--c-dark)">
      <i class="bi bi-file-earmark-text me-1"></i>급여명세서
    </div>
    <a href="<?= url('employee', 'payslip') ?>" class="btn btn-outline-secondary btn-sm w-100">
      이번 달 급여명세서 보기
    </a>
  </div>
</div>
<?php endif; ?>

<?php
$labels  = json_encode(array_column($monthlyTrend, 'label'));
$amounts = json_encode(array_column($monthlyTrend, 'total'));
$extraJs = <<<JS
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function() {
  var ctx = document.getElementById('incomeTrendChart');
  if (!ctx) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: {$labels},
      datasets: [{
        label: '확정 수입',
        data: {$amounts},
        backgroundColor: 'rgba(0,108,103,0.7)',
        borderRadius: 6,
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display: false } },
      scales: {
        y: {
          beginAtZero: true,
          ticks: { callback: function(v) { return '₩' + v.toLocaleString(); } }
        }
      }
    }
  });
})();
</script>
JS;
?>
