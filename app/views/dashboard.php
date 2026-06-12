<?php
$settings   = $settings   ?? Setting::get();
$employees  = $employees  ?? [];
$recentLogs = $recentLogs ?? [];
$todayAttendance   = $todayAttendance   ?? [];
$todayWorkingCount = $todayWorkingCount ?? 0;
$alertCounts = $alertCounts ?? ['danger' => 0, 'warning' => 0, 'info' => 0];
$pendingChangeCnt     = $pendingChangeCnt     ?? 0;
$correctionPendingCnt = $correctionPendingCnt ?? 0;
$objectionCount       = $objectionCount       ?? 0;
$monthlyPayrollTrend  = $monthlyPayrollTrend  ?? [];
$weeklyHours          = $weeklyHours          ?? [];

$riskTotal = (int)($alertCounts['danger'] ?? 0)
           + (int)($alertCounts['warning'] ?? 0)
           + (int)($alertCounts['info'] ?? 0);
$needCheck = $riskTotal + (int)$pendingChangeCnt + (int)$correctionPendingCnt + (int)$objectionCount;
?>

<!-- ── 히어로 ─────────────────────────────────────────── -->
<section class="store-hero">
  <div>
    <h1><?= h($settings['business_name'] ?? '내 매장') ?> 대시보드</h1>
    <div class="hero-badges">
      <span class="hero-badge"><i class="bi bi-calendar3"></i><?= date('Y년 n월 j일') ?></span>
      <span class="hero-badge"><i class="bi bi-building"></i><?= ($settings['employee_count_type'] ?? '') === 'over5' ? '5인 이상 사업장' : '5인 미만 사업장' ?></span>
      <span class="hero-badge"><i class="bi bi-currency-won"></i>최저시급 <?= number_format((int)($settings['minimum_wage'] ?? 0)) ?>원</span>
    </div>
  </div>
  <div class="hero-actions">
    <a href="<?= url('qr') ?>" class="btn btn-secondary btn-sm">
      <i class="bi bi-qr-code me-1"></i>QR 출퇴근
    </a>
    <a href="<?= url('work_logs', 'create') ?>" class="btn btn-primary btn-sm">
      <i class="bi bi-plus me-1"></i>근무 기록 추가
    </a>
  </div>
</section>

<!-- ── 핵심 지표 카드 4개 ──────────────────────────────── -->
<div class="summary-grid" style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem;">

  <a href="<?= url('members') ?>" class="summary-card" style="text-decoration:none;color:inherit">
    <div class="summary-icon-wrap icon-people">
      <i class="bi bi-people-fill"></i>
    </div>
    <div class="summary-value"><?= count($employees) ?><span class="summary-unit">명</span></div>
    <div class="summary-label">재직 직원</div>
    <div class="summary-caption">현재 등록 직원</div>
  </a>

  <a href="<?= url('attendance') ?>" class="summary-card" style="text-decoration:none;color:inherit">
    <div class="summary-icon-wrap icon-clock">
      <i class="bi bi-door-open-fill"></i>
    </div>
    <div class="summary-value <?= $todayWorkingCount > 0 ? 'success' : '' ?>"><?= (int)$todayWorkingCount ?><span class="summary-unit">명</span></div>
    <div class="summary-label">오늘 근무</div>
    <div class="summary-caption">현재 출근 중</div>
  </a>

  <a href="<?= url('labor_risk') ?>" class="summary-card" style="text-decoration:none;color:inherit">
    <div class="summary-icon-wrap icon-alert">
      <i class="bi bi-bell-fill"></i>
    </div>
    <div class="summary-value <?= $needCheck > 0 ? 'danger' : '' ?>"><?= $needCheck ?><span class="summary-unit">건</span></div>
    <div class="summary-label">확인 필요</div>
    <div class="summary-caption">리스크 · 수정요청 · 이의</div>
  </a>

  <a href="<?= url('payroll') ?>" class="summary-card" style="text-decoration:none;color:inherit">
    <div class="summary-icon-wrap icon-payroll">
      <i class="bi bi-cash-coin"></i>
    </div>
    <div class="summary-value" style="font-size:1.2rem;letter-spacing:0;color:var(--primary);font-weight:800;">
      바로가기 →
    </div>
    <div class="summary-label">급여 계산</div>
    <div class="summary-caption">주간 · 월간 급여</div>
  </a>

</div>

