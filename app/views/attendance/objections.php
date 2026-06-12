<?php
/** @var array $objections */
function objFmt(?string $dt): string
{
    return $dt ? date('m/d H:i', strtotime($dt)) : '—';
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0" style="color:#003844">
    <i class="bi bi-flag-fill me-1" style="color:#dc3545"></i>직원 이의제기 관리
  </h5>
  <a href="<?= url('attendance_change') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>수정 요청 목록으로
  </a>
</div>

<?php if (empty($objections)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center text-muted py-5">
    <i class="bi bi-check2-circle fs-1 d-block mb-2" style="color:#006C67"></i>
    처리 대기 중인 이의제기가 없습니다.
  </div>
</div>
<?php else: ?>

<?php foreach ($objections as $o):
  $hasReq = !empty($o['employee_requested_clock_in']) || !empty($o['employee_requested_clock_out'])
            || ($o['employee_requested_break_min'] !== null && $o['employee_requested_break_min'] !== '');
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-3">
      <div class="fw-bold" style="color:#003844">
        <i class="bi bi-person-circle me-1" style="color:#006C67"></i><?= h($o['member_name']) ?>
        <span class="text-muted small ms-1"><?= h($o['work_date']) ?></span>
      </div>
      <span class="badge bg-danger">이의제기됨</span>
    </div>

    <!-- 3단 비교표 -->
    <div class="row g-2 small mb-3">
      <div class="col-4">
        <div class="text-muted mb-1 fw-semibold">원본</div>
        <div class="text-muted">출근: <?= objFmt($o['original_clock_in']) ?></div>
        <div class="text-muted">퇴근: <?= objFmt($o['original_clock_out']) ?></div>
        <div class="text-muted">휴게: <?= ($o['original_break_min'] !== null && $o['original_break_min'] !== '') ? (int)$o['original_break_min'] : 0 ?>분</div>
      </div>
      <div class="col-4">
        <div class="text-muted mb-1 fw-semibold">점주 수정안</div>
        <div class="text-warning fw-semibold">출근: <?= objFmt($o['proposed_clock_in']) ?></div>
        <div class="text-warning fw-semibold">퇴근: <?= objFmt($o['proposed_clock_out']) ?></div>
        <div class="text-warning fw-semibold">휴게: <?= ($o['proposed_break_min'] !== null && $o['proposed_break_min'] !== '') ? (int)$o['proposed_break_min'] : 0 ?>분</div>
      </div>
      <div class="col-4">
        <div class="text-muted mb-1 fw-semibold">직원 요청</div>
        <?php if ($hasReq): ?>
        <div class="text-success fw-semibold">출근: <?= objFmt($o['employee_requested_clock_in']) ?></div>
        <div class="text-success fw-semibold">퇴근: <?= objFmt($o['employee_requested_clock_out']) ?></div>
        <div class="text-success fw-semibold">휴게: <?= ($o['employee_requested_break_min'] !== null && $o['employee_requested_break_min'] !== '') ? (int)$o['employee_requested_break_min'] : 0 ?>분</div>
        <?php else: ?>
        <div class="text-muted fst-italic">요청값 없음<br>(원본 요청)</div>
        <?php endif; ?>
      </div>
    </div>

    <div class="alert alert-danger py-2 px-3 small mb-2">
      <i class="bi bi-flag-fill me-1"></i><strong>이의제기 사유:</strong> <?= h($o['employee_objection']) ?>
    </div>
    <div class="small text-muted mb-3">제출일시: <?= $o['employee_response_at'] ? date('Y-m-d H:i', strtotime($o['employee_response_at'])) : '—' ?></div>

    <div class="d-flex gap-2 flex-wrap">
      <button type="button" class="btn btn-sm btn-success"
              onclick="openAcceptModal(<?= (int)$o['id'] ?>, <?= $hasReq ? 'true' : 'false' ?>)">
        <i class="bi bi-check-lg me-1"></i>수락
      </button>
      <button type="button" class="btn btn-sm btn-outline-danger"
              onclick="openRejectModal(<?= (int)$o['id'] ?>)">
        <i class="bi bi-x-lg me-1"></i>거부
      </button>
      <button type="button" class="btn btn-sm btn-outline-warning"
              onclick="openCounterModal(<?= (int)$o['id'] ?>, '<?= h(date('Y-m-d\TH:i', strtotime($o['proposed_clock_in']))) ?>', '<?= $o['proposed_clock_out'] ? h(date('Y-m-d\TH:i', strtotime($o['proposed_clock_out']))) : '' ?>', <?= ($o['proposed_break_min'] !== null && $o['proposed_break_min'] !== '') ? (int)$o['proposed_break_min'] : 0 ?>)">
        <i class="bi bi-arrow-repeat me-1"></i>재수정 제안
      </button>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── 수락 모달 ──────────────────────────────── -->
<div class="modal fade" id="acceptModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">이의제기 수락</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('attendance_change', 'acceptObjection') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="acceptId">
        <div class="modal-body">
          <label class="form-label small fw-semibold mb-2">확정에 반영할 값</label>
          <div class="form-check">
            <input class="form-check-input" type="radio" name="accept_type" value="employee_request"
                   id="acceptEmp" checked>
            <label class="form-check-label small" for="acceptEmp">직원 요청값 적용</label>
          </div>
          <div class="form-check mb-3">
            <input class="form-check-input" type="radio" name="accept_type" value="original" id="acceptOrig">
            <label class="form-check-label small" for="acceptOrig">원본값 적용</label>
          </div>
          <div class="alert alert-warning small py-2 mb-3 d-none" id="acceptNoReqWarn">
            <i class="bi bi-exclamation-triangle me-1"></i>직원 요청값이 없어 직원 요청값 선택 시 원본값이 적용됩니다.
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">점주 코멘트 (선택)</label>
            <textarea name="owner_response" class="form-control" rows="2"
                      placeholder="직원에게 전달할 메모를 입력하세요."></textarea>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-success btn-sm">수락 확정</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── 거부 모달 ──────────────────────────────── -->
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">이의제기 거부</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('attendance_change', 'rejectObjection') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="rejectId">
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small fw-semibold">거부 사유 <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="3" required
                      placeholder="이의제기를 거부하는 사유를 입력하세요."></textarea>
          </div>
          <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>현재 점주 수정안이 유지되며, 직원에게 공개됩니다.
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-danger btn-sm">거부 확정</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── 재수정 제안 모달 ───────────────────────── -->
<div class="modal fade" id="counterModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">재수정 제안</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('attendance_change', 'counterPropose') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="counterId">
        <div class="modal-body">
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small fw-semibold">출근시간 <span class="text-danger">*</span></label>
              <input type="datetime-local" name="counter_clock_in" id="counterIn"
                     class="form-control form-control-sm" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">퇴근시간 <span class="text-danger">*</span></label>
              <input type="datetime-local" name="counter_clock_out" id="counterOut"
                     class="form-control form-control-sm" required>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold">휴게시간(분)</label>
              <input type="number" name="counter_break_min" id="counterBreak"
                     class="form-control form-control-sm" min="0" max="480">
            </div>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">재수정 사유 <span class="text-danger">*</span></label>
            <textarea name="counter_reason" class="form-control" rows="2" required
                      placeholder="재수정안을 제안하는 사유를 입력하세요."></textarea>
          </div>
          <div class="alert alert-light border small text-muted mb-0">
            <i class="bi bi-info-circle me-1"></i>재수정안은 직원에게 전달되며, 직원 수락 시 정정 기록에 반영됩니다.
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-warning btn-sm text-dark">재수정안 전달</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="alert alert-light border small text-muted mt-3">
  <i class="bi bi-info-circle me-1"></i>
  <strong>수락</strong>: 직원 요청값 또는 원본값을 정정 기록에 반영합니다.
  <strong>거부</strong>: 현재 점주 수정안을 유지하며 직원에게 공개됩니다.
  <strong>재수정 제안</strong>: 새 시간을 제안해 직원의 재검토를 받습니다.
  모든 변경 이력은 삭제되지 않고 보존됩니다.
</div>

<script>
function openAcceptModal(id, hasReq) {
  document.getElementById('acceptId').value = id;
  document.getElementById('acceptEmp').checked = true;
  document.getElementById('acceptNoReqWarn').classList.toggle('d-none', hasReq);
  new bootstrap.Modal(document.getElementById('acceptModal')).show();
}
function openRejectModal(id) {
  document.getElementById('rejectId').value = id;
  new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
function openCounterModal(id, propIn, propOut, propBreak) {
  document.getElementById('counterId').value = id;
  document.getElementById('counterIn').value = propIn || '';
  document.getElementById('counterOut').value = propOut || '';
  document.getElementById('counterBreak').value = propBreak || 0;
  new bootstrap.Modal(document.getElementById('counterModal')).show();
}
</script>
