<?php $isSuspended = ($user['account_status'] ?? 'ACTIVE') === 'SUSPENDED'; ?>
<a href="<?= url('admin', 'users') ?>" class="text-decoration-none small d-inline-block mb-3"><i class="bi bi-arrow-left"></i> 사용자 목록</a>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card mb-3">
      <div class="admin-card-header">
        <span><i class="bi bi-person me-1"></i>기본 정보</span>
        <span><?= adminBadge($user['role'] ?? '') ?> <?= adminBadge($user['account_status'] ?? 'ACTIVE') ?></span>
      </div>
      <div class="admin-card-body">
        <dl class="row mb-0 small">
          <dt class="col-sm-3 text-muted">이름</dt><dd class="col-sm-9 fw-semibold"><?= h($user['name'] ?? '-') ?></dd>
          <dt class="col-sm-3 text-muted">이메일</dt><dd class="col-sm-9"><?= h($user['email'] ?? '') ?></dd>
          <dt class="col-sm-3 text-muted">전화</dt><dd class="col-sm-9"><?= h($user['phone'] ?? '-') ?></dd>
          <dt class="col-sm-3 text-muted">소유 사업장</dt><dd class="col-sm-9"><?= h($user['store_names'] ?? '-') ?></dd>
          <dt class="col-sm-3 text-muted">가입일</dt><dd class="col-sm-9"><?= h(substr((string)($user['created_at'] ?? ''), 0, 19)) ?></dd>
        </dl>
      </div>
    </div>

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

  <div class="col-lg-4">
    <!-- 계정 상태 -->
    <div class="admin-card mb-3">
      <div class="admin-card-header"><span><i class="bi bi-shield-lock me-1"></i>계정 관리</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'user_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
          <input type="hidden" name="action" value="<?= $isSuspended ? 'reactivate' : 'suspend' ?>">
          <div class="mb-2">
            <input type="text" name="reason" class="form-control form-control-sm" placeholder="사유 (선택)">
          </div>
          <?php if ($isSuspended): ?>
            <button type="submit" class="btn btn-sm btn-success w-100"><i class="bi bi-check-circle me-1"></i>계정 재활성화</button>
          <?php else: ?>
            <button type="submit" class="btn btn-sm btn-danger w-100" onclick="return confirm('이 계정을 정지하시겠습니까?')"><i class="bi bi-slash-circle me-1"></i>계정 정지</button>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- 관리자 메모 -->
    <div class="admin-card">
      <div class="admin-card-header"><span><i class="bi bi-sticky me-1"></i>관리자 메모</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'user_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$user['id'] ?>">
          <input type="hidden" name="action" value="memo">
          <textarea name="admin_memo" class="form-control form-control-sm mb-2" rows="5" placeholder="내부 메모"><?= h($user['admin_memo'] ?? '') ?></textarea>
          <button type="submit" class="btn btn-sm btn-outline-secondary w-100">메모 저장</button>
        </form>
      </div>
    </div>
  </div>
</div>