<!-- ── 본문 2열 ────────────────────────────────────────── -->
<div class="dashboard-main-grid">

  <!-- 오늘 출퇴근 현황 -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-clock-fill me-1"></i>오늘 출퇴근 현황</span>
      <a href="<?= url('attendance') ?>" class="btn btn-sm btn-outline-primary">전체 보기</a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($todayAttendance)): ?>
      <div class="empty-state">
        <i class="bi bi-door-closed empty-state-icon"></i>
        <p class="empty-state-title">아직 출근 기록이 없어요</p>
        <p class="empty-state-desc">직원이 QR 또는 앱으로 출근하면 여기에 실시간으로 표시됩니다.</p>
      </div>
      <?php else: ?>
      <?php foreach ($todayAttendance as $log):
        $statusBadge = match($log['status'] ?? '') {
          'working'   => ['class' => 'badge-working', 'label' => '근무 중'],
          'completed', 'corrected' => ['class' => 'badge-done',    'label' => '퇴근 완료'],
          default     => ['class' => 'badge-check',   'label' => '확인 필요'],
        };
        $inAt  = $log['effective_clock_in_at']  ?? $log['original_clock_in_at']  ?? null;
        $outAt = $log['effective_clock_out_at'] ?? $log['original_clock_out_at'] ?? null;
        $name  = $log['member_name'] ?? '';
        $initial = mb_substr($name, 0, 1);
      ?>
      <div class="attendance-item">
        <div class="attendance-avatar"><?= h($initial) ?></div>
        <div class="flex-grow-1">
          <div class="attendance-name"><?= h($name) ?></div>
          <div class="attendance-time">
            <?= $inAt ? h(date('H:i', strtotime($inAt))) : '—' ?>
            ~ <?= $outAt ? h(date('H:i', strtotime($outAt))) : '근무 중' ?>
          </div>
        </div>
        <div class="attendance-right">
          <span class="status-badge <?= $statusBadge['class'] ?>"><?= h($statusBadge['label']) ?></span>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- 확인 필요 항목 -->
  <div class="card">
    <div class="card-header">
      <i class="bi bi-bell-fill me-1"></i>확인 필요한 항목
    </div>
    <div class="card-body p-0">
      <?php if ($needCheck === 0): ?>
      <div class="empty-state">
        <i class="bi bi-check-circle empty-state-icon" style="color:#86EFAC"></i>
        <p class="empty-state-title">모두 확인됐어요</p>
        <p class="empty-state-desc">처리해야 할 항목이 없습니다.</p>
      </div>
      <?php else: ?>
      <?php if ($riskTotal > 0): ?>
      <div class="check-item">
        <span class="check-item-text">
          <strong class="text-danger"><?= $riskTotal ?>건</strong> 노무 리스크
        </span>
        <a href="<?= url('labor_risk') ?>" class="btn btn-sm btn-outline-danger">확인</a>
      </div>
      <?php endif; ?>
      <?php if ($correctionPendingCnt): ?>
      <div class="check-item">
        <span class="check-item-text">
          <strong class="text-warning"><?= (int)$correctionPendingCnt ?>건</strong> 근무 수정 요청
        </span>
        <a href="<?= url('attendance', 'corrections') ?>" class="btn btn-sm btn-outline-primary">확인</a>
      </div>
      <?php endif; ?>
      <?php if ($objectionCount): ?>
      <div class="check-item">
        <span class="check-item-text">
          <strong class="text-danger"><?= (int)$objectionCount ?>건</strong> 직원 이의제기
        </span>
        <a href="<?= url('attendance_change', 'objections') ?>" class="btn btn-sm btn-outline-danger">확인</a>
      </div>
      <?php endif; ?>
      <?php if ($pendingChangeCnt): ?>
      <div class="check-item">
        <span class="check-item-text">
          <strong class="text-warning"><?= (int)$pendingChangeCnt ?>건</strong> 출퇴근 수정 대기
        </span>
        <a href="<?= url('attendance_change') ?>" class="btn btn-sm btn-outline-primary">확인</a>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ── 하단 2열 ────────────────────────────────────────── -->
