<?php
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date('Y-m-d', strtotime($weekStart . " +$i days"));
}
$prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));
$weekEnd  = end($days);

// 월간 캘린더 준비
$calMonth   = $calMonth ?? substr($weekStart, 0, 7);
$mFirst     = strtotime($calMonth . '-01');
$mDays      = (int)date('t', $mFirst);
$mStartDow  = (int)date('w', $mFirst); // 0=일
$mPrevMonth = date('Y-m', strtotime($calMonth . '-01 -1 month'));
$mNextMonth = date('Y-m', strtotime($calMonth . '-01 +1 month'));
$mByDate    = [];
foreach ($monthSchedules ?? [] as $s) {
    $mByDate[$s['schedule_date']][] = $s;
}
// 직원 색상 팔레트
$palette = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#f97316','#ec4899','#84cc16','#14b8a6'];
$empIdx  = []; $ci = 0;
foreach ($employees as $e) { $empIdx[$e['id']] = $ci++; }
function _eColor(int $id, array $idx, array $pal): string {
    return $pal[($idx[$id] ?? $id) % count($pal)];
}

// 날짜별 그룹핑
$byDate = [];
foreach ($schedules as $s) {
    $byDate[$s['schedule_date']][] = $s;
}
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h1 class="h3 mb-0"><i class="bi bi-calendar-week me-2 text-primary"></i>근무표</h1>
  <div class="d-flex align-items-center gap-2">
    <!-- 뷰 전환 -->
    <div class="btn-group btn-group-sm" role="group">
      <a href="<?= url('schedules', 'index', ['week_date' => $weekStart, 'view' => 'week']) ?>"
         class="btn btn-<?= ($view === 'week') ? 'primary' : 'outline-secondary' ?>">
        <i class="bi bi-calendar-week me-1"></i>주간
      </a>
      <a href="<?= url('schedules', 'index', ['view' => 'month', 'cal_month' => $calMonth]) ?>"
         class="btn btn-<?= ($view === 'month') ? 'primary' : 'outline-secondary' ?>">
        <i class="bi bi-calendar-month me-1"></i>월간
      </a>
    </div>
    <a href="<?= url('schedules', 'create', ['date' => $weekStart]) ?>" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-circle me-1"></i>추가
    </a>
  </div>
</div>

<?php if ($view === 'week'): ?>
<!-- ===== 주간 뷰 ===== -->
<!-- 주 이동 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap gap-2">
    <a href="<?= url('schedules', 'index', ['week_date' => $prevWeek]) ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-chevron-left"></i> 이전 주
    </a>
    <span class="fw-semibold">
      <?= h($weekStart) ?> ~ <?= h($weekEnd) ?>
    </span>
    <a href="<?= url('schedules', 'index', ['week_date' => $nextWeek]) ?>" class="btn btn-sm btn-outline-secondary">
      다음 주 <i class="bi bi-chevron-right"></i>
    </a>
  </div>
</div>

