<?php
$statuses = ['ACTIVE','TRIAL','PAYMENT_PENDING','SUSPENDED','CANCEL_REQUESTED','INACTIVE'];
$plans    = ['FREE','STARTER','BUSINESS','PRO'];
?>
<a href="<?= url('admin', 'businesses') ?>" class="text-decoration-none small d-inline-block mb-3"><i class="bi bi-arrow-left"></i> 사업장 목록</a>

<div class="row g-3">
  <!-- 기본 정보 -->
  <div class="col-lg-8">
    <div class="admin-card mb-3">
      <div class="admin-card-header">
        <span><i class="bi bi-info-circle me-1"></i>기본 정보</span>
        <span><?= adminBadge($store['status'] ?? 'ACTIVE') ?> <?= adminBadge($store['plan'] ?? 'FREE') ?></span>
      </div>
      <div class="admin-card-body">
        <dl class="row mb-0 small">
          <dt class="col-sm-3 text-muted">사업장명</dt><dd class="col-sm-9 fw-semibold"><?= h($store['store_name']) ?></dd>
          <dt class="col-sm-3 text-muted">대표 사장</dt><dd class="col-sm-9"><?= h($store['owner_name'] ?? '-') ?> (<?= h($store['owner_email'] ?? '') ?>)</dd>
          <dt class="col-sm-3 text-muted">사업자번호</dt><dd class="col-sm-9"><?= h($store['business_number'] ?? '-') ?></dd>
          <dt class="col-sm-3 text-muted">상시근로</dt><dd class="col-sm-9"><?= h($store['employee_count_type'] ?? '-') ?></dd>
          <dt class="col-sm-3 text-muted">무료체험 종료</dt><dd class="col-sm-9"><?= h($store['trial_ends_at'] ?? '-') ?></dd>
          <dt class="col-sm-3 text-muted">가입일</dt><dd class="col-sm-9"><?= h(substr((string)($store['created_at'] ?? ''), 0, 19)) ?></dd>
        </dl>
      </div>
    </div>

    <!-- 직원 목록 -->
    <div class="admin-card mb-3">
      <div class="admin-card-header"><span><i class="bi bi-people me-1"></i>직원 목록</span><span class="small text-muted"><?= count($members) ?>명</span></div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>이름</th><th>역할</th><th>시급</th><th>상태</th><th>입사일</th></tr></thead>
          <tbody>
            <?php if (empty($members)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">직원이 없습니다.</td></tr>
            <?php else: foreach ($members as $m): ?>
              <tr>
                <td class="fw-semibold"><?= h($m['name'] ?? '-') ?><?php if (!empty($m['email'])): ?><br><span class="small text-muted"><?= h($m['email']) ?></span><?php endif; ?></td>
                <td class="small"><?= adminLabel($m['member_role'] ?? '') ?: '-' ?></td>
                <td class="small"><?= $m['hourly_wage'] !== null ? number_format((int)$m['hourly_wage']) . '원' : '-' ?></td>
                <td><span class="badge bg-<?= ((int)($m['is_active'] ?? 0)) ? 'success' : 'secondary' ?>"><?= ((int)($m['is_active'] ?? 0)) ? '활성' : '비활성' ?></span></td>
                <td class="small text-muted"><?= h($m['employment_start_date'] ?? '-') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 최근 근무기록 -->
    <div class="admin-card mb-3">
      <div class="admin-card-header"><span><i class="bi bi-clock-history me-1"></i>최근 근무기록</span></div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>날짜</th><th>직원</th><th>출근</th><th>퇴근</th><th>휴게(분)</th><th>비고</th></tr></thead>
          <tbody>
            <?php if (empty($workLogs)): ?>
              <tr><td colspan="6" class="text-center text-muted py-3">근무기록이 없습니다.</td></tr>
            <?php else: foreach ($workLogs as $w): ?>
              <tr>
                <td class="small"><?= h($w['work_date'] ?? '') ?></td>
                <td class="small"><?= h($w['employee_name'] ?? '-') ?></td>
                <td class="small"><?= h(substr((string)($w['start_time'] ?? ''), 0, 5)) ?></td>
                <td class="small"><?= h(substr((string)($w['end_time'] ?? ''), 0, 5)) ?></td>
                <td class="small"><?= (int)($w['break_minutes'] ?? 0) ?></td>
                <td class="small"><?php if ((int)($w['is_absent'] ?? 0)): ?><span class="badge bg-danger">결근</span><?php elseif ((int)($w['is_holiday'] ?? 0)): ?><span class="badge bg-info">휴일</span><?php endif; ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- 리스크 현황 -->
    <div class="admin-card mb-3">
      <div class="admin-card-header"><span><i class="bi bi-exclamation-triangle me-1"></i>노동법 리스크 현황</span><span class="small text-muted"><?= count($riskAlerts) ?>건</span></div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>심각도</th><th>직원</th><th>내용</th><th>상태</th><th>일시</th></tr></thead>
          <tbody>
            <?php if (empty($riskAlerts)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">미해결 리스크가 없습니다.</td></tr>
            <?php else: foreach ($riskAlerts as $r): ?>
              <tr>
                <td><span class="badge badge-<?= h($r['severity'] ?? 'info') ?>-sev"><?= h($r['severity'] ?? '') ?></span></td>
                <td class="small"><?= h($r['employee_name'] ?? '-') ?></td>
                <td class="small"><?= h($r['title'] ?? '') ?></td>
                <td class="small"><?= h($r['status'] ?? '') ?></td>
                <td class="small text-muted"><?= h(substr((string)($r['created_at'] ?? ''), 0, 16)) ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Audit Log -->
    <div class="admin-card">
      <div class="admin-card-header"><span><i class="bi bi-journal-check me-1"></i>변경 이력 (Audit Log)</span></div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead><tr><th>일시</th><th>작업자</th><th>액션</th><th>변경</th><th>사유</th></tr></thead>
          <tbody>
            <?php if (empty($auditLogs)): ?>
              <tr><td colspan="5" class="text-center text-muted py-3">이력이 없습니다.</td></tr>
            <?php else: foreach ($auditLogs as $log): ?>
              <tr>
                <td class="small text-muted"><?= h(substr((string)($log['created_at'] ?? ''), 0, 19)) ?></td>
                <td class="small"><?= h($log['actor_name'] ?? '-') ?></td>
                <td class="small"><code><?= h($log['action'] ?? '') ?></code></td>
                <td class="small text-muted"><?= h($log['before_value'] ?? '') ?> → <?= h($log['after_value'] ?? '') ?></td>
                <td class="small"><?= h($log['reason'] ?? '') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 우측: 관리 액션 -->
  <div class="col-lg-4">
    <!-- 상태 변경 -->
    <div class="admin-card mb-3">
      <div class="admin-card-header"><span><i class="bi bi-toggle-on me-1"></i>상태 변경</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'business_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$store['id'] ?>">
          <input type="hidden" name="action" value="status">
          <div class="mb-2">
            <select name="status" class="form-select form-select-sm">
              <?php foreach ($statuses as $st): ?>
                <option value="<?= h($st) ?>" <?= ($store['status'] ?? '') === $st ? 'selected' : '' ?>><?= adminLabel($st) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <input type="text" name="reason" class="form-control form-control-sm" placeholder="변경 사유 (선택)">
          </div>
          <button type="submit" class="btn btn-sm w-100" style="background:var(--admin-accent);color:#fff">상태 변경</button>
        </form>
      </div>
    </div>

    <!-- 요금제 변경 -->
    <div class="admin-card mb-3">
      <div class="admin-card-header"><span><i class="bi bi-tag me-1"></i>요금제 변경</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'business_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$store['id'] ?>">
          <input type="hidden" name="action" value="plan">
          <div class="mb-2">
            <select name="plan" class="form-select form-select-sm">
              <?php foreach ($plans as $pl): ?>
                <option value="<?= h($pl) ?>" <?= ($store['plan'] ?? '') === $pl ? 'selected' : '' ?>><?= adminLabel($pl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-2">
            <input type="text" name="reason" class="form-control form-control-sm" placeholder="변경 사유 (선택)">
          </div>
          <button type="submit" class="btn btn-sm w-100" style="background:var(--admin-accent);color:#fff">요금제 변경</button>
        </form>
      </div>
    </div>

    <!-- 관리자 메모 -->
    <div class="admin-card">
      <div class="admin-card-header"><span><i class="bi bi-sticky me-1"></i>관리자 메모</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'business_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$store['id'] ?>">
          <input type="hidden" name="action" value="memo">
          <textarea name="admin_memo" class="form-control form-control-sm mb-2" rows="5" placeholder="내부 메모"><?= h($store['admin_memo'] ?? '') ?></textarea>
          <button type="submit" class="btn btn-sm btn-outline-secondary w-100">메모 저장</button>
        </form>
      </div>
    </div>
  </div>
</div>
