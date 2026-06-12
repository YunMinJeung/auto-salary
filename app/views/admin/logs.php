<?php $types = ['LOGIN','QR_ATTENDANCE','WORK_RECORD_CHANGE','PAYROLL_CALCULATION','ROLE_CHANGE','BUSINESS_SETTING_CHANGE','ERROR']; ?>
<div class="admin-card mb-3">
  <div class="admin-card-body">
    <form method="GET" action="<?= url('admin', 'logs') ?>" class="row g-2 align-items-end">
      <input type="hidden" name="c" value="admin">
      <input type="hidden" name="a" value="logs">
      <div class="col-auto">
        <label class="form-label small mb-1">로그 종류</label>
        <select name="type" class="form-select form-select-sm">
          <option value="">전체</option>
          <?php foreach ($types as $t): ?>
            <option value="<?= h($t) ?>" <?= ($filters['type'] ?? '') === $t ? 'selected' : '' ?>><?= h($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">사업장</label>
        <select name="store_id" class="form-select form-select-sm">
          <option value="">전체</option>
          <?php foreach ($stores as $s): ?>
            <option value="<?= (int)$s['id'] ?>" <?= (string)($filters['store_id'] ?? '') === (string)$s['id'] ? 'selected' : '' ?>><?= h($s['store_name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">시작일</label>
        <input type="date" name="date_from" class="form-control form-control-sm" value="<?= h($filters['date_from'] ?? '') ?>">
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">종료일</label>
        <input type="date" name="date_to" class="form-control form-control-sm" value="<?= h($filters['date_to'] ?? '') ?>">
      </div>
      <div class="col-auto">
        <label class="form-label small mb-1">결과</label>
        <select name="is_success" class="form-select form-select-sm">
          <option value="" <?= ($filters['is_success'] ?? '') === '' ? 'selected' : '' ?>>전체</option>
          <option value="1" <?= (string)($filters['is_success'] ?? '') === '1' ? 'selected' : '' ?>>성공</option>
          <option value="0" <?= (string)($filters['is_success'] ?? '') === '0' ? 'selected' : '' ?>>실패</option>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-funnel me-1"></i>필터</button>
        <a href="<?= url('admin', 'logs') ?>" class="btn btn-sm btn-outline-secondary">초기화</a>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><span><i class="bi bi-list-ul me-1"></i>시스템 로그</span><span class="small text-muted"><?= count($logs) ?>건</span></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>일시</th><th>종류</th><th>사용자</th><th>사업장</th><th>액션</th><th>결과</th><th>IP</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">로그가 없습니다.</td></tr>
        <?php else: foreach ($logs as $l): ?>
          <tr>
            <td class="small text-muted"><?= h(substr((string)($l['created_at'] ?? ''), 0, 19)) ?></td>
            <td class="small"><code><?= h($l['log_type'] ?? '') ?></code></td>
            <td class="small"><?= h($l['user_name'] ?? '-') ?></td>
            <td class="small"><?= h($l['store_name'] ?? '-') ?></td>
            <td class="small"><?= h($l['action'] ?? '') ?><?php if (!empty($l['error_message'])): ?><br><span class="small text-danger"><?= h($l['error_message']) ?></span><?php endif; ?></td>
            <td><span class="badge bg-<?= (int)($l['is_success'] ?? 1) ? 'success' : 'danger' ?>"><?= (int)($l['is_success'] ?? 1) ? '성공' : '실패' ?></span></td>
            <td class="small text-muted"><?= h($l['ip_address'] ?? '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
