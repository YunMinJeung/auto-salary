<div class="d-flex align-items-center mb-4">
  <h1 class="h3 mb-0">
    <i class="bi bi-bank me-2 text-primary"></i>퇴직금 계산
  </h1>
  <span class="badge ms-3 small fw-normal" style="background:var(--c-cream);color:var(--c-dark)">
    근로자퇴직급여 보장법 제8조
  </span>
</div>

<!-- 직원 선택 폼 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body">
    <form method="GET" action="<?= url('severance') ?>" class="row g-3 align-items-end">
      <input type="hidden" name="c" value="severance">
      <div class="col-md-5">
        <label class="form-label fw-semibold">직원 선택</label>
        <select name="employee_id" class="form-select" onchange="this.form.submit()">
          <option value="">-- 직원을 선택하세요 --</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>"
                  <?= $emp['id'] == $employeeId ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
            <?= $emp['employment_end_date'] ? ' (퇴사 '.$emp['employment_end_date'].')' : '' ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($employeeId): ?>
      <div class="col-md-4">
        <label class="form-label fw-semibold">
          퇴직일
          <i class="bi bi-question-circle text-muted" data-bs-toggle="tooltip"
             title="마지막 근무일의 다음날 (퇴직일 당일은 근무하지 않은 날)"></i>
        </label>
        <input type="date" name="retire_date" class="form-control"
               value="<?= h($retireDate) ?>">
      </div>
      <div class="col-md-3">
        <button type="submit" class="btn btn-primary w-100">
          <i class="bi bi-calculator me-1"></i>계산
        </button>
      </div>
      <?php endif; ?>
    </form>
  </div>
</div>

<?php if ($result): ?>

<?php $r = $result; ?>

<!-- 적격 여부 -->
<?php if (!$r['eligible']): ?>
<div class="alert alert-warning d-flex align-items-start gap-2 mb-4">
  <i class="bi bi-exclamation-triangle-fill fs-5 mt-1"></i>
  <div>
    <strong>퇴직금 지급 대상이 아닙니다.</strong>
    <ul class="mb-0 mt-1">
      <?php foreach ($r['ineligible_reasons'] as $reason): ?>
      <li><?= h($reason) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php endif; ?>

