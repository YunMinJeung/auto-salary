<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted small">연도별 급여 계산 기준 (최저임금·심야시간·4대보험 요율)</div>
  <a href="<?= url('admin', 'standard_form') ?>" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-plus-lg me-1"></i>새 기준 추가</a>
</div>

<div class="admin-card">
  <div class="admin-card-header"><span><i class="bi bi-sliders me-1"></i>계산 기준 목록</span><span class="small text-muted"><?= count($standards) ?>건</span></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr>
          <th>연도</th><th class="text-end">최저시급</th><th>심야시간</th>
          <th class="text-end">국민연금</th><th class="text-end">건강</th><th class="text-end">장기요양</th><th class="text-end">고용</th>
          <th>적용기간</th><th>활성</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($standards)): ?>
          <tr><td colspan="10" class="text-center text-muted py-4">기준이 없습니다.</td></tr>
        <?php else: foreach ($standards as $st): ?>
          <tr>
            <td class="fw-semibold"><?= (int)$st['year'] ?></td>
            <td class="text-end"><?= number_format((int)$st['min_hourly_wage']) ?>원</td>
            <td class="small"><?= h(substr((string)$st['night_start_time'], 0, 5)) ?>~<?= h(substr((string)$st['night_end_time'], 0, 5)) ?></td>
            <td class="text-end small"><?= number_format((float)$st['insurance_national_pension_rate'] * 100, 2) ?>%</td>
            <td class="text-end small"><?= number_format((float)$st['insurance_health_rate'] * 100, 2) ?>%</td>
            <td class="text-end small"><?= number_format((float)$st['insurance_long_term_care_rate'] * 100, 2) ?>%</td>
            <td class="text-end small"><?= number_format((float)$st['insurance_employment_rate'] * 100, 2) ?>%</td>
            <td class="small text-muted"><?= h($st['applies_from']) ?> ~ <?= h($st['applies_to'] ?? '') ?></td>
            <td><span class="badge bg-<?= (int)$st['is_active'] ? 'success' : 'secondary' ?>"><?= (int)$st['is_active'] ? '활성' : '비활성' ?></span></td>
            <td class="text-end"><a href="<?= url('admin', 'standard_form', ['id' => $st['id']]) ?>" class="btn btn-sm btn-outline-secondary">수정</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
