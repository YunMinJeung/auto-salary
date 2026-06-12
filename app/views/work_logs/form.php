<?php $isEdit = $action === 'edit'; ?>
<div class="d-flex align-items-center mb-4">
  <a href="<?= url('work_logs', 'index', ['employee_id' => $log['employee_id'] ?? 0]) ?>"
     class="btn btn-sm btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0">
    <i class="bi bi-calendar-plus-fill me-2 text-success"></i>
    <?= $isEdit ? '근무 기록 수정' : '근무 기록 추가' ?>
  </h1>
</div>

<div class="card border-0 shadow-sm" style="max-width:560px">
  <div class="card-body">
    <form method="post" id="workLogForm"
          action="<?= $isEdit ? url('work_logs', 'edit', ['id' => $log['id']]) : url('work_logs', 'create') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">직원 <span class="text-danger">*</span></label>
        <select name="employee_id"
                class="form-select <?= isset($errors['employee_id']) ? 'is-invalid' : '' ?>" required>
          <option value="">— 선택 —</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>"
                  <?= (string)($log['employee_id'] ?? '') === (string)$emp['id'] ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
        <?php if (isset($errors['employee_id'])): ?>
          <div class="invalid-feedback"><?= h($errors['employee_id']) ?></div>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">근무일 <span class="text-danger">*</span></label>
        <input type="date" name="work_date"
               class="form-control <?= isset($errors['work_date']) ? 'is-invalid' : '' ?>"
               value="<?= h($log['work_date'] ?? date('Y-m-d')) ?>" required>
        <?php if (isset($errors['work_date'])): ?>
          <div class="invalid-feedback"><?= h($errors['work_date']) ?></div>
        <?php endif; ?>
      </div>

      <!-- 결근 체크 시 시간 입력 숨김 -->
      <div class="mb-3">
        <div class="form-check form-check-inline">
          <input class="form-check-input" type="checkbox" id="is_absent" name="is_absent" value="1"
                 <?= ($log['is_absent'] ?? 0) ? 'checked' : '' ?>
                 onchange="toggleAbsent(this.checked)">
          <label class="form-check-label text-danger fw-semibold" for="is_absent">결근</label>
        </div>
      </div>

      <div id="timeSection">
        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">시작시간 <span class="text-danger">*</span></label>
            <input type="time" name="start_time"
                   class="form-control <?= isset($errors['start_time']) ? 'is-invalid' : '' ?>"
                   value="<?= h(substr($log['start_time'] ?? '', 0, 5)) ?>">
            <?php if (isset($errors['start_time'])): ?>
              <div class="invalid-feedback"><?= h($errors['start_time']) ?></div>
            <?php endif; ?>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">마감시간 <span class="text-danger">*</span></label>
            <input type="time" name="end_time"
                   class="form-control <?= isset($errors['end_time']) ? 'is-invalid' : '' ?>"
                   value="<?= h(substr($log['end_time'] ?? '', 0, 5)) ?>">
            <?php if (isset($errors['end_time'])): ?>
              <div class="invalid-feedback"><?= h($errors['end_time']) ?></div>
            <?php endif; ?>
            <div class="form-text">마감이 시작보다 이르면 다음날 새벽 퇴근으로 처리됩니다.</div>
          </div>
        </div>

        <!-- 근무시간 미리보기 -->
        <div id="workPreview" class="alert alert-light border small py-2 mb-3" style="display:none">
          <span id="previewText"></span>
        </div>

        <!-- 휴게시간 -->
        <div class="mb-3">
          <label class="form-label fw-semibold">
            휴게시간
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="근무 중 쉬는 시간. 임금 계산에서 제외됩니다."></i>
          </label>
          <div class="mb-2">
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="checkbox" id="break_auto" name="break_auto" value="1"
                     <?= ($log['break_auto'] ?? 1) ? 'checked' : '' ?>
                     onchange="toggleBreakAuto(this.checked)">
              <label class="form-check-label" for="break_auto">
                자동 계산 (4시간 이상: 30분, 8시간 이상: 60분)
              </label>
            </div>
          </div>
          <div id="breakManual" style="display:<?= ($log['break_auto'] ?? 1) ? 'none' : 'block' ?>">
            <div class="input-group" style="max-width:200px">
              <input type="number" name="break_minutes" class="form-control"
                     id="break_minutes_input"
                     value="<?= h($log['break_minutes'] ?? 0) ?>" min="0" step="5">
              <span class="input-group-text">분</span>
            </div>
            <?php if (isset($errors['break_minutes'])): ?>
              <div class="text-danger small mt-1"><?= h($errors['break_minutes']) ?></div>
            <?php endif; ?>
          </div>
        </div>
      </div><!-- /timeSection -->

      <div class="mb-3">
        <label class="form-label fw-semibold">기타</label>
        <div class="d-flex flex-wrap gap-3">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_holiday" name="is_holiday" value="1"
                   <?= ($log['is_holiday'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_holiday">
              휴일근로
              <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
                 title="법정 공휴일 또는 주휴일에 근로한 경우"></i>
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_late" name="is_late" value="1"
                   <?= ($log['is_late'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_late">지각</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_early_leave" name="is_early_leave" value="1"
                   <?= ($log['is_early_leave'] ?? 0) ? 'checked' : '' ?>
                   onchange="toggleEmployerEarly()">
            <label class="form-check-label" for="is_early_leave">조퇴</label>
          </div>
        </div>
        <!-- 사업주 귀책 조기퇴근: 조퇴 체크 시에만 표시 -->
        <div id="employerEarlyWrap" class="mt-2 ps-2 border-start border-warning"
             style="display:<?= ($log['is_early_leave'] ?? 0) ? 'block' : 'none' ?>">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_employer_early_leave"
                   name="is_employer_early_leave" value="1"
                   <?= ($log['is_employer_early_leave'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="is_employer_early_leave">
              사업주 지시 조기퇴근
              <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
                 title="손님 없음 등 매장 사정으로 사업주가 조기 퇴근을 지시한 경우. 5인 이상 사업장에서는 못 일한 시간의 70%를 휴업수당으로 지급해야 합니다."></i>
            </label>
          </div>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">메모</label>
        <input type="text" name="memo" class="form-control"
               value="<?= h($log['memo'] ?? '') ?>">
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-success px-4">
          <i class="bi bi-save me-1"></i>저장
        </button>
        <a href="<?= url('work_logs', 'index', ['employee_id' => $log['employee_id'] ?? 0]) ?>"
           class="btn btn-outline-secondary">취소</a>
      </div>
    </form>
  </div>
</div>

<script>
function toggleEmployerEarly() {
    var isEarly = document.getElementById('is_early_leave').checked;
    document.getElementById('employerEarlyWrap').style.display = isEarly ? 'block' : 'none';
    if (!isEarly) document.getElementById('is_employer_early_leave').checked = false;
}
function toggleAbsent(isAbsent) {
    document.getElementById('timeSection').style.display = isAbsent ? 'none' : 'block';
}
function toggleBreakAuto(isAuto) {
    document.getElementById('breakManual').style.display = isAuto ? 'none' : 'block';
}
// 실시간 근무시간 미리보기
function updatePreview() {
    const start = document.querySelector('[name=start_time]').value;
    const end   = document.querySelector('[name=end_time]').value;
    const preview = document.getElementById('workPreview');
    const text    = document.getElementById('previewText');
    if (!start || !end) { preview.style.display = 'none'; return; }

    let [sh, sm] = start.split(':').map(Number);
    let [eh, em] = end.split(':').map(Number);
    let startMin = sh * 60 + sm;
    let endMin   = eh * 60 + em;
    let overnight = false;
    if (endMin <= startMin) { endMin += 1440; overnight = true; }
    const workMin = endMin - startMin;
    const autoBreak = workMin >= 480 ? 60 : workMin >= 240 ? 30 : 0;
    const paidMin   = workMin - autoBreak;

    const h = m => `${Math.floor(m/60)}시간${m%60 ? ' '+m%60+'분' : ''}`;
    text.innerHTML =
        `총 근무: <strong>${h(workMin)}</strong>` +
        (overnight ? ' <span class="badge bg-info">익일 퇴근</span>' : '') +
        ` · 자동 휴게: ${h(autoBreak)} · 유급 근무: <strong>${h(paidMin)}</strong>`;

    // 야간 계산
    const nightStart = 22 * 60, nightEnd = 30 * 60; // 22:00 ~ 06:00 (+1440)
    const nsOverlap = Math.max(0, Math.min(endMin, nightEnd) - Math.max(startMin, nightStart));
    if (nsOverlap > 0) {
        text.innerHTML += ` · 야간: <span class="text-primary">${h(nsOverlap)}</span>`;
    }

    preview.style.display = 'block';
}
document.querySelector('[name=start_time]').addEventListener('input', updatePreview);
document.querySelector('[name=end_time]').addEventListener('input', updatePreview);
toggleAbsent(document.getElementById('is_absent').checked);
updatePreview();
</script>