<!-- 주간 달력 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="table-responsive">
    <table class="table table-bordered align-top mb-0 small">
      <thead class="table-light">
        <tr>
          <?php foreach ($days as $d): ?>
          <th class="text-center" style="width:14.28%">
            <?= dayOfWeekKo($d) ?><br>
            <span class="text-muted"><?= h(substr($d, 5)) ?></span>
          </th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <tr>
          <?php foreach ($days as $d): ?>
          <td style="min-height:90px">
            <?php foreach ($byDate[$d] ?? [] as $s): ?>
            <a href="<?= url('schedules', 'edit', ['id' => $s['id']]) ?>"
               class="d-block text-decoration-none mb-1 p-1 rounded"
               style="background:var(--c-cream,#FFEBC6);color:#212529;font-size:.78rem;">
              <span class="fw-semibold"><?= h($s['employee_name']) ?></span><br>
              <?= h(substr($s['start_time'], 0, 5)) ?>~<?= h(substr($s['end_time'], 0, 5)) ?>
              <?php if ((int)$s['break_minutes'] > 0): ?>
                <span class="text-muted">(휴 <?= (int)$s['break_minutes'] ?>분)</span>
              <?php endif; ?>
            </a>
            <?php endforeach; ?>
            <a href="<?= url('schedules', 'create', ['date' => $d]) ?>"
               class="d-block text-center text-muted" style="font-size:.75rem">
              <i class="bi bi-plus"></i>
            </a>
          </td>
          <?php endforeach; ?>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- 예정 인건비 -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
    <span><i class="bi bi-cash-coin me-1 text-success"></i>이번 주 예정 인건비 (참고용)</span>
    <span class="text-success fw-bold"><?= formatWon($estimate['total_amount']) ?></span>
  </div>
  <?php if (empty($estimate['rows'])): ?>
  <div class="card-body text-center text-muted py-4">
    이 주에 등록된 근무 일정이 없습니다.
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-sm align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th class="ps-3">직원</th>
          <th class="text-end">예정 근무시간</th>
          <th class="text-end pe-3">예정 인건비</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($estimate['rows'] as $r): ?>
        <tr>
          <td class="ps-3"><?= h($r['name']) ?></td>
          <td class="text-end"><?= minutesToHoursStr($r['minutes']) ?></td>
          <td class="text-end pe-3"><?= formatWon($r['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
        <tr class="table-success fw-bold">
          <td class="ps-3">합 계</td>
          <td class="text-end"><?= minutesToHoursStr($estimate['total_minutes']) ?></td>
          <td class="text-end pe-3"><?= formatWon($estimate['total_amount']) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="alert alert-light border small text-muted mt-4">
  <i class="bi bi-info-circle me-1"></i>
  예정 인건비는 등록된 시급 × 예정 유급근무시간(휴게 제외)으로 계산한 단순 참고치입니다.
  주휴수당·야간·연장 가산수당은 포함되지 않습니다.
</div>

<?php else: ?>
<!-- ===== 월간 뷰 ===== -->
<!-- 월 이동 -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 d-flex align-items-center justify-content-between gap-2">
    <a href="<?= url('schedules', 'index', ['view' => 'month', 'cal_month' => $mPrevMonth]) ?>"
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-chevron-left"></i> 이전 달
    </a>
    <span class="fw-semibold"><?= h(date('Y년 n월', $mFirst)) ?></span>
    <a href="<?= url('schedules', 'index', ['view' => 'month', 'cal_month' => $mNextMonth]) ?>"
       class="btn btn-sm btn-outline-secondary">
      다음 달 <i class="bi bi-chevron-right"></i>
    </a>
  </div>
</div>

<!-- 직원 범례 -->
<?php if (!empty($employees)): ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach ($employees as $e): ?>
  <?php $ec = _eColor((int)$e['id'], $empIdx, $palette); ?>
  <span class="badge rounded-pill d-flex align-items-center gap-1" style="background:<?= $ec ?>;font-size:.75rem">
    <?= h($e['name']) ?>
  </span>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- 월간 캘린더 그리드 -->
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-bordered align-top mb-0 small" style="table-layout:fixed">
      <thead class="table-light">
        <tr>
          <?php foreach (['일','월','화','수','목','금','토'] as $dow): ?>
          <th class="text-center py-2" style="width:14.28%"><?= $dow ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php
        $cell = 0;
        $totalCells = $mStartDow + $mDays;
        $rows = (int)ceil($totalCells / 7);
        for ($row = 0; $row < $rows; $row++):
        ?>
        <tr>
          <?php for ($col = 0; $col < 7; $col++):
            $dayNum = $cell - $mStartDow + 1;
            $cell++;
            $isOff = $dayNum < 1 || $dayNum > $mDays;
            $dateStr = $isOff ? '' : sprintf('%s-%02d', $calMonth, $dayNum);
            $isToday = $dateStr === date('Y-m-d');
          ?>
          <td class="<?= $isOff ? 'bg-light' : '' ?>" style="min-height:80px;vertical-align:top;padding:4px">
            <?php if (!$isOff): ?>
            <div class="d-flex justify-content-between align-items-center mb-1">
              <span class="fw-semibold <?= $isToday ? 'text-white bg-primary rounded-circle px-1' : ($col===0?'text-danger':($col===6?'text-primary':'')) ?>"
                    style="font-size:.8rem;min-width:20px;text-align:center">
                <?= $dayNum ?>
              </span>
              <a href="<?= url('schedules', 'create', ['date' => $dateStr]) ?>"
                 class="text-muted" style="font-size:.7rem;line-height:1"><i class="bi bi-plus"></i></a>
            </div>
            <?php foreach ($mByDate[$dateStr] ?? [] as $s):
              $ec = _eColor((int)$s['employee_id'], $empIdx, $palette);
            ?>
            <a href="<?= url('schedules', 'edit', ['id' => $s['id']]) ?>"
               class="d-block text-decoration-none text-white rounded mb-1 px-1"
               style="background:<?= $ec ?>;font-size:.72rem;line-height:1.4;overflow:hidden;white-space:nowrap;text-overflow:ellipsis"
               title="<?= h($s['employee_name']) ?> <?= h(substr($s['start_time'],0,5)) ?>~<?= h(substr($s['end_time'],0,5)) ?>">
              <?= h($s['employee_name']) ?> <?= h(substr($s['start_time'],0,5)) ?>~<?= h(substr($s['end_time'],0,5)) ?>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
          </td>
          <?php endfor; ?>
        </tr>
        <?php endfor; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
