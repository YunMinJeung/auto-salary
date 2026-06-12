<?php $isWorking = !empty($working); ?>

<!-- ── 퇴근 직후 오늘 급여 결과 ────────────────── -->
<?php if (!empty($clockOutResult)): ?>
<div class="card border-0 mb-3 text-center"
     style="background:linear-gradient(135deg,var(--c-teal),#004d4a);color:#fff;border-radius:16px">
  <div class="card-body py-4">
    <div style="font-size:.85rem;opacity:.75;margin-bottom:.2rem">
      <i class="bi bi-check-circle-fill me-1"></i>오늘 근무 완료
    </div>
    <div style="font-size:2.2rem;font-weight:700;letter-spacing:-.02em">
      <?= formatWon($clockOutResult['pay']) ?>
    </div>
    <div style="font-size:.85rem;opacity:.75;margin-top:.2rem">
      <?= minutesToHoursStr($clockOutResult['minutes']) ?> 근무
    </div>
  </div>
</div>
<?php endif; ?>

<!-- ── 현재 상태 카드 ──────────────────────────── -->
<div class="card border-0 shadow mb-3 text-center p-4"
     style="background:<?= $isWorking ? 'var(--c-teal)' : 'var(--c-dark)' ?>;color:#fff;border-radius:20px">
  <div class="mb-2" style="font-size:.9rem;opacity:.8">
    <?= date('Y년 m월 d일 (D)', strtotime($today)) ?>
  </div>
  <?php if ($isWorking): ?>
    <div class="mb-1" style="font-size:2rem"><i class="bi bi-person-workspace"></i></div>
    <div style="font-size:1.1rem;font-weight:700">근무 중</div>
    <div style="font-size:.85rem;opacity:.8">출근: <?= date('H:i', strtotime($working['original_clock_in_at'])) ?></div>
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
  <input type="hidden" name="latitude"  id="hidLatCo">
  <input type="hidden" name="longitude" id="hidLngCo">
  <input type="hidden" name="accuracy"  id="hidAccCo">
  <input type="hidden" name="geo_error" id="hidErrCo">
  <button type="submit" id="clockOutBtn" class="btn w-100 py-4 mb-3 fw-bold"
          style="background:var(--c-amber);color:var(--c-dark);font-size:1.3rem;border-radius:16px;border:none">
    <i class="bi bi-door-open me-2"></i>퇴근하기
  </button>
