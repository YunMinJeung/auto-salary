<?php $statuses = ['OPEN','IN_PROGRESS','HOLD','ANSWERED','CLOSED']; ?>
<div class="admin-card mb-3">
  <div class="admin-card-body">
    <form method="GET" action="<?= url('admin', 'tickets') ?>" class="row g-2 align-items-end">
      <input type="hidden" name="c" value="admin">
      <input type="hidden" name="a" value="tickets">
      <div class="col-auto">
        <label class="form-label small mb-1">상태</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">전체</option>
          <?php foreach ($statuses as $st): ?>
            <option value="<?= h($st) ?>" <?= ($filters['status'] ?? '') === $st ? 'selected' : '' ?>><?= adminLabel($st) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-auto">
        <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-funnel me-1"></i>필터</button>
        <a href="<?= url('admin', 'tickets') ?>" class="btn btn-sm btn-outline-secondary">초기화</a>
      </div>
    </form>
  </div>
</div>

<div class="admin-card">
  <div class="admin-card-header"><span><i class="bi bi-chat-left-text-fill me-1"></i>문의/피드백</span><span class="small text-muted"><?= count($tickets) ?>건</span></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>제목</th><th>유형</th><th>작성자</th><th>사업장</th><th>상태</th><th>등록일</th><th></th></tr></thead>
      <tbody>
        <?php if (empty($tickets)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">문의가 없습니다.</td></tr>
        <?php else: foreach ($tickets as $t): ?>
          <tr>
            <td class="fw-semibold"><?= h($t['title']) ?></td>
            <td class="small"><?= h($t['ticket_type'] ?? '') ?></td>
            <td class="small"><?= h($t['user_name'] ?? '-') ?><br><span class="small text-muted"><?= h($t['user_email'] ?? '') ?></span></td>
            <td class="small"><?= h($t['store_name'] ?? '-') ?></td>
            <td><?= adminBadge($t['status'] ?? 'OPEN') ?></td>
            <td class="small text-muted"><?= h(substr((string)($t['created_at'] ?? ''), 0, 16)) ?></td>
            <td class="text-end"><a href="<?= url('admin', 'ticket_detail', ['id' => $t['id']]) ?>" class="btn btn-sm btn-outline-secondary">보기</a></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
