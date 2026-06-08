<?php $settings = Setting::get(); ?>

<!-- 히어로 배너 -->
<div class="rounded-4 px-4 py-4 mb-4 d-flex align-items-center justify-content-between flex-wrap gap-3"
     style="background: linear-gradient(135deg, #003844 0%, #006C67 100%); color:#fff;">
  <div>
    <div class="fw-bold fs-5 mb-1">
      <i class="bi bi-building me-2" style="color:#F194B4"></i><?= h($settings['business_name']) ?>
    </div>
    <div style="color:#FFEBC6; font-size:.9rem;">
      <?= date('Y년 n월 j일') ?> ·
      <?= $settings['employee_count_type'] === 'over5' ? '5인 이상 사업장' : '5인 미만 사업장' ?> ·
      <?= $settings['minimum_wage_year'] ?>년 최저시급 <?= number_format($settings['minimum_wage']) ?>원
    </div>
  </div>
  <a href="<?= url('work_logs', 'create') ?>"
     class="btn fw-bold"
     style="background:#F194B4; color:#003844; border:none;">
    <i class="bi bi-plus-circle me-1"></i>근무 기록 추가
  </a>
</div>

<!-- 통계 카드 -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card h-100 border-0" style="border-left:4px solid #006C67 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-2" style="color:#003844"><?= count($employees) ?></div>
        <div class="small text-muted">재직 중 직원</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 border-0" style="border-left:4px solid #F194B4 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-bold fs-2" style="color:#003844"><?= count($recentLogs) ?></div>
        <div class="small text-muted">최근 근무 기록</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 border-0" style="border-left:4px solid #FFB100 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-bold" style="color:#003844; font-size:1.1rem;">
          <?= number_format($settings['minimum_wage']) ?>원
        </div>
        <div class="small text-muted"><?= $settings['minimum_wage_year'] ?>년 최저시급</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card h-100 border-0" style="border-left:4px solid #FFEBC6; border-left-color:#FFB100 !important;">
      <div class="card-body text-center py-3">
        <div class="fw-semibold" style="color:#003844;">
          <a href="<?= url('payroll') ?>" class="text-decoration-none" style="color:inherit;">
            <i class="bi bi-calculator-fill me-1" style="color:#006C67"></i>급여 계산
          </a>
        </div>
        <div class="small text-muted">바로 가기</div>
      </div>
    </div>
  </div>
</div>

<!-- 직원 목록 + 최근 기록 -->
<div class="row g-4">
  <div class="col-md-6">
    <div class="card border-0">
      <div class="card-header d-flex justify-content-between align-items-center"
           style="background:#f0f7f6; color:#003844;">
        <span><i class="bi bi-people-fill me-1" style="color:#006C67"></i>재직 중 직원</span>
        <a href="<?= url('employees', 'create') ?>"
           class="btn btn-sm fw-semibold" style="background:#006C67; color:#fff; font-size:.78rem;">
          <i class="bi bi-plus"></i> 등록
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($employees)): ?>
        <div class="p-4 text-center text-muted">
          <i class="bi bi-person-x fs-2 d-block mb-1" style="color:#F194B4"></i>
          등록된 직원이 없습니다.
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($employees as $emp): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <span>
              <i class="bi bi-person-circle me-1" style="color:#006C67"></i>
              <a href="<?= url('work_logs', 'index', ['employee_id' => $emp['id']]) ?>"
                 class="text-decoration-none fw-semibold" style="color:#003844">
                <?= h($emp['name']) ?>
              </a>
            </span>
            <span class="badge rounded-pill"
                  style="background:#FFEBC6; color:#003844; border:1px solid #FFB100; font-weight:600;">
              <?= number_format($emp['hourly_wage']) ?>원
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="card border-0">
      <div class="card-header d-flex justify-content-between align-items-center"
           style="background:#f0f7f6; color:#003844;">
        <span><i class="bi bi-clock-history me-1" style="color:#F194B4"></i>최근 근무 기록</span>
        <a href="<?= url('payroll') ?>"
           class="btn btn-sm fw-semibold" style="background:#FFB100; color:#003844; font-size:.78rem;">
          <i class="bi bi-calculator"></i> 급여 계산
        </a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($recentLogs)): ?>
        <div class="p-4 text-center text-muted">
          <i class="bi bi-calendar-x fs-2 d-block mb-1" style="color:#F194B4"></i>
          근무 기록이 없습니다.
        </div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach (array_slice($recentLogs, 0, 7) as $log): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <span class="small">
              <span class="fw-semibold" style="color:#003844"><?= h($log['employee_name']) ?></span>
              <span class="text-muted ms-2">
                <?= h($log['work_date']) ?>(<?= dayOfWeekKo($log['work_date']) ?>)
              </span>
            </span>
            <span class="small">
              <?php if ($log['is_absent']): ?>
                <span class="badge" style="background:#fde8ec; color:#c0394b;">결근</span>
              <?php else: ?>
                <span class="text-muted">
                  <?= h(substr($log['start_time'],0,5)) ?>~<?= h(substr($log['end_time'],0,5)) ?>
                </span>
              <?php endif; ?>
            </span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