</form>
<?php else: ?>
<form method="post" action="<?= url('employee', 'clock_in') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="latitude"  id="hidLatCi">
  <input type="hidden" name="longitude" id="hidLngCi">
  <input type="hidden" name="accuracy"  id="hidAccCi">
  <input type="hidden" name="geo_error" id="hidErrCi">
  <button type="submit" id="clockInBtn" class="btn w-100 py-4 mb-3 fw-bold"
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
    <?php if ($showPayToEmployee): ?>
    <div class="card border-0 shadow-sm text-center py-3 px-2">
      <div class="small text-muted mb-1">이번 달 예상 급여</div>
      <div class="fw-bold fs-5" style="color:var(--c-teal)"><?= formatWon($monthPayEst) ?></div>
      <div class="small text-muted"><?= $monthSummary['work_days'] ?>일 / <?= minutesToHoursStr($monthSummary['total_minutes']) ?></div>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm text-center py-3 px-2">
      <div class="small text-muted mb-1">이번 달 근무</div>
      <div class="fw-bold fs-5"><?= $monthSummary['work_days'] ?>일</div>
      <div class="small text-muted"><?= minutesToHoursStr($monthSummary['total_minutes']) ?></div>
    </div>
    <?php endif; ?>
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
    <?php foreach ($recentLogs as $log):
      $effIn  = $log['effective_clock_in_at']  ?? $log['original_clock_in_at'];
      $effOut = $log['effective_clock_out_at'] ?? $log['original_clock_out_at'] ?? null;
      $isAdj  = !empty($log['is_adjusted']);
    ?>
    <li class="list-group-item py-2 px-3 <?= $isAdj ? 'border-start border-3 border-warning' : '' ?>">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <span class="fw-semibold small"><?= date('m/d(D)', strtotime($log['original_clock_in_at'])) ?></span>

          <?php if ($isAdj): ?>
            <div class="small mt-1">
              <span class="text-muted text-decoration-line-through">
                <?= date('H:i', strtotime($log['original_clock_in_at'])) ?>
                ~ <?= $log['original_clock_out_at'] ? date('H:i', strtotime($log['original_clock_out_at'])) : '?' ?>
              </span>
              <span class="ms-2 fw-semibold text-warning-emphasis">
                <i class="bi bi-arrow-right-short"></i>
                <?= date('H:i', strtotime($effIn)) ?>
                ~ <?= $effOut ? date('H:i', strtotime($effOut)) : '근무중' ?>
              </span>
            </div>
          <?php else: ?>
            <span class="text-muted small ms-2">
              <?= date('H:i', strtotime($effIn)) ?>
              ~ <?= $effOut ? date('H:i', strtotime($effOut)) : '근무중' ?>
            </span>
          <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-2 flex-shrink-0 ms-2">
          <?php if ($log['duration_minutes']): ?>
          <span class="small text-muted"><?= minutesToHoursStr((int)$log['duration_minutes']) ?></span>
          <?php endif; ?>
          <?php
          $badgeMap = [
            'working'              => ['bg-success',           '근무중'],
            'completed'            => ['bg-secondary',         '완료'],
            'correction_requested' => ['bg-warning text-dark', '수정요청중'],
            'corrected'            => ['bg-warning text-dark', '정정됨'],
            'approved'             => ['bg-primary',           '승인'],
            'payroll_confirmed'    => ['bg-info text-dark',    '급여확정'],
            'payroll_paid'         => ['bg-success',           '지급완료'],
          ];
          [$bc, $bl] = $badgeMap[$log['status']] ?? ['bg-light text-dark', $log['status']];
          ?>
          <span class="badge <?= $bc ?> rounded-pill" style="font-size:.7rem"><?= $bl ?></span>

          <?php if (in_array($log['status'], ['completed', 'corrected'])): ?>
          <button class="btn btn-link p-0 text-muted" style="font-size:.75rem"
                  onclick="openCorrectionModal(
                    <?= $log['id'] ?>,
                    '<?= date('Y-m-d\TH:i', strtotime($effIn)) ?>',
                    '<?= $effOut ? date('Y-m-d\TH:i', strtotime($effOut)) : '' ?>'
                  )">
            수정요청
          </button>
          <?php endif; ?>
        </div>
      </div>

      <?php if ($isAdj): ?>
      <div class="mt-2 pt-2 border-top small text-muted">
        <i class="bi bi-info-circle me-1"></i>점주에 의해 정정된 기록입니다.
        <button class="btn btn-link btn-sm p-0 ms-1" style="font-size:.75rem;color:inherit"
                onclick="loadAdjHistory(<?= $log['id'] ?>, this)">
          상세 보기
        </button>
        <div id="adj-detail-<?= $log['id'] ?>" style="display:none"></div>
      </div>
      <?php endif; ?>
    </li>
    <?php endforeach; ?>
    <?php endif; ?>
  </ul>
</div>

