<?php
/** @var array $requests */
$statusMeta = [
    'pending_employee_review' => ['bg-warning text-dark', '직원 확인 대기'],
    'objected'                => ['bg-danger',            '이의제기됨'],
    'disputed'                => ['bg-warning text-dark', '분쟁'],
];
function fmtRange(?string $in, ?string $out, $breakMin): string
{
    $s = $in  ? date('m/d H:i', strtotime($in)) : '—';
    $e = $out ? date('H:i', strtotime($out))    : '—';
    $b = ($breakMin !== null && $breakMin !== '') ? ' · 휴게 ' . (int)$breakMin . '분' : '';
    return $s . ' ~ ' . $e . $b;
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0" style="color:#003844">
    <i class="bi bi-pencil-square me-1" style="color:#FFB100"></i>출퇴근 수정 요청
  </h5>
  <a href="<?= url('attendance') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>출퇴근 현황
  </a>
</div>

<?php if (empty($requests)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center text-muted py-5">
    <i class="bi bi-check2-circle fs-1 d-block mb-2" style="color:#006C67"></i>
    처리 대기 중인 수정 요청이 없습니다.
  </div>
</div>
<?php else: ?>

<?php foreach ($requests as $req):
  [$badgeClass, $badgeLabel] = $statusMeta[$req['status']] ?? ['bg-secondary', $req['status']];
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="d-flex justify-content-between align-items-start mb-2">
      <div class="fw-bold" style="color:#003844">
        <i class="bi bi-person-circle me-1" style="color:#006C67"></i><?= h($req['member_name']) ?>
        <span class="text-muted small ms-1"><?= h($req['work_date']) ?></span>
      </div>
      <span class="badge <?= $badgeClass ?>"><?= h($badgeLabel) ?></span>
    </div>

    <div class="row g-2 small mb-2">
      <div class="col-md-6">
        <div class="text-muted">수정 전</div>
        <div class="text-decoration-line-through text-muted">
          <?= h(fmtRange($req['original_clock_in'], $req['original_clock_out'], $req['original_break_min'])) ?>
        </div>
      </div>
      <div class="col-md-6">
        <div class="text-muted">수정 후</div>
        <div class="fw-semibold" style="color:#003844">
          <?= h(fmtRange($req['proposed_clock_in'], $req['proposed_clock_out'], $req['proposed_break_min'])) ?>
        </div>
      </div>
    </div>

    <div class="small text-muted mb-1">사유: <?= h($req['change_reason']) ?></div>
    <div class="small text-muted mb-2">요청일시: <?= date('Y-m-d H:i', strtotime($req['created_at'])) ?></div>

    <?php if ($req['status'] === 'objected' && !empty($req['employee_objection'])): ?>
    <div class="alert alert-danger py-2 px-3 small mb-2">
      <i class="bi bi-flag-fill me-1"></i><strong>직원 이의제기:</strong> <?= h($req['employee_objection']) ?>
    </div>
    <?php endif; ?>

    <div class="d-flex gap-2 flex-wrap">
      <?php if ($req['status'] !== 'pending_employee_review'): ?>
      <!-- objected / disputed 인 경우 원본 유지 협의 확정 가능 -->
      <form method="POST" action="<?= url('attendance_change', 'resolve') ?>" class="d-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
        <button type="submit" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-shield-check me-1"></i>원본 유지 (협의 확정)
        </button>
      </form>
      <?php endif; ?>

      <button type="button" class="btn btn-sm btn-danger"
              onclick="openForceModal(<?= (int)$req['id'] ?>)">
        <i class="bi bi-exclamation-octagon me-1"></i>강제 확정
      </button>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- ── 강제 확정 모달 ──────────────────────────── -->
<div class="modal fade" id="forceModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">강제 확정</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('attendance_change', 'forceConfirm') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="forceId">
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small fw-semibold">강제 확정 사유 <span class="text-danger">*</span></label>
            <textarea name="force_reason" class="form-control" rows="3" required
                      placeholder="강제 확정 사유를 입력하세요."></textarea>
          </div>
          <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle me-1"></i>강제 확정은 직원에게 공개되며 이의제기 가능합니다.
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-danger btn-sm">강제 확정</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="alert alert-light border small text-muted mt-3">
  <i class="bi bi-info-circle me-1"></i>
  수정 요청은 직원 수락 후에만 정정 기록에 반영됩니다. 직원이 이의를 제기한 경우
  사실관계를 확인한 뒤 협의 확정(원본 유지) 또는 강제 확정을 선택하세요. 강제 확정 내역은
  직원에게 공개되며, 모든 변경 이력은 삭제되지 않고 보존됩니다.
</div>

<script>
function openForceModal(id) {
  document.getElementById('forceId').value = id;
  new bootstrap.Modal(document.getElementById('forceModal')).show();
}
</script>
