<?php
$days = [];
for ($i = 0; $i < 7; $i++) {
    $days[] = date('Y-m-d', strtotime($weekStart . " +$i days"));
}
$prevWeek = date('Y-m-d', strtotime($weekStart . ' -7 days'));
$nextWeek = date('Y-m-d', strtotime($weekStart . ' +7 days'));
$weekEnd  = end($days);

// 날짜별 그룹핑
$byDate = [];
foreach ($schedules as $s) {
    $byDate[$s['schedule_date']][] = $s;
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-calendar-week me-2 text-primary"></i>근무표</h1>
  <a href="<?= url('schedules', 'create', ['date' => $weekStart]) ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>근무 일정 추가
  </a>
</div>

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
