<?php
$isEdit = ($action === 'edit');
$lv = $leave;
$types = ['annual' => '연차', 'sick' => '병가', 'unpaid' => '무급휴가', 'other' => '기타'];
$statuses = ['approved' => '승인', 'pending' => '대기', 'rejected' => '반려'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-calendar-heart me-2 text-primary"></i><?= h($title) ?></h1>
  <a href="<?= url('leaves') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>연차/휴가로
  </a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
  <ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:560px">
  <div class="card-body">
    <form method="post"
          action="<?= $isEdit ? url('leaves', 'edit', ['id' => $lv['id']]) : url('leaves', 'create') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">직원 <span class="text-danger">*</span></label>
        <select name="employee_id" class="form-select" required>
          <option value="">— 선택 —</option>
          <?php foreach ($employees as $emp): ?>
          <option value="<?= $emp['id'] ?>" <?= (int)($lv['employee_id'] ?? 0) === (int)$emp['id'] ? 'selected' : '' ?>>
            <?= h($emp['name']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">휴가 유형</label>
        <select name="leave_type" class="form-select">
          <?php foreach ($types as $val => $label): ?>
          <option value="<?= $val ?>" <?= ($lv['leave_type'] ?? 'annual') === $val ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">시작일 <span class="text-danger">*</span></label>
          <input type="date" name="start_date" class="form-control"
                 value="<?= h($lv['start_date'] ?? date('Y-m-d')) ?>" required>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">종료일 <span class="text-danger">*</span></label>
          <input type="date" name="end_date" class="form-control"
                 value="<?= h($lv['end_date'] ?? date('Y-m-d')) ?>" required>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">일수</label>
          <input type="number" name="days" class="form-control" min="0" step="0.5"
                 value="<?= h((string)($lv['days'] ?? '1')) ?>">
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">상태</label>
          <select name="status" class="form-select">
            <?php foreach ($statuses as $val => $label): ?>
            <option value="<?= $val ?>" <?= ($lv['status'] ?? 'approved') === $val ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">메모</label>
        <input type="text" name="memo" class="form-control" maxlength="500"
               value="<?= h($lv['memo'] ?? '') ?>">
      </div>

      <div class="d-flex gap-2 justify-content-end">
        <a href="<?= url('leaves') ?>" class="btn btn-outline-secondary">취소</a>
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-save me-1"></i>저장
        </button>
      </div>
    </form>
  </div>
</div>
