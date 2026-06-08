<?php $isWorking = !empty($working); ?>

<!-- ── 현재 상태 카드 ──────────────────────────── -->
<div class="card border-0 shadow mb-3 text-center p-4"
     style="background:<?= $isWorking ? 'var(--c-teal)' : 'var(--c-dark)' ?>;color:#fff;border-radius:20px">
  <div class="mb-2" style="font-size:.9rem;opacity:.8">
    <?= date('Y년 m월 d일 (D)', strtotime($today)) ?>
  </div>
  <?php if ($isWorking): ?>
    <div class="mb-1" style="font-size:2rem"><i class="bi bi-person-workspace"></i></div>
    <div style="font-size:1.1rem;font-weight:700">근무 중</div>
    <div style="font-size:.85rem;opacity:.8">출근: <?= date('H:i', strtotime($working['clock_in_at'])) ?></div>
    <div class="mt-3" id="elapsed-timer" style="font-size:2.2rem;font-weight:700;letter-spacing:.05em">--:--</div>
  <?php else: ?>
    <div class="mb-1" style="font-size:2rem"><i class="bi bi-moon-stars"></i></div>
    <div style="font-size:1.1rem;font-weight:700">퇴근 / 미출근</div>
    <div style="font-size:.85rem;opacity:.8">출근 버튼을 눌러 시작하세요</div>
  <?php endif; ?>
</div>

<!-- ── 출근/퇴근 버튼 ──────────────────────────── -->
<?php if ($isWorking): ?>
<form method="post" action="<?= url('employee', 'clock_out') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="log_id" value="<?= $working['id'] ?>">
  <button type="submit" class="btn w-100 py-4 mb-3 fw-bold"
          style="background:var(--c-amber);color:var(--c-dark);font-size:1.3rem;border-radius:16px;border:none">
    <i class="bi bi-door-open me-2"></i>퇴근하기
  </button>
</form>
<?php else: ?>
<form method="post" action="<?= url('employee', 'clock_in') ?>">
  <?= csrf_field() ?>
  <button type="submit" class="btn w-100 py-4 mb-3 fw-bold"
          style="background:var(--c-pink);color:var(--c-dark);font-size:1.3rem;border-radius:16px;border:none">
    <i class="bi bi-door-closed me-2"></i>출근하기
  </button>
</form>
<?php endif; ?>

<!-- ── 이번주 / 이번달 요약 ───────────────────── -->
<div class="row g-2 mb-3">
  <div class="col-6">
    <div class="card border-0 shadow-sm text-center py-3 px-2">
      <div class="small text-muted mb-1">이번 주</div>
      <div class="fw-bold fs-5"><?= minutesToHoursStr($weekSummary['total_minutes']) ?></div>
      <div class="small text-muted"><?= $weekSummary['work_days'] ?>일 출근</div>
    </div>
  </div>
  <div class="col-6">
    <div class="card border-0 shadow-sm text-center py-3 px-2">
      <div class="small text-muted mb-1">이번 달 예상 급여</div>
      <div class="fw-bold fs-5" style="color:var(--c-teal)"><?= formatWon($monthPayEst) ?></div>
      <div class="small text-muted"><?= $monthSummary['work_days'] ?>일 / <?= minutesToHoursStr($monthSummary['total_minutes']) ?></div>
    </div>
  </div>
</div>

<!-- ── 최근 출퇴근 기록 ────────────────────────── -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small py-2">
    <i class="bi bi-clock-history me-1"></i>최근 출퇴근 기록
  </div>
  <ul class="list-group list-group-flush">
    <?php if (empty($recentLogs)): ?>
    <li class="list-group-item text-muted small py-3 text-center">기록이 없습니다.</li>
    <?php else: ?>
    <?php foreach ($recentLogs as $log): ?>
    <li class="list-group-item py-2 px-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <span class="fw-semibold small"><?= date('m/d(D)', strtotime($log['clock_in_at'])) ?></span>
          <span class="text-muted small ms-2">
            <?= date('H:i', strtotime($log['clock_in_at'])) ?>
            ~
            <?= $log['clock_out_at'] ? date('H:i', strtotime($log['clock_out_at'])) : '근무중' ?>
          </span>
        </div>
        <div class="d-flex align-items-center gap-2">
          <?php if ($log['duration_minutes']): ?>
          <span class="small text-muted"><?= minutesToHoursStr((int)$log['duration_minutes']) ?></span>
          <?php endif; ?>
          <?php
          $badgeMap = [
            'working'              => ['bg-success', '근무중'],
            'completed'            => ['bg-secondary', '완료'],
            'correction_requested' => ['bg-warning text-dark', '수정요청'],
            'corrected'            => ['bg-info text-dark', '수정됨'],
            'approved'             => ['bg-primary', '승인'],
          ];
          [$bc, $bl] = $badgeMap[$log['status']] ?? ['bg-light text-dark', $log['status']];
          ?>
          <span class="badge <?= $bc ?> rounded-pill" style="font-size:.7rem"><?= $bl ?></span>
          <?php if ($log['status'] === 'completed'): ?>
          <button class="btn btn-link p-0 text-muted" style="font-size:.75rem"
                  onclick="openCorrectionModal(<?= $log['id'] ?>, '<?= date('Y-m-d\TH:i', strtotime($log['clock_in_at'])) ?>', '<?= $log['clock_out_at'] ? date('Y-m-d\TH:i', strtotime($log['clock_out_at'])) : '' ?>')">
            수정요청
          </button>
          <?php endif; ?>
        </div>
      </div>
    </li>
    <?php endforeach; ?>
    <?php endif; ?>
  </ul>
</div>

<!-- ── 출퇴근 누락 신규 요청 ──────────────────── -->
<div class="d-grid mb-4">
  <button class="btn btn-outline-secondary btn-sm"
          onclick="openCorrectionModal(null,'','')">
    <i class="bi bi-plus-circle me-1"></i>출퇴근 누락 신고
  </button>
</div>

<!-- ── 수정 요청 모달 ────────────────────────── -->
<div class="modal fade" id="correctionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">출퇴근 수정 요청</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= url('employee', 'request_correction') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="attendance_log_id" id="corrLogId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">요청 출근 시각</label>
            <input type="datetime-local" name="requested_clock_in_at" id="corrIn" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">요청 퇴근 시각</label>
            <input type="datetime-local" name="requested_clock_out_at" id="corrOut" class="form-control">
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">수정 사유 <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3"
                      placeholder="수정이 필요한 이유를 입력하세요." required></textarea>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-primary btn-sm">요청 제출</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// 출근 중 경과시간 타이머
<?php if ($isWorking): ?>
var clockInTs = <?= strtotime($working['clock_in_at']) ?> * 1000;
function updateTimer() {
  var elapsed = Math.floor((Date.now() - clockInTs) / 1000);
  var h = Math.floor(elapsed / 3600);
  var m = Math.floor((elapsed % 3600) / 60);
  var s = elapsed % 60;
  document.getElementById('elapsed-timer').textContent =
    String(h).padStart(2,'0') + ':' +
    String(m).padStart(2,'0') + ':' +
    String(s).padStart(2,'0');
}
updateTimer();
setInterval(updateTimer, 1000);
<?php endif; ?>

function openCorrectionModal(logId, inTime, outTime) {
  document.getElementById('corrLogId').value = logId || '';
  document.getElementById('corrIn').value   = inTime || '';
  document.getElementById('corrOut').value  = outTime || '';
  new bootstrap.Modal(document.getElementById('correctionModal')).show();
}
</script>
