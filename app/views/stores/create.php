<?php /* app/views/stores/create.php */ ?>
<div class="row justify-content-center">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold py-3">
        <i class="bi bi-shop me-2"></i>새 매장 추가
      </div>
      <div class="card-body p-4">
        <form method="POST" action="<?= url('store', 'create') ?>">
          <?= csrf_field() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">매장명 <span class="text-danger">*</span></label>
            <input type="text" name="store_name" class="form-control"
                   placeholder="예: 강남점" required maxlength="100"
                   value="<?= h($_POST['store_name'] ?? '') ?>">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">사업장 규모</label>
            <select name="employee_count_type" class="form-select">
              <option value="under5" <?= (($_POST['employee_count_type'] ?? '') === 'under5') ? 'selected' : '' ?>>
                5인 미만
              </option>
              <option value="over5" <?= (($_POST['employee_count_type'] ?? '') === 'over5') ? 'selected' : '' ?>>
                5인 이상
              </option>
            </select>
            <div class="form-text">5인 이상 사업장은 연장·야간·휴일 가산수당이 적용됩니다.</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">기본 시급</label>
            <div class="input-group">
              <span class="input-group-text">₩</span>
              <input type="number" name="minimum_wage" class="form-control"
                     value="<?= h($_POST['minimum_wage'] ?? DEFAULT_MIN_WAGE) ?>"
                     min="0" step="10">
            </div>
            <div class="form-text">2026년 최저시급: ₩<?= number_format(DEFAULT_MIN_WAGE) ?></div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill">
              <i class="bi bi-shop me-1"></i>매장 추가
            </button>
            <a href="<?= url() ?>" class="btn btn-outline-secondary">취소</a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
