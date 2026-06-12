<?php $isEdit = $action === 'edit'; ?>
<?php if ($isEdit && !empty($comparison)):
  $hasWarning = $comparison['warn_15h'] || $comparison['warn_60h'] || $comparison['warn_mismatch'];
  $hasData    = $comparison['weeks_with_data'] >= 1;
?>
<?php if ($hasWarning): ?>
<div class="alert alert-warning d-flex gap-3 mb-4" role="alert" style="border-left:4px solid #ffc107">
  <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1 fs-5 text-warning"></i>
  <div>
    <div class="fw-bold mb-2">계약시간 vs 실제 근무 불일치</div>
    <?php if ($comparison['warn_mismatch']): ?>
    <p class="mb-1 small">
      최근 4주 실제 평균 <strong><?= $comparison['actual_avg_weekly'] ?>시간/주</strong>으로,
      계약상 소정근로시간 <strong><?= $comparison['contractual_weekly'] ?>시간/주</strong>와
      <strong><?= abs(round($comparison['actual_avg_weekly'] - $comparison['contractual_weekly'], 1)) ?>시간</strong> 차이가 납니다.
    </p>
    <?php endif; ?>
    <?php if ($comparison['warn_15h']): ?>
    <p class="mb-1 small text-danger">
      <i class="bi bi-shield-exclamation me-1"></i>
      계약 주 15시간 미만 &rarr; 실제 평균 <?= $comparison['actual_avg_weekly'] ?>시간/주 &mdash;
      <strong>주휴수당 및 4대보험 가입 의무</strong> 재검토 필요
    </p>
    <?php endif; ?>
    <?php if ($comparison['warn_60h']): ?>
    <p class="mb-1 small text-danger">
      <i class="bi bi-shield-exclamation me-1"></i>
      계약 월 <?= $comparison['contractual_monthly'] ?>시간 미만 &rarr; 이번 달 실제 <?= $comparison['actual_month_hours'] ?>시간 &mdash;
      <strong>4대보험 가입 의무</strong> 재검토 필요
    </p>
    <?php endif; ?>
    <p class="mb-0 small text-muted mt-1">
      주휴수당, 4대보험, 근로계약서 수정 여부를 확인하세요.
    </p>

    <?php if (!empty($comparison['actual_weeks'])): ?>
    <details class="mt-2">
      <summary class="small text-muted" style="cursor:pointer">최근 4주 상세 보기</summary>
      <table class="table table-sm table-bordered mt-2 small" style="max-width:400px">
        <thead class="table-light">
          <tr>
            <th>기간</th>
            <th class="text-end">실제</th>
            <th class="text-end">계약</th>
            <th class="text-end">차이</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($comparison['actual_weeks'] as $w):
            $diff = round($w['actual_hours'] - $comparison['contractual_weekly'], 1);
          ?>
          <tr>
            <td class="text-nowrap"><?= substr($w['week_start'], 5) ?>~<?= substr($w['week_end'], 5) ?></td>
            <td class="text-end"><?= $w['actual_hours'] ?>h</td>
            <td class="text-end text-muted"><?= $comparison['contractual_weekly'] ?>h</td>
            <td class="text-end <?= $diff > 0 ? 'text-danger' : ($diff < 0 ? 'text-primary' : '') ?>">
              <?= $diff > 0 ? '+' : '' ?><?= $diff ?>h
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </details>
    <?php endif; ?>
  </div>
</div>
<?php elseif ($hasData && $comparison['weeks_with_data'] >= 2): ?>
<div class="alert alert-success d-flex align-items-center gap-2 mb-4 py-2 small" role="alert">
  <i class="bi bi-check-circle-fill text-success"></i>
  최근 4주 평균 실제 근무 <strong><?= $comparison['actual_avg_weekly'] ?>시간/주</strong> —
  계약상 소정근로시간 <strong><?= $comparison['contractual_weekly'] ?>시간/주</strong>와 일치합니다.
</div>
<?php endif; ?>
<?php endif; ?>

<div class="d-flex align-items-center mb-4">
  <a href="<?= url('members') ?>" class="btn btn-sm btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0">
    <i class="bi bi-person-plus-fill me-2 text-primary"></i>
    <?= $isEdit ? '직원 수정' : '직원 등록' ?>
  </h1>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger mb-3">
  <?php foreach ($errors as $e): ?>
  <div><i class="bi bi-exclamation-circle me-1"></i><?= h($e) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if (!empty($warnings)): ?>
<div class="alert alert-warning mb-3">
  <?php foreach ($warnings as $w): ?>
  <div><i class="bi bi-exclamation-triangle me-1"></i><?= h($w) ?></div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<form method="post"
      action="<?= $isEdit ? url('members', 'edit', ['id' => $member['id']]) : url('members', 'create') ?>">
<?= csrf_field() ?>

<div class="alert alert-info d-flex gap-2 py-2 px-3 mb-4 small">
  <i class="bi bi-shield-check flex-shrink-0 mt-1 text-info"></i>
  <div>
    <strong>개인정보 안내</strong> — 이 서비스는 주민등록번호를 수집하지 않습니다.
    급여명세서 발급에는 이름과 생년월일 또는 직원번호를 사용합니다. 계좌번호는 수집하지 않습니다.
  </div>
</div>