<?php if (!empty($pendingChanges)): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small py-2 d-flex justify-content-between">
    <span><i class="bi bi-pencil-square me-1 text-warning"></i>출퇴근 수정 요청</span>
    <span class="badge bg-warning text-dark"><?= count($pendingChanges) ?></span>
  </div>
  <div class="list-group list-group-flush">
    <?php foreach ($pendingChanges as $req): ?>
    <div class="list-group-item px-3 py-3">
      <div class="small fw-semibold mb-2"><?= date('m/d', strtotime($req['original_clock_in'])) ?> 출퇴근 기록 수정 요청</div>
      <div class="row g-2 small mb-2">
        <div class="col-6">
          <div class="text-muted">수정 전</div>
          <div><?= date('H:i', strtotime($req['original_clock_in'])) ?> ~ <?= $req['original_clock_out'] ? date('H:i', strtotime($req['original_clock_out'])) : '—' ?></div>
          <?php if ($req['original_break_min'] !== null && $req['original_break_min'] !== ''): ?><div class="text-muted">휴게 <?= (int)$req['original_break_min'] ?>분</div><?php endif; ?>
        </div>
        <div class="col-6">
          <div class="text-muted">수정 후</div>
          <div class="fw-semibold"><?= date('H:i', strtotime($req['proposed_clock_in'])) ?> ~ <?= $req['proposed_clock_out'] ? date('H:i', strtotime($req['proposed_clock_out'])) : '—' ?></div>
          <?php if ($req['proposed_break_min'] !== null && $req['proposed_break_min'] !== ''): ?><div class="text-muted">휴게 <?= (int)$req['proposed_break_min'] ?>분</div><?php endif; ?>
        </div>
      </div>
      <div class="small text-muted mb-2">사유: <?= h($req['change_reason']) ?></div>
      <div class="small text-muted mb-2">요청일시: <?= date('m/d H:i', strtotime($req['created_at'])) ?></div>

      <?php if ($req['status'] === 'pending_employee_review'): ?>
      <div class="d-flex gap-2 mt-2">
        <form method="POST" action="<?= url('employee', 'change_accept') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
          <button type="submit" class="btn btn-outline-success btn-sm py-1" style="font-size:.78rem;">
            <i class="bi bi-check me-1"></i>수락
          </button>
        </form>
        <button class="btn btn-outline-warning btn-sm py-1" style="font-size:.78rem;"
                onclick="openObjectModal(<?= (int)$req['id'] ?>)">
          <i class="bi bi-flag me-1"></i>이의제기
        </button>
      </div>
      <?php elseif ($req['status'] === 'counter_proposed'): ?>
      <div class="small text-warning fw-semibold mb-2">
        <i class="bi bi-arrow-repeat me-1"></i>사장님이 재수정안을 제안했습니다
      </div>
      <div class="small mb-2">
        재수정안: <?= date('H:i', strtotime($req['proposed_clock_in'])) ?> ~
        <?= $req['proposed_clock_out'] ? date('H:i', strtotime($req['proposed_clock_out'])) : '—' ?>
        <?php if ($req['proposed_break_min'] !== null && $req['proposed_break_min'] !== ''): ?>(휴게 <?= (int)$req['proposed_break_min'] ?>분)<?php endif; ?>
      </div>
      <?php if (!empty($req['counter_reason'])): ?>
      <div class="small text-muted mb-2">재수정 사유: <?= h($req['counter_reason']) ?></div>
      <?php endif; ?>
      <div class="d-flex gap-2 mt-2">
        <form method="POST" action="<?= url('employee', 'acceptcounter') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$req['id'] ?>">
          <button type="submit" class="btn btn-outline-success btn-sm py-1" style="font-size:.78rem;">
            <i class="bi bi-check me-1"></i>재수정안 수락
          </button>
        </form>
        <button class="btn btn-outline-warning btn-sm py-1" style="font-size:.78rem;"
                onclick="openObjectModal(<?= (int)$req['id'] ?>)">
          <i class="bi bi-flag me-1"></i>재이의제기
        </button>
      </div>
      <?php elseif (in_array($req['objection_status'] ?? '', ['accepted', 'rejected'], true)): ?>
      <div class="d-flex align-items-center gap-2 mt-2">
        <?php if ($req['objection_status'] === 'accepted'): ?>
        <span class="badge bg-success">이의제기 수락됨</span>
        <?php else: ?>
        <span class="badge bg-danger">이의제기 거부됨</span>
        <?php endif; ?>
      </div>
      <?php if (!empty($req['owner_response'])): ?>
      <div class="small text-muted mt-1">사장님 답변: <?= h($req['owner_response']) ?></div>
      <?php endif; ?>
      <?php elseif ($req['status'] === 'objected'): ?>
      <div class="small text-warning mt-1"><i class="bi bi-flag me-1"></i>이의제기 전달됨 — 사장님 검토 대기</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 이의제기 모달 (출퇴근 수정 요청용) -->
