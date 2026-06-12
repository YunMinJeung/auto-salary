<?php
$fd = $formData;
function _fv(array $fd, string $key, string $default = ''): string {
    return htmlspecialchars((string)($fd[$key] ?? $default), ENT_QUOTES, 'UTF-8');
}
?>
<div class="container py-4" style="max-width:720px">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0">친권자(후견인) 동의서</h4>
      <p class="text-muted small mb-0 mt-1">
        <?= htmlspecialchars($member['name'], ENT_QUOTES, 'UTF-8') ?> · 연소근로자 근로계약 관련
      </p>
    </div>
    <a href="<?= url('members','contract',['id'=>$member['id']]) ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>근로계약서로
    </a>
  </div>

  <div class="alert alert-info small mb-4">
    <i class="bi bi-info-circle me-2"></i>
    「근로기준법」 제66조에 따라 만 18세 미만 연소근로자는 친권자 또는 후견인의 동의서를 갖추어야 합니다.
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <?php if ($history): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold small">이전 동의서 이력</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr><th>근로자 생년월일</th><th>친권자</th><th>작성일시</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h2): ?>
          <tr>
            <td><?= htmlspecialchars($h2['date_of_birth'] ?? '', ENT_QUOTES, 'UTF-8') ?> (만 <?= (int)($h2['age_at_signing'] ?? 0) ?>세)</td>
            <td><?= htmlspecialchars($h2['guardian_name'] ?? '', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($h2['guardian_relation'] ?? '', ENT_QUOTES, 'UTF-8') ?>)</td>
            <td><?= htmlspecialchars(substr($h2['created_at'], 0, 16), ENT_QUOTES, 'UTF-8') ?></td>
            <td>
              <a href="<?= url('members','minor_consent_view',['id'=>$member['id'],'consent_id'=>$h2['id']]) ?>"
                 class="btn btn-sm btn-outline-primary py-0 px-2" target="_blank">
                <i class="bi bi-printer me-1"></i>인쇄
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>

    <!-- 근로자 정보 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-teal);color:#fff">
        <i class="bi bi-person me-2"></i>근로자 정보
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">성명 (실명) <span class="text-danger">*</span></label>
            <input type="text" name="employee_name" class="form-control"
                   value="<?= _fv($fd,'employee_name') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">생년월일 <span class="text-danger">*</span></label>
            <input type="date" name="date_of_birth" class="form-control"
                   value="<?= _fv($fd,'date_of_birth') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">주소</label>
            <input type="text" name="employee_address" class="form-control"
                   value="<?= _fv($fd,'employee_address') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- 사업장 정보 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-dark);color:#fff">
        <i class="bi bi-building me-2"></i>사업장 / 근로 조건
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">사업장명</label>
            <input type="text" name="business_name" class="form-control"
                   value="<?= _fv($fd,'business_name',$store['store_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">사업주명</label>
            <input type="text" name="employer_name" class="form-control"
                   value="<?= _fv($fd,'employer_name') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">근무 장소</label>
            <input type="text" name="work_location" class="form-control"
                   value="<?= _fv($fd,'work_location',$store['store_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">업무 내용</label>
            <input type="text" name="job_duties" class="form-control"
                   value="<?= _fv($fd,'job_duties') ?>" placeholder="예: 매장 서빙 및 청소">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">계약 시작일</label>
            <input type="date" name="contract_start_date" class="form-control"
                   value="<?= _fv($fd,'contract_start_date',$member['employment_start_date'] ?? '') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">계약 종료일</label>
            <input type="date" name="contract_end_date" class="form-control"
                   value="<?= _fv($fd,'contract_end_date') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">시급 (원)</label>
            <input type="number" name="hourly_wage" class="form-control" min="0"
                   value="<?= _fv($fd,'hourly_wage',$member['hourly_wage'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">근무 시간</label>
            <div class="d-flex gap-2 align-items-center">
              <input type="time" name="work_start_time" class="form-control" value="<?= _fv($fd,'work_start_time') ?>">
              <span>~</span>
              <input type="time" name="work_end_time" class="form-control" value="<?= _fv($fd,'work_end_time') ?>">
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- 친권자/후견인 정보 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-amber);color:#212529">
        <i class="bi bi-people me-2"></i>친권자 / 후견인 정보
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">성명 <span class="text-danger">*</span></label>
            <input type="text" name="guardian_name" class="form-control"
                   value="<?= _fv($fd,'guardian_name') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">근로자와의 관계 <span class="text-danger">*</span></label>
            <input type="text" name="guardian_relation" class="form-control"
                   value="<?= _fv($fd,'guardian_relation') ?>" placeholder="예: 부, 모, 후견인" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">연락처</label>
            <input type="text" name="guardian_phone" class="form-control"
                   value="<?= _fv($fd,'guardian_phone') ?>" placeholder="010-0000-0000">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">주소</label>
            <input type="text" name="guardian_address" class="form-control"
                   value="<?= _fv($fd,'guardian_address') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">작성일</label>
            <input type="date" name="issue_date" class="form-control"
                   value="<?= _fv($fd,'issue_date',date('Y-m-d')) ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
      <a href="<?= url('members','edit',['id'=>$member['id']]) ?>" class="btn btn-outline-secondary">취소</a>
      <button type="submit" class="btn text-white" style="background:var(--c-amber);color:#212529!important">
        <i class="bi bi-save me-1"></i>동의서 저장 후 인쇄 화면으로
      </button>
    </div>
  </form>
</div>