<div class="dashboard-bottom-grid">

  <!-- 재직 중 직원 -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-people-fill me-1"></i>재직 중 직원</span>
      <a href="<?= url('members', 'add') ?>" class="btn btn-primary btn-sm">
        <i class="bi bi-plus me-1"></i>직원 추가
      </a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($employees)): ?>
      <div class="empty-state">
        <i class="bi bi-person-plus empty-state-icon"></i>
        <p class="empty-state-title">등록된 직원이 없어요</p>
        <p class="empty-state-desc">직원 추가 버튼을 눌러 첫 번째 직원을 등록해보세요.</p>
      </div>
      <?php else: ?>
      <div class="employee-card-grid">
        <?php foreach ($employees as $emp):
          $initial = mb_substr($emp['name'] ?? '?', 0, 1);
          $accountStatus = $emp['account_status'] ?? '';
        ?>
        <div class="employee-mini-card">
          <div class="employee-avatar"><?= h($initial) ?></div>
          <div class="flex-grow-1">
            <div class="employee-info-name"><?= h($emp['name'] ?? '') ?></div>
            <div class="employee-info-wage">시급 <?= number_format((int)($emp['hourly_wage'] ?? 0)) ?>원</div>
          </div>
          <div class="employee-card-right">
            <?php if ($accountStatus === 'linked'): ?>
              <span class="status-badge badge-linked">연결 완료</span>
            <?php elseif ($accountStatus === 'invited'): ?>
              <span class="status-badge badge-invited">초대 대기</span>
            <?php else: ?>
              <span class="status-badge badge-noaccount">계정 없음</span>
            <?php endif; ?>
            <a href="<?= url('members', 'edit', ['id' => $emp['id']]) ?>"
               class="btn btn-xs btn-outline-secondary">상세</a>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 최근 근무 기록 -->
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span><i class="bi bi-clock-history me-1"></i>최근 근무 기록</span>
      <a href="<?= url('work_logs') ?>" class="btn btn-sm btn-outline-primary">전체 보기</a>
    </div>
    <div class="card-body p-0">
      <?php if (empty($recentLogs)): ?>
      <div class="empty-state">
        <i class="bi bi-calendar-x empty-state-icon"></i>
        <p class="empty-state-title">근무 기록이 없어요</p>
        <p class="empty-state-desc">근무 기록을 추가하면 여기서 확인할 수 있습니다.</p>
      </div>
      <?php else: ?>
      <?php foreach (array_slice($recentLogs, 0, 8) as $log):
        $isAbsent = !empty($log['is_absent']);
        $startStr = substr((string)($log['start_time'] ?? ''), 0, 5);
        $endStr   = substr((string)($log['end_time']   ?? ''), 0, 5);
        // 근무 시간 계산 (분)
        $durationStr = '';
        if (!$isAbsent && $startStr && $endStr) {
          $s = strtotime('2000-01-01 ' . $startStr);
          $e = strtotime('2000-01-01 ' . $endStr);
          if ($e > $s) {
            $mins = (int)(($e - $s) / 60);
            $h    = intdiv($mins, 60);
            $m    = $mins % 60;
            $durationStr = $h > 0 ? "{$h}시간" . ($m > 0 ? " {$m}분" : '') : "{$m}분";
          }
        }
      ?>
      <div class="work-log-item">
        <div class="work-log-dot <?= $isAbsent ? 'absent' : '' ?>"></div>
        <div class="flex-grow-1">
          <div>
            <span class="work-log-name"><?= h($log['employee_name'] ?? '') ?></span>
            <span class="work-log-date"><?= h($log['work_date'] ?? '') ?>(<?= dayOfWeekKo($log['work_date'] ?? '') ?>)</span>
          </div>
          <div class="work-log-time">
            <?php if ($isAbsent): ?>
              <span class="status-badge badge-missing">결근</span>
            <?php else: ?>
              <span><?= h($startStr) ?> ~ <?= h($endStr) ?></span>
              <?php if ($durationStr): ?>
                <span class="work-log-duration"><?= $durationStr ?></span>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ── 차트 섹션 ───────────────────────────────────────── -->
<?php if (!empty($monthlyPayrollTrend) || !empty($weeklyHours)): ?>
<div class="row g-4 mt-2">
  <?php if (!empty($monthlyPayrollTrend)): ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-bar-chart-fill me-1"></i>월별 인건비 추이</div>
      <div class="card-body"><canvas id="chartMonthly" height="200"></canvas></div>
    </div>
  </div>
  <?php endif; ?>
  <?php if (!empty($weeklyHours)): ?>
  <div class="col-md-6">
    <div class="card">
      <div class="card-header"><i class="bi bi-person-clock me-1"></i>이번 주 직원별 근무시간</div>
      <div class="card-body"><canvas id="chartWeekly" height="200"></canvas></div>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>
(function() {
  <?php if (!empty($monthlyPayrollTrend)): ?>
  var ctx1 = document.getElementById('chartMonthly');
  if (ctx1) {
    new Chart(ctx1, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($monthlyPayrollTrend, 'ym')) ?>,
        datasets: [{
          label: '인건비 (원)',
          data: <?= json_encode(array_map(fn($r) => (int)$r['total'], $monthlyPayrollTrend)) ?>,
          backgroundColor: 'rgba(0,108,103,0.6)',
          borderColor: '#006C67',
          borderWidth: 1
        }]
      },
      options: { responsive: true, plugins: { legend: { display: false } },
                 scales: { y: { ticks: { callback: function(v) { return (v/10000).toFixed(0) + '만'; } } } } }
    });
  }
  <?php endif; ?>
  <?php if (!empty($weeklyHours)): ?>
  var ctx2 = document.getElementById('chartWeekly');
  if (ctx2) {
    new Chart(ctx2, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($weeklyHours, 'name')) ?>,
        datasets: [{
          label: '유급 근무시간',
          data: <?= json_encode(array_map(fn($r) => round((float)$r['total_paid_minutes'] / 60, 1), $weeklyHours)) ?>,
          backgroundColor: 'rgba(241,148,180,0.6)',
          borderColor: '#F194B4',
          borderWidth: 1
        }]
      },
      options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } },
                 scales: { x: { ticks: { callback: function(v) { return v + 'h'; } } } } }
    });
  }
  <?php endif; ?>
})();
</script>
<?php endif; ?>