<div class="modal fade" id="objectionChangeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">이의제기</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('employee', 'change_object') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="objChangeId">
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">이의제기 사유 <span class="text-danger">*</span></label>
            <textarea name="objection" class="form-control" rows="3" required
                      placeholder="어떤 부분이 다른지 알려주세요."></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">내가 생각하는 올바른 시간 (선택)</label>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label small text-muted">출근시간</label>
                <input type="datetime-local" name="requested_clock_in" class="form-control form-control-sm">
              </div>
              <div class="col-6">
                <label class="form-label small text-muted">퇴근시간</label>
                <input type="datetime-local" name="requested_clock_out" class="form-control form-control-sm">
              </div>
              <div class="col-12">
                <label class="form-label small text-muted">휴게시간(분)</label>
                <input type="number" name="requested_break_min" class="form-control form-control-sm" min="0" max="480">
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-warning btn-sm text-dark">이의제기 제출</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function openObjectModal(id) {
  document.getElementById('objChangeId').value = id;
  new bootstrap.Modal(document.getElementById('objectionChangeModal')).show();
}
</script>
<?php endif; ?>

<?php if (!empty($myAlerts)): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small py-2 d-flex justify-content-between">
    <span><i class="bi bi-bell me-1" style="color:var(--c-amber)"></i>확인 필요한 기록</span>
    <span class="badge bg-warning text-dark"><?= count($myAlerts) ?></span>
  </div>
  <div class="list-group list-group-flush">
    <?php foreach ($myAlerts as $alert): ?>
    <div class="list-group-item px-3 py-3">
      <?php
      $empMsg = h($alert['employee_message'] ?: $alert['message']);
      $iconClass = $alert['severity'] === 'danger'  ? 'bi-exclamation-circle-fill text-danger'
                 : ($alert['severity'] === 'warning' ? 'bi-exclamation-triangle text-warning'
                                                     : 'bi-info-circle text-info');
      ?>
      <div class="d-flex align-items-start gap-2 mb-2">
        <i class="bi <?= $iconClass ?> flex-shrink-0 mt-1"></i>
        <div>
          <div class="fw-semibold small"><?= h($alert['title']) ?></div>
          <div class="small text-muted mt-1"><?= $empMsg ?></div>
          <div class="text-muted" style="font-size:.75rem;"><?= date('m/d H:i', strtotime($alert['created_at'])) ?></div>
        </div>
      </div>
      <?php
      $responded = false;
      foreach ($myObjections as $obj) {
          if ($obj['related_type'] === 'labor_risk_alert' && (int)$obj['related_id'] === (int)$alert['id']) {
              $responded = true; break;
          }
      }
      ?>
      <?php if (!$responded): ?>
      <div class="d-flex gap-2 mt-1">
        <form method="POST" action="<?= url('employee', 'respond') ?>" style="display:inline">
          <?= csrf_field() ?>
          <input type="hidden" name="related_type" value="labor_risk_alert">
          <input type="hidden" name="related_id" value="<?= (int)$alert['id'] ?>">
          <input type="hidden" name="response_type" value="acknowledged">
          <button type="submit" class="btn btn-outline-success btn-sm py-1" style="font-size:.78rem;">
            <i class="bi bi-check me-1"></i>확인했어요
          </button>
        </form>
        <button class="btn btn-outline-warning btn-sm py-1" style="font-size:.78rem;"
                onclick="openObjectionModal(<?= (int)$alert['id'] ?>, '<?= addslashes(h($alert['title'])) ?>')">
          <i class="bi bi-flag me-1"></i>이의제기
        </button>
      </div>
      <?php else: ?>
      <div class="small text-muted mt-1"><i class="bi bi-check-circle me-1 text-success"></i>확인 완료</div>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ── 이의제기 모달 ────────────────────────── -->
