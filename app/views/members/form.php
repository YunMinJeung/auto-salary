<?php $isEdit = $action === 'edit'; ?>
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

<form method="post"
      action="<?= $isEdit ? url('members', 'edit', ['id' => $member['id']]) : url('members', 'create') ?>">
<?= csrf_field() ?>

<div class="row g-4">

  <!-- 기본 정보 -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-person me-1"></i>기본 정보
      </div>
      <div class="card-body">

        <div class="mb-3">
          <label class="form-label fw-semibold">이름 <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control"
                 value="<?= h($member['name'] ?? '') ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">연락처</label>
          <input type="tel" name="phone" class="form-control"
                 value="<?= h($member['phone'] ?? '') ?>" placeholder="010-0000-0000">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">시급 <span class="text-danger">*</span></label>
          <div class="input-group">
            <input type="number" name="hourly_wage" class="form-control"
                   value="<?= h($member['hourly_wage'] ?? $settings['minimum_wage']) ?>"
                   min="1" required>
            <span class="input-group-text">원/시간</span>
          </div>
          <div class="form-text">최저시급 기준: <?= number_format($settings['minimum_wage']) ?>원</div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">주 소정근로시간</label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_hours" class="form-control"
                     value="<?= h($member['weekly_scheduled_hours'] ?? 40) ?>"
                     min="0" max="80" step="0.5">
              <span class="input-group-text">시간</span>
            </div>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">주 소정근로일</label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_days" class="form-control"
                     value="<?= h($member['weekly_scheduled_days'] ?? 5) ?>"
                     min="1" max="7">
              <span class="input-group-text">일</span>
            </div>
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
            <input type="date" name="employment_start_date" class="form-control"
                   value="<?= h($member['employment_start_date'] ?? date('Y-m-d')) ?>">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">퇴사일 <span class="text-muted small fw-normal">(선택)</span></label>
            <input type="date" name="employment_end_date" class="form-control"
                   value="<?= h($member['employment_end_date'] ?? '') ?>">
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
          <label class="form-label fw-semibold">메모</label>
          <textarea name="memo" class="form-control" rows="2"><?= h($member['memo'] ?? '') ?></textarea>
        </div>

      </div>
    </div>
  </div>

  <!-- 앱 계정 -->
  <div class="col-lg-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold" style="background:var(--c-cream)">
        <i class="bi bi-phone me-1"></i>앱 로그인 계정
      </div>
      <div class="card-body">

        <?php if ($isEdit && ($member['user_id'] ?? null)): ?>
        <div class="alert alert-success small py-2">
          <i class="bi bi-check-circle me-1"></i>
          계정이 연결되어 있습니다: <strong><?= h($member['user_email'] ?? '') ?></strong>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3">
          알바생이 스마트폰으로 직접 출퇴근할 수 있도록 로그인 계정을 만들어 줄 수 있습니다.
          계정 없이도 점주가 수동으로 기록할 수 있습니다.
        </p>

        <div class="mb-3">
          <label class="form-label fw-semibold">이메일</label>
          <input type="email" name="user_email" class="form-control"
                 value="<?= h($member['user_email'] ?? '') ?>" placeholder="albaeng@example.com">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">비밀번호</label>
          <input type="password" name="user_password" class="form-control"
                 placeholder="4자 이상" autocomplete="new-password">
          <div class="form-text">빈칸으로 두면 계정이 생성되지 않습니다.</div>
        </div>
        <?php endif; ?>

      </div>
    </div>
  </div>

</div>

<div class="d-flex gap-2 mt-4">
  <button type="submit" class="btn btn-primary px-4">
    <i class="bi bi-save me-1"></i>저장
  </button>
  <a href="<?= url('members') ?>" class="btn btn-outline-secondary">취소</a>
</div>

</form>