<div class="row g-4">

  <!-- 기본 정보 -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-person me-1"></i>기본 정보
      </div>
      <div class="card-body">

        <div class="mb-3">
          <label class="form-label fw-semibold">직원명 <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= h($member['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">연락처 <span class="text-muted small fw-normal">(선택)</span></label>
          <input type="tel" name="phone" class="form-control"
                 value="<?= h($member['phone'] ?? '') ?>" placeholder="010-0000-0000">
          <div class="form-text">알바생 모바일 출퇴근 초대 기능을 사용할 때만 필요합니다.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">시급 <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" name="hourly_wage" class="form-control"
                   value="<?= h($member['hourly_wage'] ?? MinimumWage::currentHourlyWage()) ?>"
                   min="1" required>
            <span class="input-group-text">원/시간</span>
          </div>
          <?php $curMinWage = MinimumWage::currentHourlyWage(); ?>
          <div class="form-text">
            <?= date('Y') ?>년 법정 최저시급: <?= number_format($curMinWage) ?>원
          </div>
          <div id="wage-warning" class="alert alert-warning d-none mt-2 mb-0 py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            입력한 시급이 최저임금(<?= number_format((int)($settings['minimum_wage'] ?? 10320)) ?>원)에 미달합니다.
            저장은 가능하지만 노무 리스크가 기록됩니다.
          </div>
          <?php if ($isEdit): ?>
          <div class="border rounded p-3 mt-2 mb-0" style="background:#f8f9fa">
            <div class="small fw-semibold text-muted mb-2">
              <i class="bi bi-clock-history me-1"></i>시급 변경 시 적용일
            </div>
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label small mb-1">적용 시작일</label>
                <input type="date" name="wage_effective_from" class="form-control form-control-sm"
                       value="<?= date('Y-m-d') ?>">
              </div>
              <div class="col-6">
                <label class="form-label small mb-1">변경 사유 <span class="text-muted fw-normal">(선택)</span></label>
                <input type="text" name="wage_change_memo" class="form-control form-control-sm"
                       placeholder="예: 인상 협의">
              </div>
            </div>
            <div class="form-text mt-1">시급을 변경하지 않으면 이 필드는 무시됩니다. 정확한 급여 계산을 위해 <strong>주 시작일(월요일) 또는 월 첫날</strong>로 설정하세요.</div>
          </div>
          <?php endif; ?>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">근무 시작시간</label>
            <input type="time" name="work_start_time" id="calcStart" class="form-control"
                   value="<?= h(substr($member['work_start_time'] ?? '09:00', 0, 5)) ?>"
                   oninput="updateScheduleCalc()">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">근무 종료시간</label>
            <input type="time" name="work_end_time" id="calcEnd" class="form-control"
                   value="<?= h(substr($member['work_end_time'] ?? '18:00', 0, 5)) ?>"
                   oninput="updateScheduleCalc()">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">휴게시간</label>
            <div class="input-group">
              <input type="number" name="daily_break_minutes" id="calcBreak" class="form-control"
                     min="0" max="480" step="5"
                     value="<?= h($member['daily_break_minutes'] ?? '') ?>"
                     oninput="updateScheduleCalc()">
              <span class="input-group-text">분</span>
            </div>
            <div class="form-text" id="calcBreakHint"></div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">주 소정근로일</label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_days" id="weeklyDaysInput" class="form-control"
                     value="<?= h($member['weekly_scheduled_days'] ?? 5) ?>"
                     min="1" max="7" oninput="updateScheduleCalc()">
              <span class="input-group-text">일</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">주 소정근로시간</label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_hours" id="weeklyHoursInput" class="form-control"
                     value="<?= h($member['weekly_scheduled_hours'] ?? 40) ?>"
                     min="0" max="80" step="any">
              <span class="input-group-text">시간</span>
            </div>
            <div class="form-text" id="scheduleCalcResult"></div>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="weekly_holiday_enabled"
                   name="weekly_holiday_enabled" value="1"
                   <?= ($member['weekly_holiday_enabled'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="weekly_holiday_enabled">주휴수당 계산 대상</label>
          </div>
        </div>

        <hr>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">입사일</label>
            <input type="date" name="employment_start_date" id="empStartDate" class="form-control"
                   value="<?= h($member['employment_start_date'] ?? date('Y-m-d')) ?>">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">계약 종료일 <span class="text-muted small fw-normal">(선택)</span></label>
            <input type="date" name="employment_end_date" id="employment_end_date" class="form-control"
                   value="<?= h($member['employment_end_date'] ?? '') ?>">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">생년월일 <span class="text-muted small fw-normal">(선택)</span></label>
            <input type="date" name="date_of_birth" id="dateOfBirth" class="form-control"
                   value="<?= h($member['date_of_birth'] ?? '') ?>">
            <div class="form-text">65세 이후 신규 고용 여부 자동 판단에 사용됩니다.</div>
            <div class="form-text">생년월일 또는 직원번호는 급여명세서에서 근로자를 구분하기 위해 사용됩니다. 주민등록번호는 수집하지 않습니다.</div>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">직원번호 <span class="text-muted small fw-normal">(선택)</span></label>
            <input type="text" name="employee_code" class="form-control"
                   value="<?= h($member['employee_code'] ?? '') ?>" placeholder="예: EMP-001">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">근무 형태 <span class="text-muted small fw-normal">(선택)</span></label>
            <?php $empType = $member['employment_type'] ?? ''; ?>
            <select name="employment_type" class="form-select">
              <option value="">선택 안 함</option>
              <option value="PART_TIME" <?= $empType === 'PART_TIME' ? 'selected' : '' ?>>시간제 (파트타임)</option>
              <option value="FULL_TIME" <?= $empType === 'FULL_TIME' ? 'selected' : '' ?>>전일제</option>
              <option value="TEMPORARY" <?= $empType === 'TEMPORARY' ? 'selected' : '' ?>>기간제</option>
              <option value="DAILY"     <?= $empType === 'DAILY'     ? 'selected' : '' ?>>일용직</option>
            </select>
          </div>
        </div>

        <!-- 수습기간 -->
        <?php
          $hasTrial     = !empty($member['trial_end_date']);
          $trialWageVal = $member['trial_hourly_wage'] ?? '';
        ?>
        <div class="mb-2">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="trialEnabled"
                   <?= $hasTrial ? 'checked' : '' ?>
                   onchange="toggleTrial(this.checked)">
            <label class="form-check-label fw-semibold" for="trialEnabled">수습기간 적용</label>
          </div>
        </div>
        <div id="trialSection" class="p-3 rounded mb-3" style="background:var(--c-cream);display:<?= $hasTrial ? 'block' : 'none' ?>">
          <div class="alert alert-warning small py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-1"></i>
            수습 감액(최저시급 90%)은 <strong>계약기간 1년 이상 + 수습 3개월 이내</strong>일 때만 적용 가능합니다.
            편의점·카페 스태프 등 <strong>단순노무직은 계약기간 무관 감액 불가</strong>입니다.
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label fw-semibold">수습 종료일</label>
              <input type="date" name="trial_end_date" id="trialEndDate" class="form-control"
                     value="<?= h($member['trial_end_date'] ?? '') ?>">
              <div class="form-text" id="trialDurationHint"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">수습 시급</label>
              <div class="input-group">
                <input type="number" name="trial_hourly_wage" id="trialWageInput" class="form-control"
                       value="<?= h($trialWageVal) ?>" min="1"
                       oninput="updateTrialHints()">
                <span class="input-group-text">원/시간</span>
              </div>
              <div class="form-text" id="trialWageHint"></div>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_active"
                   name="is_active" value="1"
                   <?= ($member['is_active'] ?? 1) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_active">재직 중 (출퇴근 기록 허용)</label>
          </div>
        </div>

        <div class="mb-3">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="is_minor"
                   name="is_minor" value="1"
                   <?= ($member['is_minor'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_minor">
              연소근로자 (만 18세 미만)
              <span class="badge bg-warning text-dark ms-1" style="font-size:.7rem">친권자 동의서 필요</span>
            </label>
          </div>
          <div class="form-text" id="minorHint" style="display:none">
            <i class="bi bi-info-circle text-warning me-1"></i>
            「근로기준법」 제66조에 따라 연소근로자 근로계약 시 친권자 또는 후견인 동의서를 갖추어야 합니다.
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">메모</label>
          <textarea name="memo" class="form-control" rows="2"><?= h($member['memo'] ?? '') ?></textarea>
          <div class="form-text text-danger-emphasis">
            <i class="bi bi-shield-exclamation me-1"></i>주민등록번호, 계좌번호, 건강정보 등 민감한 개인정보는 입력하지 마세요.
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- 앱 계정 연결 -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-person-badge me-1"></i>앱 계정 연결
      </div>
      <div class="card-body py-3">
        <?php if (!empty($member['user_id'])): ?>
          <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>앱 계정 연결됨</span>
          <?php if (!empty($member['user_email'])): ?>
          <div class="small text-muted mt-2"><?= h($member['user_email']) ?></div>
          <?php endif; ?>
        <?php elseif ($isEdit): ?>
          <?php $as = $member['account_status'] ?? 'no_account'; ?>
          <?php if (!empty($lastInviteLink)): ?>
          <div class="alert alert-success py-2 mb-3">
            <div class="fw-semibold small mb-1">📎 <?= h($lastInviteMember) ?>님 초대 링크</div>
            <input type="text" class="form-control form-control-sm font-monospace mb-2"
                   value="<?= h($lastInviteLink) ?>" readonly onclick="this.select()">
            <div class="text-muted" style="font-size:.75rem">7일 내 유효. 직원에게 링크를 직접 공유하세요.</div>
          </div>
          <?php elseif ($as === 'invited' && !empty($pendingInvite)): ?>
          <div class="alert alert-warning py-2 mb-3">
            <div class="fw-semibold small mb-1"><i class="bi bi-send me-1"></i>초대 대기 중</div>
            <div class="text-muted small">만료: <?= h(substr($pendingInvite['expires_at'], 0, 10)) ?></div>
          </div>
          <div class="d-flex gap-2">
            <a href="<?= url('invite', 'form') ?>?store_member_id=<?= (int)$member['id'] ?>"
               class="btn btn-outline-secondary btn-sm">
              <i class="bi bi-arrow-repeat me-1"></i>재발송
            </a>
            <form method="POST" action="<?= url('invite', 'cancel') ?>" class="d-inline">
              <?= csrf_field() ?>
              <input type="hidden" name="invitation_id" value="<?= (int)$pendingInvite['id'] ?>">
              <input type="hidden" name="store_member_id" value="<?= (int)$member['id'] ?>">
              <button type="submit" class="btn btn-outline-danger btn-sm"
                      onclick="return confirm('초대를 취소하시겠습니까?')">
                <i class="bi bi-x-circle me-1"></i>초대 취소
              </button>
            </form>
          </div>
          <?php else: ?>
          <div class="d-flex gap-2 flex-wrap">
            <a href="<?= url('invite', 'form') ?>?store_member_id=<?= (int)$member['id'] ?>"
               class="btn btn-outline-primary btn-sm">
              <i class="bi bi-link-45deg me-1"></i>초대 링크 보내기
            </a>
            <a href="<?= url('invite', 'form') ?>?store_member_id=<?= (int)$member['id'] ?>&amp;guided=1"
               class="btn btn-outline-warning btn-sm">
              <i class="bi bi-qr-code me-1"></i>가입 도와주기
            </a>
          </div>
          <?php endif; ?>
        <?php else: ?>
          <span class="text-muted small">직원 등록 후 편집 화면에서 초대 링크를 생성할 수 있습니다.</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── 4대보험 가입 의무 체크 ─────────────────────────────────────────── -->
<div class="row g-4 mt-0">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-shield-check me-1"></i>4대보험 가입 의무 체크
        <span class="badge bg-secondary ms-2 fw-normal" style="font-size:.72rem">자동 판단</span>
      </div>
      <div class="card-body">
        <!-- 자동 판단 결과 패널 -->
        <div class="p-3 rounded mb-3" style="background:#f8f9fa;border:1px solid #dee2e6">
          <div class="small fw-semibold text-muted mb-2">
            <i class="bi bi-cpu me-1"></i>시스템 자동 판단 결과
            <span class="fw-normal">(주 소정근로시간 · 입사일 · 계약 종료일 기준)</span>
          </div>
          <div id="ins-check-rows" class="row g-2">
            <div class="col-12 text-muted small">계산 중...</div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">보험 관리 상태</label>
            <select name="user_selected_status" id="insUserStatus" class="form-select">
              <?php
              $us = $member['user_selected_status'] ?? $insuranceSetting['user_selected_status'] ?? 'needs_review';
              foreach (['needs_review' => '확인 필요', 'enrolled' => '가입 처리 완료', 'not_enrolled' => '미가입'] as $v => $l): ?>
              <option value="<?= $v ?>" <?= $us === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
            <div id="insStatusWarning" class="alert alert-danger small py-2 mt-2 mb-0" style="display:none">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              가입 대상 가능성이 높습니다. 미가입 처리 시 <strong>과태료 및 소급 처리</strong> 등 불이익이 발생할 수 있습니다.
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold text-muted">메모 <span class="fw-normal">(선택)</span></label>
            <input type="text" name="insurance_memo" class="form-control"
                   value="<?= h($member['insurance_memo'] ?? $insuranceSetting['memo'] ?? '') ?>"
                   placeholder="ex. 사업장가입자, 지역가입자 전환 예정 등">
          </div>
        </div>

        <div class="small text-muted">
          <i class="bi bi-info-circle me-1"></i>
          정확한 가입 여부는 관할 공단에 확인하세요.
        </div>

        <input type="hidden" name="warning_acknowledged" id="warningAcknowledged" value="0">
      </div>
    </div>
  </div>
</div>

<!-- ── 4대보험 관련 정보 (복수 사업장) ──────────────────────────────── -->
<?php
  $worksOther   = $member['works_at_other_business']           ?? 'UNKNOWN';
  $otherEnrolled= $member['other_business_insurance_enrolled'] ?? 'UNKNOWN';
  $healthType   = $member['health_insurance_type']             ?? 'UNKNOWN';
?>
<div class="row g-4 mt-0">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-buildings me-1"></i>4대보험 관련 정보 (복수 사업장)
      </div>
      <div class="card-body">

        <div class="mb-3">
          <label class="form-label fw-semibold">다른 사업장에서도 근무 중인가요?</label>
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach (['NO' => '아니오', 'YES' => '예', 'UNKNOWN' => '잘 모르겠음'] as $v => $l): ?>
            <div class="form-check">
              <input class="form-check-input multi-works-radio" type="radio"
                     name="works_at_other_business" id="works_<?= $v ?>" value="<?= $v ?>"
                     <?= $worksOther === $v ? 'checked' : '' ?>>
              <label class="form-check-label" for="works_<?= $v ?>"><?= $l ?></label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div id="otherEnrolledSection" class="mb-3 p-3 rounded"
             style="background:#f8f9fa;border:1px solid #dee2e6;display:<?= $worksOther === 'YES' ? 'block' : 'none' ?>">
          <label class="form-label fw-semibold">다른 사업장에서 4대보험에 가입되어 있나요?</label>
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach (['NO' => '아니오', 'YES' => '예', 'UNKNOWN' => '잘 모르겠음'] as $v => $l): ?>
            <div class="form-check">
              <input class="form-check-input" type="radio"
                     name="other_business_insurance_enrolled" id="otherins_<?= $v ?>" value="<?= $v ?>"
                     <?= $otherEnrolled === $v ? 'checked' : '' ?>>
              <label class="form-check-label" for="otherins_<?= $v ?>"><?= $l ?></label>
            </div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-1">
          <label class="form-label fw-semibold">건강보험 가입 유형</label>
          <select name="health_insurance_type" class="form-select" style="max-width:280px">
            <?php foreach (['LOCAL' => '지역가입자', 'EMPLOYEE' => '직장가입자', 'DEPENDENT' => '피부양자', 'UNKNOWN' => '모름'] as $v => $l): ?>
            <option value="<?= $v ?>" <?= $healthType === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <?php
          // ── 복수 사업장 4대보험 안내 (참고용, 법적 확정 아님) ──
          $monthlyMinutes = 0;
          if ($isEdit && !empty($member['id'])) {
              $ms = AttendanceLog::monthSummary((int)$member['id'], date('Y'), date('n'));
              $monthlyMinutes = (int)($ms['total_minutes'] ?? 0);
          }
          $isOverThreshold = $monthlyMinutes >= (60 * 60);

          if ($isOverThreshold) {
              if ($worksOther === 'NO') {
                  $insuranceNotice = ['level' => 'warning', 'msg' => '4대보험 가입 대상 가능성 있음'];
              } elseif ($worksOther === 'YES' && $otherEnrolled === 'YES') {
                  $insuranceNotice = ['level' => 'danger', 'msg' => '고용보험 중복 확인 필요 (다른 사업장 기가입)'];
              } elseif ($worksOther === 'YES' && $otherEnrolled === 'NO') {
                  $insuranceNotice = ['level' => 'warning', 'msg' => '4대보험 가입 대상 가능성 있음 (복수 사업장 합산 고려)'];
              } else {
                  $insuranceNotice = ['level' => 'info', 'msg' => '확인 필요 항목 있음 — 다른 사업장 근무 여부를 확인하세요'];
              }
          } else {
              $insuranceNotice = ['level' => 'secondary', 'msg' => '현재 근무시간 기준 가입 기준 미달 가능성 (월 60시간 미만)'];
          }
        ?>
        <?php if ($isEdit): ?>
        <div class="alert alert-<?= $insuranceNotice['level'] ?> small mt-3 mb-0">
          <i class="bi bi-info-circle me-1"></i>
          <?= h($insuranceNotice['msg']) ?>
          <div class="text-muted mt-1" style="font-size:0.8em">※ 이 안내는 참고용이며, 정확한 가입 여부는 근로복지공단에서 확인하세요.</div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</div>

<!-- ── 4대보험 및 세금 정보 (사장 확인용) ──────────────────────────── -->
<?php
  $insStatusOptions = [
    'NOT_APPLICABLE' => '해당없음',
    'NEEDS_CHECK'    => '확인필요',
    'ENROLLED'       => '가입됨',
    'NOT_ENROLLED'   => '미가입',
  ];
  $insFields = [
    'national_pension_status'              => '국민연금',
    'health_insurance_status'              => '건강보험',
    'long_term_care_insurance_status'      => '장기요양보험',
    'employment_insurance_status'          => '고용보험',
    'industrial_accident_insurance_status' => '산재보험',
  ];
  $taxOptions = [
    'SIMPLE'      => '간이세액',
    'MANUAL'      => '수동',
    'NONE'        => '없음',
    'NEEDS_CHECK' => '확인필요',
  ];
?>
<div class="row g-4 mt-0">
  <div class="col-12">
    <div class="card mb-3 border-0 shadow-sm">
      <div class="card-header d-flex align-items-center gap-2" style="background:var(--c-cream)">
        <i class="bi bi-shield-check text-success"></i>
        <span class="fw-semibold">4대보험 및 세금 정보 <span class="text-muted small fw-normal">(선택)</span></span>
      </div>
      <div class="card-body">
        <div class="alert alert-light border small py-2 mb-3">
          4대보험 정보는 가입 여부를 확정하기 위한 것이 아니라, 사장님이 확인해야 할 항목을 안내하기 위해 사용됩니다.
        </div>
        <div class="row g-3">
          <?php foreach ($insFields as $field => $label):
            $cur = $member[$field] ?? 'NEEDS_CHECK'; ?>
          <div class="col-md-4">
            <label class="form-label small fw-semibold"><?= $label ?></label>
            <select name="<?= $field ?>" class="form-select form-select-sm">
              <?php foreach ($insStatusOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $cur === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <?php endforeach; ?>

          <div class="col-md-4">
            <label class="form-label small fw-semibold">소득세 방식</label>
            <?php $curTax = $member['income_tax_method'] ?? 'NEEDS_CHECK'; ?>
            <select name="income_tax_method" class="form-select form-select-sm">
              <?php foreach ($taxOptions as $v => $l): ?>
              <option value="<?= $v ?>" <?= $curTax === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">부양가족 수</label>
            <input type="number" name="dependent_count" class="form-control form-control-sm"
                   min="0" max="20" value="<?= h($member['dependent_count'] ?? '') ?>" placeholder="예: 1">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold d-block">비과세 항목</label>
            <div class="form-check form-switch mt-1">
              <input class="form-check-input" type="checkbox" id="has_non_taxable_items"
                     name="has_non_taxable_items" value="1"
                     <?= !empty($member['has_non_taxable_items']) ? 'checked' : '' ?>>
              <label class="form-check-label small" for="has_non_taxable_items">비과세 항목 있음 (식대 등)</label>
            </div>
          </div>

          <div class="col-12">
            <label class="form-label small fw-semibold">복수 사업장 고용보험</label>
            <?php $obEi = $member['other_business_employment_insurance'] ?? 'UNKNOWN'; ?>
            <div class="d-flex gap-3 flex-wrap">
              <?php foreach (['NO' => '아니오', 'YES' => '예', 'UNKNOWN' => '잘 모르겠음'] as $v => $l): ?>
              <div class="form-check">
                <input class="form-check-input" type="radio"
                       name="other_business_employment_insurance" id="obei_<?= $v ?>" value="<?= $v ?>"
                       <?= $obEi === $v ? 'checked' : '' ?>>
                <label class="form-check-label small" for="obei_<?= $v ?>"><?= $l ?></label>
              </div>
              <?php endforeach; ?>
            </div>
            <div class="form-text">다른 사업장에서 이미 고용보험에 가입되어 있는지 여부 (참고용).</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── 리스크 경고 모달 ──────────────────────────────────────────────── -->
<div class="modal fade" id="insWarningModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-danger">
      <div class="modal-header py-3" style="background:#dc3545;color:#fff">
        <h6 class="modal-title fw-bold">
          <i class="bi bi-exclamation-triangle-fill me-1"></i>4대보험 미가입 리스크 경고
        </h6>
      </div>
      <div class="modal-body">
        <p class="mb-2">시스템 판단 결과 이 직원은 <strong>4대보험 의무 가입 대상</strong>일 가능성이 높습니다.</p>
        <div id="insWarningItems" class="mb-3 ps-2"></div>
        <div class="alert alert-warning small mb-0">
          <i class="bi bi-exclamation-circle me-1"></i>
          의무 가입 대상자를 미가입으로 저장하면 <strong>과태료 및 소급 처리</strong> 등의 불이익이 발생할 수 있습니다.
          반드시 관할 공단(국민연금공단·건강보험공단·고용센터)에 확인 후 처리하세요.
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">
          취소 (돌아가기)
        </button>
        <button type="button" class="btn btn-danger btn-sm" id="btnConfirmWarning">
          <i class="bi bi-check-circle me-1"></i>리스크 확인 후 저장
        </button>
      </div>
    </div>
  </div>
</div>

<div class="d-flex flex-wrap gap-2 mt-4">
  <button type="submit" class="btn btn-primary px-4" id="btnSaveMember">
    <i class="bi bi-save me-1"></i>저장
  </button>
  <a href="<?= url('members') ?>" class="btn btn-outline-secondary">취소</a>
  <?php if ($isEdit): ?>
  <div class="ms-auto d-flex gap-2">
    <a href="<?= url('members', 'contract', ['id' => $member['id']]) ?>"
       target="_blank" class="btn btn-outline-dark">
      <i class="bi bi-file-earmark-text me-1"></i>근로계약서 생성
    </a>
    <?php if ($member['is_minor'] ?? 0): ?>
    <a href="<?= url('members', 'minor_consent', ['id' => $member['id']]) ?>"
       target="_blank" class="btn btn-outline-warning">
      <i class="bi bi-file-earmark-person me-1"></i>친권자 동의서
    </a>
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<?php if ($isEdit && !empty($wageHistory)): ?>
<div class="card border-0 shadow-sm mt-4">
  <div class="card-header small fw-semibold" style="background:var(--c-cream)">
    <i class="bi bi-clock-history me-1"></i>시급 변경 이력
  </div>
  <div class="card-body p-0">
    <table class="table table-sm small mb-0">
      <thead class="table-light">
        <tr><th>적용일</th><th>시급</th><th>사유</th></tr>
      </thead>
      <tbody>
        <?php foreach ($wageHistory as $wh):
          $isPending = $wh['effective_from'] > date('Y-m-d');
        ?>
        <tr class="<?= $isPending ? 'table-warning' : '' ?>">
          <td>
            <?= h($wh['effective_from']) ?>
            <?php if ($isPending): ?>
            <span class="badge bg-warning text-dark ms-1" style="font-size:.7rem">예정</span>
            <?php endif; ?>
          </td>
          <td class="fw-semibold"><?= number_format((int)$wh['hourly_wage']) ?>원</td>
          <td class="text-muted"><?= h($wh['memo'] ?: '—') ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

</form>

<script>
(function () {
  var LABELS = {
    'likely_required': ['danger',    '가입 대상 가능성 높음'],
    'possibly_exempt': ['secondary', '제외 가능성 있음'],
    'needs_review':    ['warning',   '확인 필요'],
    'required':        ['info',      '사용자 전액 부담']
  };
  var INS_NAMES = {
    'national_pension':     '국민연금',
    'health_insurance':     '건강보험 / 장기요양',
    'employment_insurance': '고용보험',
    'industrial_accident':  '산재보험'
  };

  function isOver65AtHire(dobVal, startVal) {
    if (!dobVal || !startVal) return false;
    var dob   = new Date(dobVal);
    var start = new Date(startVal);
    if (isNaN(dob) || isNaN(start)) return false;
    var age = start.getFullYear() - dob.getFullYear();
    var m   = start.getMonth() - dob.getMonth();
    if (m < 0 || (m === 0 && start.getDate() < dob.getDate())) age--;
    return age >= 65;
  }

  function calcDuration(startVal, endVal) {
    if (!startVal) return 'undefined';
    if (!endVal)   return 'over3m';
    var days = Math.round((new Date(endVal) - new Date(startVal)) / 86400000);
    if (days < 30) return 'under1m';
    if (days < 90) return '1m_to_3m';
    return 'over3m';
  }

  var DUR_LABEL = {
    'under1m': '1개월 미만', '1m_to_3m': '1개월 이상 3개월 미만',
    'over3m': '3개월 이상', 'undefined': '기간 미정'
  };

  function computeJudgment(weeklyHours, startVal, endVal, dobVal) {
    var monthly     = Math.round(weeklyHours * 4.345 * 10) / 10;
    var duration    = calcDuration(startVal, endVal);
    var over65      = isOver65AtHire(dobVal, startVal);

    function pension() {
      if (duration === 'under1m') return 'possibly_exempt';
      if (weeklyHours < 15)       return 'possibly_exempt';
      return 'likely_required';
    }
    function health() {
      if (duration === 'under1m') return 'possibly_exempt';
      if (weeklyHours < 15)       return 'possibly_exempt';
      return 'likely_required';
    }
    // 고용보험: ① 주 15h↑ → 적용(기간 무관)  ② 주 15h↓+3개월↑ → 적용
    //           ③ 주 15h↓+3개월↓ → 제외       ④ 65세↑ 신규 → 제외
    function employment() {
      if (over65)                  return 'possibly_exempt';
      if (weeklyHours >= 15)       return 'likely_required';
      if (duration === 'over3m')   return 'likely_required';
      if (duration === 'under1m' || duration === '1m_to_3m') return 'possibly_exempt';
      return 'needs_review';
    }

    return {
      monthly:              monthly,
      duration:             duration,
      over65_new_hire:      over65,
      national_pension:     pension(),
      health_insurance:     health(),
      employment_insurance: employment(),
      industrial_accident:  'required'
    };
  }

  function renderPanel() {
    var weeklyEl = document.querySelector('[name="weekly_scheduled_hours"]');
    var weekly   = weeklyEl ? (parseFloat(weeklyEl.value) || 0) : 0;
    var startDt  = (document.getElementById('empStartDate') || {}).value || '';
    var endDt    = (document.querySelector('[name="employment_end_date"]') || {}).value || '';
    var dob      = (document.getElementById('dateOfBirth')  || {}).value || '';
    var j        = computeJudgment(weekly, startDt, endDt, dob);

    var keys  = ['national_pension', 'health_insurance', 'employment_insurance', 'industrial_accident'];
    var html  = '';
    var risky = false;
    keys.forEach(function (k) {
      var info = LABELS[j[k]] || ['secondary', j[k]];
      if (j[k] === 'likely_required') risky = true;
      html += '<div class="col-sm-6">' +
        '<div class="d-flex align-items-center gap-2 p-2 rounded" style="background:#fff;border:1px solid #dee2e6">' +
        '<span class="badge bg-' + info[0] + ' text-nowrap" style="min-width:100px">' + info[1] + '</span>' +
        '<span class="small text-muted">' + INS_NAMES[k] + '</span>' +
        '</div></div>';
    });
    var footer = '월 소정근로시간 <strong>' + j.monthly + '시간</strong>'
               + ' &nbsp;|&nbsp; 계약 기간: <strong>' + (DUR_LABEL[j.duration] || j.duration) + '</strong>';
    if (j.over65_new_hire) footer += ' &nbsp;|&nbsp; <span class="text-warning fw-semibold">65세 이후 신규 고용 — 고용보험 적용 제외</span>';
    html += '<div class="col-12 small text-muted pt-1">' + footer + '</div>';
    document.getElementById('ins-check-rows').innerHTML = html;
    document.getElementById('ins-check-rows').dataset.risky = risky ? '1' : '0';
    document.getElementById('ins-check-rows').dataset.judgment = JSON.stringify(j);
    updateInsStatusWarning();
  }

  function updateInsStatusWarning() {
    var status  = (document.getElementById('insUserStatus') || {}).value;
    var panelEl = document.getElementById('ins-check-rows');
    var risky   = panelEl ? (panelEl.dataset.risky || '0') === '1' : false;
    var warnEl  = document.getElementById('insStatusWarning');
    if (!warnEl) return;
    warnEl.style.display = (status === 'not_enrolled' && risky) ? '' : 'none';
  }

  document.addEventListener('DOMContentLoaded', function () {
    // is_minor hint toggle
    var minorCb   = document.getElementById('is_minor');
    var minorHint = document.getElementById('minorHint');
    if (minorCb && minorHint) {
      function toggleMinorHint() { minorHint.style.display = minorCb.checked ? '' : 'none'; }
      minorCb.addEventListener('change', toggleMinorHint);
      toggleMinorHint();
    }

    renderPanel();

    // 미가입 선택 즉시 인라인 경고
    var insStatusEl = document.getElementById('insUserStatus');
    if (insStatusEl) insStatusEl.addEventListener('change', updateInsStatusWarning);

    // 복수 사업장 — "다른 사업장 근무" 라디오에 따라 보험 가입 여부 섹션 토글
    var otherSec = document.getElementById('otherEnrolledSection');
    if (otherSec) {
      function toggleOtherSec() {
        var sel = document.querySelector('input[name="works_at_other_business"]:checked');
        otherSec.style.display = (sel && sel.value === 'YES') ? 'block' : 'none';
      }
      document.querySelectorAll('.multi-works-radio').forEach(function (r) {
        r.addEventListener('change', toggleOtherSec);
      });
      toggleOtherSec();
    }

    // 보험 판단 패널 재계산 트리거
    var watchIds = ['weeklyHoursInput', 'dateOfBirth', 'empStartDate', 'employment_end_date'];
    var watchNames = ['work_start_time', 'work_end_time', 'daily_break_minutes', 'weekly_scheduled_days'];
    var seen = new Set();
    watchIds.forEach(function (id) {
      var el = document.getElementById(id);
      if (!el || seen.has(el)) return;
      seen.add(el);
      el.addEventListener('change', renderPanel);
      el.addEventListener('input', renderPanel);
    });
    watchNames.forEach(function (nm) {
      var el = document.querySelector('[name="' + nm + '"]');
      if (!el || seen.has(el)) return;
      seen.add(el);
      el.addEventListener('change', renderPanel);
      el.addEventListener('input', renderPanel);
    });

    // 수습기간 힌트 — 입사일·시급 변경 시 갱신
    ['empStartDate', 'trialEndDate'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('change', updateTrialHints);
    });
    document.querySelector('[name="hourly_wage"]').addEventListener('input', updateTrialHints);
    updateTrialHints();

    // 근무 일정 → 주 소정근로시간 자동 계산
    ['calcStart', 'calcEnd', 'calcBreak', 'weeklyDaysInput'].forEach(function (id) {
      var el = document.getElementById(id);
      if (el) el.addEventListener('input', updateScheduleCalc);
    });
    updateScheduleCalc();

    // 최저임금 미달 시급 경고
    var MIN_WAGE  = <?= (int)($settings['minimum_wage'] ?? 10320) ?>;
    var wageInput = document.querySelector('[name="hourly_wage"]');
    var wageWarn  = document.getElementById('wage-warning');
    function checkWageWarning() {
      if (!wageInput || !wageWarn) return;
      var v = parseInt(wageInput.value, 10) || 0;
      wageWarn.classList.toggle('d-none', !(v > 0 && v < MIN_WAGE));
    }
    if (wageInput) {
      wageInput.addEventListener('input', checkWageWarning);
      wageInput.addEventListener('change', checkWageWarning);
      checkWageWarning();
    }

    // 저장 버튼 인터셉트 — 미가입 + 고위험 시 경고 모달
    document.querySelector('form').addEventListener('submit', function (e) {
      var status = document.getElementById('insUserStatus').value;
      if (status !== 'not_enrolled') return;

      var panelEl = document.getElementById('ins-check-rows');
      if ((panelEl.dataset.risky || '0') !== '1') return;

      e.preventDefault();

      var j      = JSON.parse(panelEl.dataset.judgment || '{}');
      var risky  = [];
      ['national_pension', 'health_insurance', 'employment_insurance'].forEach(function (k) {
        if (j[k] === 'likely_required') risky.push(INS_NAMES[k]);
      });

      var ul = risky.map(function (n) {
        return '<li><strong class="text-danger">' + n + '</strong>: 가입 대상 가능성 높음</li>';
      }).join('');
      document.getElementById('insWarningItems').innerHTML = '<ul class="mb-0 small">' + ul + '</ul>';

      var modal = new bootstrap.Modal(document.getElementById('insWarningModal'));

      document.getElementById('btnConfirmWarning').onclick = function () {
        document.getElementById('warningAcknowledged').value = '1';
        modal.hide();
        document.querySelector('form').submit();
      };

      modal.show();
    });
  });

  // updateScheduleCalc(IIFE 외부)에서 직접 호출할 수 있도록 전역 노출
  window._renderInsPanel = renderPanel;
})();

// 수습기간 토글
function toggleTrial(checked) {
  document.getElementById('trialSection').style.display = checked ? 'block' : 'none';
  if (!checked) {
    document.getElementById('trialEndDate').value  = '';
    document.getElementById('trialWageInput').value = '';
  } else {
    updateTrialHints();
  }
}

// 수습기간 힌트 갱신
function updateTrialHints() {
  var startEl    = document.getElementById('empStartDate');
  var endEl      = document.getElementById('trialEndDate');
  var wageEl     = document.getElementById('trialWageInput');
  var durationEl = document.getElementById('trialDurationHint');
  var wageHintEl = document.getElementById('trialWageHint');
  var hourlyWage = parseFloat(document.querySelector('[name="hourly_wage"]').value) || 0;

  // 수습 기간 일수 및 경고
  if (startEl && endEl && startEl.value && endEl.value) {
    var days = Math.round((new Date(endEl.value) - new Date(startEl.value)) / 86400000);
    if (days <= 0) {
      durationEl.innerHTML = '<span class="text-danger">수습 종료일이 입사일보다 이릅니다.</span>';
    } else if (days > 92) {
      durationEl.innerHTML = '<span class="text-danger">수습기간이 3개월을 초과합니다 (' + days + '일). 법적 감액 불가.</span>';
    } else {
      durationEl.innerHTML = '<span class="text-success">수습기간 ' + days + '일 (3개월 이내) ✓</span>';
    }
  } else {
    durationEl.textContent = '';
  }

  // 수습 시급 검증 및 90% 추천
  if (wageEl && hourlyWage > 0) {
    var suggested = Math.round(hourlyWage * 0.9);
    var trialWage = parseFloat(wageEl.value) || 0;
    var hints = [];
    if (wageEl.value === '' || trialWage === 0) {
      hints.push('추천 수습 시급 (90%): <strong>' + suggested.toLocaleString() + '원</strong> '
        + '<a href="#" onclick="document.getElementById(\'trialWageInput\').value=' + suggested + ';updateTrialHints();return false;" '
        + 'class="text-primary small">적용</a>');
    } else {
      var minWage = <?= MinimumWage::currentHourlyWage() ?>;
      if (trialWage < minWage * 0.9) {
        hints.push('<span class="text-danger">법정 최저시급 90%(' + Math.round(minWage * 0.9).toLocaleString() + '원) 미만입니다.</span>');
      } else if (trialWage >= hourlyWage) {
        hints.push('<span class="text-muted">수습 시급이 정규 시급과 같습니다.</span>');
      } else {
        var pct = Math.round(trialWage / hourlyWage * 1000) / 10;
        hints.push('<span class="text-success">정규 시급의 ' + pct + '% ✓</span>');
      }
    }
    wageHintEl.innerHTML = hints.join(' ');
  }
}

// 근무 일정 계산기
function updateScheduleCalc() {
  var startEl  = document.getElementById('calcStart');
  var endEl    = document.getElementById('calcEnd');
  var breakEl  = document.getElementById('calcBreak');
  var daysEl   = document.getElementById('weeklyDaysInput');
  var hoursEl  = document.getElementById('weeklyHoursInput');
  var resEl    = document.getElementById('scheduleCalcResult');
  var hintEl   = document.getElementById('calcBreakHint');

  if (!startEl || !endEl || !startEl.value || !endEl.value) {
    if (resEl) resEl.textContent = '';
    return;
  }
  var sp    = startEl.value.split(':').map(Number);
  var ep    = endEl.value.split(':').map(Number);
  var gross = (ep[0]*60+ep[1]) - (sp[0]*60+sp[1]);
  if (gross <= 0) { if (resEl) resEl.textContent = ''; return; }

  var days     = Math.max(1, parseInt(daysEl ? daysEl.value : 1, 10) || 1);
  var legalMin = gross > 480 ? 60 : gross >= 240 ? 30 : 0;
  var breakMin = breakEl && breakEl.value !== '' ? Math.max(0, parseInt(breakEl.value, 10) || 0) : legalMin;

  // 휴게 법정 기준 힌트
  if (hintEl) {
    if (breakMin < legalMin) {
      hintEl.innerHTML = '<span class="text-danger">법정 최소 ' + legalMin + '분</span>';
    } else if (legalMin > 0) {
      hintEl.innerHTML = '<span class="text-success">법정 기준 ' + legalMin + '분 이상 ✓</span>';
    } else {
      hintEl.textContent = '';
    }
  }

  var net    = Math.max(0, (gross - breakMin) / 60);
  var weekly = Math.round(net * days * 10) / 10;

  // 주 소정근로시간 자동 입력 + 보험 패널 즉시 갱신
  if (hoursEl) {
    hoursEl.value = weekly;
    if (typeof window._renderInsPanel === 'function') window._renderInsPanel();
  }

  if (resEl) {
    var breakText = breakMin > 0 ? ' − 휴게 ' + breakMin + '분' : '';
    resEl.innerHTML = '1일 ' + (gross/60).toFixed(1) + 'h' + breakText
      + ' = 순 ' + net.toFixed(1) + 'h × ' + days + '일';
  }
}
</script>