<div class="modal fade" id="objectionModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold" id="objectionTitle">이의제기</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('employee', 'respond') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="related_type" value="labor_risk_alert">
        <input type="hidden" name="related_id" id="objectionRelatedId">
        <input type="hidden" name="response_type" value="objection">
        <div class="modal-body">
          <p class="text-muted small mb-3">이의제기 내용을 입력하면 사장님에게 전달됩니다.</p>
          <div class="mb-2">
            <label class="form-label small fw-semibold">이의제기 사유</label>
            <textarea name="message" class="form-control" rows="3" required
                      placeholder="어떤 부분이 다른지 알려주세요."></textarea>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-warning btn-sm text-dark">이의제기 제출</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function openObjectionModal(alertId, title) {
  document.getElementById('objectionRelatedId').value = alertId;
  document.getElementById('objectionTitle').textContent = '이의제기: ' + title;
  new bootstrap.Modal(document.getElementById('objectionModal')).show();
}
</script>
<?php endif; ?>

<?php
$visibleCorrections = array_filter($corrections ?? [], fn($c) => in_array($c['status'], ['pending', 'rejected', 'objected', 'final_rejected']));
?>
<?php if (!empty($visibleCorrections)): ?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold small py-2">
    <i class="bi bi-plus-circle me-1"></i>출퇴근 누락 신고 현황
  </div>
  <ul class="list-group list-group-flush">
    <?php foreach ($visibleCorrections as $cor): ?>
    <li class="list-group-item py-2 px-3">
      <div class="d-flex justify-content-between align-items-start">
        <div class="small">
          <div class="text-muted mb-1">
            <?= $cor['requested_clock_in_at'] ? date('m/d H:i', strtotime($cor['requested_clock_in_at'])) : '?' ?>
            ~
            <?= $cor['requested_clock_out_at'] ? date('H:i', strtotime($cor['requested_clock_out_at'])) : '?' ?>
          </div>
          <div class="text-muted">신청 사유: <?= h($cor['reason']) ?></div>
          <?php if ($cor['status'] === 'rejected' && !empty($cor['owner_comment'])): ?>
          <div class="text-danger mt-1"><i class="bi bi-x-circle me-1"></i>반려 사유: <?= h($cor['owner_comment']) ?></div>
          <?php endif; ?>
          <?php if ($cor['status'] === 'rejected'): ?>
          <button class="btn btn-outline-warning btn-sm mt-2 py-1" style="font-size:.75rem"
                  onclick="openObjModal(<?= (int)$cor['id'] ?>)">
            <i class="bi bi-flag me-1"></i>이의제기
          </button>
          <?php endif; ?>
        </div>
        <?php if ($cor['status'] === 'rejected'): ?>
        <span class="badge bg-danger ms-2 flex-shrink-0">반려됨</span>
        <?php elseif ($cor['status'] === 'objected'): ?>
        <span class="badge bg-warning text-dark ms-2 flex-shrink-0">이의제기 검토중</span>
        <?php elseif ($cor['status'] === 'final_rejected'): ?>
        <span class="badge bg-danger ms-2 flex-shrink-0">최종반려</span>
        <?php else: ?>
        <span class="badge bg-warning text-dark ms-2 flex-shrink-0">대기중</span>
        <?php endif; ?>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<!-- ── 빠른 메뉴 ────────────────────────────── -->
<div class="row g-2 mb-4">
  <div class="col-4">
    <a href="<?= url('employee', 'payslip', ['year' => date('Y'), 'month' => date('n')]) ?>"
       class="btn btn-outline-primary w-100 btn-sm py-2">
      <i class="bi bi-file-earmark-text d-block mb-1" style="font-size:1.3rem"></i>
      이달 예상급여
    </a>
  </div>
  <div class="col-4">
    <a href="<?= url('employee', 'payslips') ?>"
       class="btn btn-outline-success w-100 btn-sm py-2">
      <i class="bi bi-file-earmark-check d-block mb-1" style="font-size:1.3rem"></i>
      발급 명세서
    </a>
  </div>
  <div class="col-4">
    <button class="btn btn-outline-secondary w-100 btn-sm py-2"
            onclick="openCorrectionModal(null,'','')">
      <i class="bi bi-plus-circle d-block mb-1" style="font-size:1.3rem"></i>
      누락 신고
    </button>
  </div>
</div>

