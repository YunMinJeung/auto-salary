<?php $roles = ['super_admin','admin','owner','employee']; ?>
<div class="admin-card mb-3">
  <div class="admin-card-body">
    <form method="GET" action="<?= url('admin', 'users') ?>" class="row g-2 align-items-end">
      <input type="hidden" name="c" value="admin">
      <input type="hidden" name="a" value="users">
      <div class="col-auto">
        <label class="form-label small mb-1">역할</label>
        <select name="role" class="form-select form-select-sm">
          <option value="">전체</option>
          <?php foreach ($roles as $r): ?>
            <option value="<?= h($r) ?>" <?= ($filters['role'] ?? '') === $r ? 'selected' : '' ?>><?= adminLabel($r) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">검색 (이름/이메일)</label>
        <input type="text" name="search" class="form-control form-control-sm" value="<?= h($filters['search'] ?? '') ?>" style="min-width:240px">
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-search me-1"></i>검색</button>
        <a href="<?= url('admin', 'users') ?>" class="btn btn-sm btn-outline-secondary">초기화</a>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header">
    <span><i class="bi bi-people-fill me-1"></i>사용자 목록</span>
    <span class="small text-muted"><?= count($users) ?>건</span>
  </div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead>
        <tr><th>이름</th><th>이메일</th><th>역할</th><th>계정상태</th><th>소유 사업장</th><th>가입일</th><th></th></tr>
      </thead>
      <tbody>
        <?php if (empty($users)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">조건에 맞는 사용자가 없습니다.</td></tr>
        <?php else: foreach ($users as $u): ?>
          <tr>
            <td class="fw-semibold"><?= h($u['name'] ?? '-') ?></td>
            <td class="small"><?= h($u['email'] ?? '') ?></td>
            <td><?= adminBadge($u['role'] ?? '') ?></td>
            <td><?= adminBadge($u['account_status'] ?? 'ACTIVE') ?></td>
            <td class="small text-muted"><?= h($u['store_names'] ?? '-') ?></td>
            <td class="small text-muted"><?= h(substr((string)($u['created_at'] ?? ''), 0, 10)) ?></td>
            <td class="text-end"><a href="<?= url('admin', 'user_detail', ['id' => $u['id']]) ?>" class="btn btn-sm btn-outline-secondary">상세</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