<div class="row g-4">

  <!-- 왼쪽: 근속·임금 상세 -->
  <div class="col-lg-7">

    <!-- 근속기간 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-calendar-range me-1"></i>근속기간
      </div>
      <div class="card-body">
        <div class="row text-center g-3">
          <div class="col-4">
            <div class="small text-muted">입사일</div>
            <div class="fw-semibold"><?= h($r['start_date']) ?></div>
          </div>
          <div class="col-4">
            <div class="small text-muted">퇴직일</div>
            <div class="fw-semibold"><?= h($r['retire_date']) ?></div>
          </div>
          <div class="col-4">
            <div class="small text-muted">재직일수</div>
            <div class="fw-semibold"><?= number_format($r['service_days']) ?>일</div>
          </div>
        </div>
        <div class="text-center mt-3 py-2 rounded" style="background:var(--c-cream)">
          <span class="fs-5 fw-bold" style="color:var(--c-dark)">
            <?= $r['service_years'] ?>년
            <?php if ($r['service_months']): ?>
            <?= $r['service_months'] ?>개월
            <?php endif; ?>
            <?php if ($r['service_day_rem']): ?>
            <?= $r['service_day_rem'] ?>일
            <?php endif; ?>
          </span>
        </div>
      </div>
    </div>

    <!-- 3개월 평균임금 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-receipt me-1"></i>평균임금 산정
        <small class="text-muted fw-normal ms-2"><?= h($r['period_start']) ?> ~ <?= h($r['period_end']) ?> (<?= $r['calendar_days'] ?>일)</small>
      </div>
      <div class="card-body p-0">
        <?php if ($r['no_log_data']): ?>
        <div class="p-3">
          <div class="alert alert-info mb-0">
            <i class="bi bi-info-circle me-1"></i>
            해당 기간에 등록된 근무 기록이 없습니다.
            평균임금 대신 <strong>통상임금</strong>으로 계산합니다.
          </div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
          <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
              <tr>
                <th>기간</th>
                <th class="text-end">유급근무시간</th>
                <th class="text-end">기본급</th>
                <th class="text-end">주휴수당</th>
                <th class="text-end">가산수당</th>
                <th class="text-end">소계</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($r['weeks'] as $w): ?>
              <tr>
                <td class="small"><?= substr($w['period_start'],5) ?> ~ <?= substr($w['period_end'],5) ?></td>
                <td class="text-end small"><?= minutesToHoursStr($w['paid_work_minutes']) ?></td>
                <td class="text-end small"><?= formatWon($w['base_pay']) ?></td>
                <td class="text-end small"><?= formatWon($w['weekly_holiday_pay']) ?></td>
                <td class="text-end small"><?= formatWon($w['night_premium'] + $w['overtime_premium'] + $w['holiday_premium']) ?></td>
                <td class="text-end fw-semibold small"><?= formatWon($w['total_pay']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-semibold">
              <tr>
                <td colspan="5" class="text-end">3개월 총임금</td>
                <td class="text-end" style="color:var(--c-teal)"><?= formatWon($r['total_wage_3m']) ?></td>
              </tr>
            </tfoot>
          </table>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 평균임금 vs 통상임금 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-arrow-left-right me-1"></i>일급 비교
        <small class="text-muted fw-normal ms-1">(높은 쪽 적용)</small>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-6">
            <div class="p-3 rounded text-center <?= !$r['used_ordinary'] ? 'border border-2' : '' ?>"
                 style="<?= !$r['used_ordinary'] ? 'border-color:var(--c-teal)!important;background:rgba(0,108,103,.07)' : 'background:var(--c-cream)' ?>">
              <div class="small text-muted mb-1">평균임금 일급</div>
              <div class="fs-5 fw-bold <?= !$r['used_ordinary'] ? '' : 'text-muted' ?>">
                <?= number_format((int)round($r['avg_daily_wage'])) ?>원
              </div>
              <div class="small text-muted mt-1">
                <?= formatWon($r['total_wage_3m']) ?> ÷ <?= $r['calendar_days'] ?>일
              </div>
              <?php if (!$r['used_ordinary']): ?>
              <span class="badge mt-2" style="background:var(--c-teal)">적용</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="col-6">
            <div class="p-3 rounded text-center <?= $r['used_ordinary'] ? 'border border-2' : '' ?>"
                 style="<?= $r['used_ordinary'] ? 'border-color:var(--c-teal)!important;background:rgba(0,108,103,.07)' : 'background:var(--c-cream)' ?>">
              <div class="small text-muted mb-1">통상임금 일급</div>
              <div class="fs-5 fw-bold <?= $r['used_ordinary'] ? '' : 'text-muted' ?>">
                <?= number_format((int)round($r['ordinary_daily_wage'])) ?>원
              </div>
              <?php
                $wh = (float)$employee['weekly_scheduled_hours'];
                $wd = max(1,(int)$employee['weekly_scheduled_days']);
                $dh = round($wh/$wd,1);
              ?>
              <div class="small text-muted mt-1">
                <?= number_format($employee['hourly_wage']) ?>원 × <?= $dh ?>시간
              </div>
              <?php if ($r['used_ordinary']): ?>
              <span class="badge mt-2" style="background:var(--c-teal)">적용</span>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php if ($r['used_ordinary']): ?>
        <div class="alert alert-info mt-3 mb-0 small">
          <i class="bi bi-info-circle me-1"></i>
          평균임금이 통상임금보다 낮아 <strong>통상임금</strong>으로 대체합니다. (근기법 제2조 제2항)
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div><!-- /col-lg-7 -->

  <!-- 오른쪽: 최종 결과 -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm sticky-top" style="top:80px">
      <div class="card-header fw-semibold text-white" style="background:var(--c-dark)">
        <i class="bi bi-cash-stack me-1"></i>퇴직금 계산 결과
      </div>
      <div class="card-body p-4">

        <div class="mb-3 pb-3 border-bottom">
          <div class="text-muted small mb-1">적용 일급</div>
          <div class="fs-4 fw-bold" style="color:var(--c-teal)">
            <?= number_format((int)round($r['effective_daily_wage'])) ?>원
          </div>
          <div class="small text-muted">
            <?= $r['used_ordinary'] ? '통상임금 기준' : '평균임금 기준' ?>
          </div>
        </div>

        <div class="mb-3 small">
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">공식</span>
            <span>일급 × 30 × (재직일수 ÷ 365)</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">일급</span>
            <span><?= number_format((int)round($r['effective_daily_wage'])) ?>원</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">재직일수</span>
            <span><?= number_format($r['service_days']) ?>일</span>
          </div>
          <div class="d-flex justify-content-between py-1">
            <span class="text-muted">÷ 365</span>
            <span><?= round($r['service_days'] / 365, 4) ?>년</span>
          </div>
        </div>

        <div class="text-center py-3 rounded mt-2 <?= !$r['eligible'] ? 'opacity-50' : '' ?>"
             style="background:var(--c-cream)">
          <div class="text-muted small mb-1">예상 퇴직금</div>
          <div class="fw-bold" style="font-size:2rem;color:var(--c-dark)">
            <?= formatWon($r['severance_pay']) ?>
          </div>
          <?php if (!$r['eligible']): ?>
          <div class="small text-danger mt-1">지급 요건 미충족</div>
          <?php endif; ?>
        </div>

        <div class="mt-3 p-3 rounded small text-muted" style="background:#f8f9fa">
          <i class="bi bi-exclamation-circle me-1"></i>
          본 계산은 참고용 예상 금액입니다. 실제 지급액은 퇴직 전 3개월간
          실제 지급된 임금을 기준으로 산정하며, 전문가 확인을 권장합니다.
        </div>
      </div>
    </div>
  </div><!-- /col-lg-5 -->

</div><!-- /row -->

<?php endif; // $result ?>