<!-- ── 면책 문구 ────────────────────────────── -->
<div class="alert alert-light border small text-muted mb-4">
  <i class="bi bi-info-circle me-1"></i>
  표시된 근무시간과 급여 계산 내역은 사업장에 입력된 근무기록과 설정값을 기준으로 한 자료입니다.
  기록이 실제와 다르면 이의제기를 통해 사업장에 확인을 요청할 수 있습니다.
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
          <p class="text-muted small mb-3">
            수정 요청 후 점주의 승인이 필요합니다. 직접 수정은 불가능합니다.
          </p>
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
<?php if ($isWorking): ?>
// 타이머는 원본 출근 시각 기준 (서버가 기록한 실제 출근 시간)
var clockInTs = <?= strtotime($working['original_clock_in_at']) ?> * 1000;
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

function loadAdjHistory(logId, btn) {
  var el = document.getElementById('adj-detail-' + logId);
  if (el.style.display !== 'none') { el.style.display = 'none'; return; }
  el.innerHTML = '<span class="text-muted">불러오는 중...</span>';
  el.style.display = 'block';
  fetch('<?= BASE_URL ?>api/employee/adj-history.php?log_id=' + logId, { credentials: 'same-origin' })
    .then(function(r){ return r.ok ? r.json() : null; })
    .then(function(data) {
      if (!data || !data.items || !data.items.length) {
        el.innerHTML = '<span class="text-muted">상세 정보를 불러올 수 없습니다.</span>';
        return;
      }
      var html = '';
      data.items.forEach(function(item) {
        html += '<div class="mt-1 p-2 rounded" style="background:#fffbe6">'
          + '<div><strong>' + (item.changed_by_name || '점주') + '</strong> · ' + item.created_at + '</div>'
          + '<div>원본: ' + (item.before_clock_in || '—') + ' ~ ' + (item.before_clock_out || '—') + '</div>'
          + '<div>정정: <span class="fw-semibold">' + (item.after_clock_in || '—') + ' ~ ' + (item.after_clock_out || '—') + '</span></div>'
          + '<div class="text-muted">사유: ' + item.reason + '</div>'
          + '</div>';
      });
      el.innerHTML = html;
    })
    .catch(function() { el.innerHTML = '<span class="text-danger">오류가 발생했습니다.</span>'; });
}

// ── GPS 좌표 수집 후 출퇴근 폼 제출 ──────────────────────
(function () {
  function gpsSubmit(btnId, latId, lngId, accId, errId) {
    var btn = document.getElementById(btnId);
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      var form = btn.closest('form');
      if (!navigator.geolocation) {
        document.getElementById(errId).value = 'unsupported';
        form.submit();
        return;
      }
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>위치 확인 중...';
      navigator.geolocation.getCurrentPosition(
        function (pos) {
          document.getElementById(latId).value = pos.coords.latitude;
          document.getElementById(lngId).value = pos.coords.longitude;
          document.getElementById(accId).value = pos.coords.accuracy;
          form.submit();
        },
        function (err) {
          document.getElementById(errId).value = err.code;
          form.submit();
        },
        { enableHighAccuracy: true, timeout: 8000 }
      );
    });
  }
  gpsSubmit('clockInBtn',  'hidLatCi', 'hidLngCi', 'hidAccCi', 'hidErrCi');
  gpsSubmit('clockOutBtn', 'hidLatCo', 'hidLngCo', 'hidAccCo', 'hidErrCo');
})();
</script>

<!-- 이의제기 모달 (누락신고 반려용) -->
<div class="modal fade" id="corrObjModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-3">
        <h6 class="modal-title fw-bold">이의제기</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="POST" action="<?= url('employee', 'object_correction') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="correction_id" id="corrObjId">
        <div class="modal-body">
          <p class="small text-muted mb-3">이의제기 사유를 입력하면 사장님에게 전달됩니다.</p>
          <textarea name="objection_text" class="form-control" rows="3" required
                    placeholder="반려 처리에 동의하지 않는 이유를 입력하세요."></textarea>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-warning btn-sm text-dark">이의제기 제출</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
function openObjModal(id) {
  document.getElementById('corrObjId').value = id;
  new bootstrap.Modal(document.getElementById('corrObjModal')).show();
}
</script>
