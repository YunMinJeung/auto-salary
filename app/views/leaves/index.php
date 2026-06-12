<?php
$typeLabels = [
    'annual' => ['연차', 'bg-success-subtle text-success border'],
    'sick'   => ['병가', 'bg-info-subtle text-info border'],
    'unpaid' => ['무급', 'bg-secondary-subtle text-secondary border'],
    'other'  => ['기타', 'bg-light text-dark border'],
];
$statusLabels = [
    'approved' => ['승인', 'bg-success'],
    'pending'  => ['대기', 'bg-warning text-dark'],
    'rejected' => ['반려', 'bg-danger'],
];
function leaveNum($n) {
    return rtrim(rtrim(number_format((float)$n, 1, '.', ''), '0'), '.');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-calendar-heart me-2 text-primary"></i>연차/휴가</h1>
  <a href="<?= url('leaves', 'create') ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>휴가 기록 추가
  </a>
</div>

<!-- 직원별 연차 현황 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-person-vcard me-1 text-primary"></i>직원별 연차 현황
  </div>
  <?php if (empty($employees)): ?>
  <div class="card-body text-center text-muted py-4">등록된 직원이 없습니다.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th class="ps-3">직원</th>
          <th class="text-end">발생(연차)</th>
          <th class="text-end">사용</th>
          <th class="text-end">잔여</th>
          <th class="text-end pe-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($employees as $emp):
          $b = $balances[(int)$emp['id']] ?? ['granted' => 0, 'used' => 0, 'remaining' => 0];
        ?>
        <tr>
          <td class="ps-3 fw-semibold"><?= h($emp['name']) ?></td>
          <td class="text-end"><?= leaveNum($b['granted']) ?>일</td>
          <td class="text-end text-muted"><?= leaveNum($b['used']) ?>일</td>
          <td class="text-end fw-bold <?= $b['remaining'] < 0 ? 'text-danger' : 'text-success' ?>">
            <?= leaveNum($b['remaining']) ?>일
          </td>
          <td class="text-end pe-3">
            <a href="<?= url('leaves', 'create', ['employee_id' => $emp['id']]) ?>"
               class="btn btn-xs btn-outline-primary py-0 px-1">
              <i class="bi bi-plus"></i> 휴가
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<!-- 최근 휴가 기록 -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-clock-history me-1 text-primary"></i>최근 휴가 기록
  </div>
  <?php if (empty($records)): ?>
  <div class="card-body text-center text-muted py-4">휴가 기록이 없습니다.</div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th class="ps-3">직원</th>
          <th>유형</th>
          <th>기간</th>
          <th class="text-end">일수</th>
          <th>상태</th>
          <th>메모</th>
          <th class="text-end pe-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($records as $r):
          $tl = $typeLabels[$r['leave_type']] ?? [$r['leave_type'], 'bg-light text-dark border'];
          $sl = $statusLabels[$r['status']]   ?? [$r['status'], 'bg-secondary'];
        ?>
        <tr>
          <td class="ps-3 fw-semibold"><?= h($r['employee_name'] ?? '') ?></td>
          <td><span class="badge <?= $tl[1] ?>"><?= $tl[0] ?></span></td>
          <td>
            <?= h($r['start_date']) ?>
            <?php if ($r['end_date'] !== $r['start_date']): ?>~ <?= h($r['end_date']) ?><?php endif; ?>
          </td>
          <td class="text-end"><?= leaveNum($r['days']) ?>일</td>
          <td><span class="badge <?= $sl[1] ?>"><?= $sl[0] ?></span></td>
          <td class="text-muted"><?= h($r['memo'] ?? '') ?></td>
          <td class="text-end pe-3">
            <div class="d-flex gap-1 justify-content-end">
              <a href="<?= url('leaves', 'edit', ['id' => $r['id']]) ?>"
                 class="btn btn-xs btn-outline-primary py-0 px-1">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" action="<?= url('leaves', 'delete') ?>"
                    onsubmit="return confirm('이 휴가 기록을 삭제하시겠습니까?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-xs btn-outline-danger py-0 px-1">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="alert alert-light border small text-muted mt-4">
  <i class="bi bi-info-circle me-1"></i>
  연차 발생일수는 근로기준법 제60조 기준 참고값입니다(1년 미만: 개근 월 1일 최대 11일, 1년 이상: 15일).
  3년 이상 근속 가산연차는 반영되지 않으며, 5인 미만 사업장은 법정 연차 의무 대상이 아닙니다.
  실제 부여 일수는 사업장 규모·소정근로일·계약 내용에 따라 달라질 수 있습니다.
</div>
