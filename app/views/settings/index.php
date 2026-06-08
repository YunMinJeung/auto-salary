<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-gear-fill me-2 text-secondary"></i>사업장 설정</h1>
</div>

<div class="row g-4">

<!-- ── 왼쪽: 사업장 기본 설정 ─────────────────── -->
<div class="col-lg-6">
  <div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold" style="background:var(--c-cream)">
      <i class="bi bi-building me-1"></i>사업장 기본 설정
    </div>
    <div class="card-body">
      <form method="post" action="<?= url('settings') ?>">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label fw-semibold">사업장명</label>
          <input type="text" name="business_name" class="form-control"
                 value="<?= h($settings['business_name']) ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">
            상시근로자 수
            <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
               title="5인 이상 사업장은 연장·야간·휴일 가산수당이 법적으로 의무 적용됩니다."></i>
          </label>
          <div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="employee_count_type"
                     id="et_over5" value="over5"
                     <?= $settings['employee_count_type'] === 'over5' ? 'checked' : '' ?>>
              <label class="form-check-label" for="et_over5">5인 이상</label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="employee_count_type"
                     id="et_under5" value="under5"
                     <?= $settings['employee_count_type'] === 'under5' ? 'checked' : '' ?>>
              <label class="form-check-label" for="et_under5">5인 미만</label>
            </div>
          </div>
        </div>

        <hr>
        <h6 class="text-muted mb-3 text-uppercase small fw-bold">가산수당 적용</h6>
        <div class="alert alert-info small py-2 mb-3">
          5인 이상 사업장은 연장·야간·휴일 가산수당이 의무입니다.
          5인 미만 사업장도 체크하여 계산에 포함할 수 있습니다.
        </div>

        <?php
        $premiums = [
            ['apply_overtime_premium', '연장근로 가산수당', '50% 가산'],
            ['apply_night_premium',    '야간근로 가산수당', '22:00~06:00, 50% 가산'],
            ['apply_holiday_premium',  '휴일근로 가산수당', '50% 가산'],
        ];
        foreach ($premiums as [$key, $label, $hint]):
        ?>
        <div class="mb-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="<?= $key ?>"
                   name="<?= $key ?>" value="1"
                   <?= $settings[$key] ? 'checked' : '' ?>>
            <label class="form-check-label" for="<?= $key ?>">
              <?= $label ?> <span class="text-muted small">(<?= $hint ?>)</span>
            </label>
          </div>
        </div>
        <?php endforeach; ?>

        <hr>
        <h6 class="text-muted mb-3 text-uppercase small fw-bold">자동 계산</h6>

        <div class="mb-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="auto_break_enabled"
                   name="auto_break_enabled" value="1"
                   <?= $settings['auto_break_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="auto_break_enabled">
              휴게시간 자동 계산
              <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
                 title="4시간 이상: 30분, 8시간 이상: 60분 자동 차감"></i>
            </label>
          </div>
        </div>
        <div class="mb-4">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="auto_weekly_holiday_enabled"
                   name="auto_weekly_holiday_enabled" value="1"
                   <?= $settings['auto_weekly_holiday_enabled'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="auto_weekly_holiday_enabled">
              주휴수당 자동 계산
              <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
                 title="1주 소정근로시간 15시간 이상 + 개근 시 자동 계산"></i>
            </label>
          </div>
        </div>

        <!-- 숨겨진 필드: 기존 minimum_wage 컬럼 유지 (기존 계산 호환) -->
        <input type="hidden" name="minimum_wage_year" value="<?= h($settings['minimum_wage_year']) ?>">
        <input type="hidden" name="minimum_wage"      value="<?= h($settings['minimum_wage']) ?>">

        <button type="submit" class="btn btn-primary px-4">
          <i class="bi bi-save me-1"></i>저장
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ── 오른쪽: 연도별 최저시급 관리 ───────────── -->
<div class="col-lg-6">
  <div class="card border-0 shadow-sm">
    <div class="card-header fw-semibold d-flex justify-content-between align-items-center"
         style="background:var(--c-cream)">
      <span><i class="bi bi-currency-won me-1"></i>연도별 최저시급 관리</span>
      <button class="btn btn-sm btn-outline-primary py-0"
              data-bs-toggle="modal" data-bs-target="#addMinWageModal">
        <i class="bi bi-plus me-1"></i>추가
      </button>
    </div>
    <div class="card-body p-0">
      <?php if (empty($minWages)): ?>
      <div class="text-center py-4 text-muted small">
        등록된 최저시급 데이터가 없습니다.
      </div>
      <?php else: ?>
      <div class="table-responsive">
        <table class="table table-sm align-middle mb-0 small">
          <thead class="table-light">
            <tr>
              <th class="ps-3">연도</th>
              <th>시급</th>
              <th>월환산액</th>
              <th>적용 기간</th>
              <th class="pe-3 text-end">관리</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $currentYear = (int) date('Y');
            foreach ($minWages as $mw):
              $isCurrent = (int)$mw['year'] === $currentYear;
            ?>
            <tr class="<?= $isCurrent ? 'table-success' : '' ?>">
              <td class="ps-3 fw-bold">
                <?= $mw['year'] ?>
                <?php if ($isCurrent): ?>
                  <span class="badge bg-success-subtle text-success border border-success-subtle ms-1"
                        style="font-size:.65rem">적용중</span>
                <?php endif; ?>
              </td>
              <td class="fw-semibold"><?= number_format($mw['hourly_wage']) ?>원</td>
              <td class="text-muted">
                <?= $mw['monthly_wage'] > 0 ? number_format($mw['monthly_wage']) . '원' : '—' ?>
              </td>
              <td class="text-muted">
                <?php if ($mw['effective_from'] && $mw['effective_to']): ?>
                  <?= substr($mw['effective_from'], 0, 7) ?> ~
                  <?= substr($mw['effective_to'], 0, 7) ?>
                <?php else: ?>—
                <?php endif; ?>
              </td>
              <td class="pe-3 text-end">
                <div class="d-flex gap-1 justify-content-end">
                  <button class="btn btn-xs btn-outline-secondary"
                          style="font-size:.75rem;padding:.15rem .4rem"
                          onclick="editMinWage(<?= htmlspecialchars(json_encode($mw), ENT_QUOTES) ?>)">
                    수정
                  </button>
                  <form method="post" action="<?= url('settings', 'min_wage_delete') ?>"
                        onsubmit="return confirm('<?= $mw['year'] ?>년 최저시급 데이터를 삭제할까요?')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $mw['id'] ?>">
                    <button type="submit" class="btn btn-xs btn-outline-danger"
                            style="font-size:.75rem;padding:.15rem .4rem">삭제</button>
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
    <div class="card-footer bg-white small text-muted py-2">
      <i class="bi bi-info-circle me-1"></i>
      급여 계산 시 <strong>근무일 기준 연도</strong>의 최저시급이 자동 적용됩니다.
      해당 연도 데이터가 없으면 직전 연도 값이 사용됩니다.
    </div>
  </div>
</div>

</div><!-- /row -->

<!-- ── 최저시급 추가/수정 모달 ──────────────── -->
<div class="modal fade" id="addMinWageModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="minWageModalTitle">최저시급 추가</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" id="minWageForm" action="<?= url('settings', 'min_wage_save') ?>">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-4">
              <label class="form-label fw-semibold">연도 <span class="text-danger">*</span></label>
              <input type="number" name="year" id="mw_year" class="form-control"
                     value="<?= date('Y') + 1 ?>" min="2000" max="2100" required>
            </div>
            <div class="col-8">
              <label class="form-label fw-semibold">시급 <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" name="hourly_wage" id="mw_hourly" class="form-control"
                       placeholder="예) 10320" min="1" required oninput="calcMonthly()">
                <span class="input-group-text">원</span>
              </div>
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">
                월환산액
                <span class="text-muted small fw-normal">(시급 × 209시간, 빈칸 시 자동 계산)</span>
              </label>
              <div class="input-group">
                <input type="number" name="monthly_wage" id="mw_monthly" class="form-control"
                       placeholder="예) 2156880" min="0">
                <span class="input-group-text">원</span>
              </div>
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">적용 시작일</label>
              <input type="date" name="effective_from" id="mw_from" class="form-control">
            </div>
            <div class="col-6">
              <label class="form-label fw-semibold">적용 종료일</label>
              <input type="date" name="effective_to" id="mw_to" class="form-control">
            </div>
            <div class="col-12">
              <label class="form-label fw-semibold">메모</label>
              <input type="text" name="memo" id="mw_memo" class="form-control"
                     placeholder="고용노동부 고시 등">
            </div>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="bi bi-save me-1"></i>저장
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function calcMonthly() {
  var h = parseInt(document.getElementById('mw_hourly').value) || 0;
  document.getElementById('mw_monthly').placeholder = h > 0 ? (h * 209).toLocaleString() : '예) 2156880';
}

function editMinWage(data) {
  document.getElementById('minWageModalTitle').textContent = data.year + '년 최저시급 수정';
  document.getElementById('mw_year').value    = data.year;
  document.getElementById('mw_hourly').value  = data.hourly_wage;
  document.getElementById('mw_monthly').value = data.monthly_wage > 0 ? data.monthly_wage : '';
  document.getElementById('mw_from').value    = data.effective_from  || '';
  document.getElementById('mw_to').value      = data.effective_to    || '';
  document.getElementById('mw_memo').value    = data.memo            || '';
  calcMonthly();
  new bootstrap.Modal(document.getElementById('addMinWageModal')).show();
}

// 모달 닫힐 때 폼 초기화
document.getElementById('addMinWageModal').addEventListener('hidden.bs.modal', function() {
  document.getElementById('minWageModalTitle').textContent = '최저시급 추가';
  document.getElementById('minWageForm').reset();
  document.getElementById('mw_year').value = <?= date('Y') + 1 ?>;
  calcMonthly();
});
</script>
