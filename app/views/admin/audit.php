<div class="admin-card">
  <div class="admin-card-header"><span><i class="bi bi-journal-check me-1"></i>Audit Log (최근 100건)</span><span class="small text-muted"><?= count($logs) ?>건</span></div>
  <div class="table-responsive">
    <table class="admin-table">
      <thead><tr><th>일시</th><th>작업자</th><th>액션</th><th>대상</th><th>변경 전 → 후</th><th>사유</th><th>IP</th></tr></thead>
      <tbody>
        <?php if (empty($logs)): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">이력이 없습니다.</td></tr>
        <?php else: foreach ($logs as $log): ?>
          <tr>
            <td class="small text-muted"><?= h(substr((string)($log['created_at'] ?? ''), 0, 19)) ?></td>
            <td class="small"><?= h($log['actor_name'] ?? '-') ?><br><span class="small text-muted"><?= h($log['actor_role'] ?? '') ?></span></td>
            <td class="small"><code><?= h($log['action'] ?? '') ?></code></td>
            <td class="small"><?= h($log['target_type'] ?? '') ?><?php if ($log['target_id'] !== null): ?> #<?= (int)$log['target_id'] ?><?php endif; ?></td>
            <td class="small text-muted" style="max-width:280px"><?= h($log['before_value'] ?? '') ?> → <?= h($log['after_value'] ?? '') ?></td>
            <td class="small"><?= h($log['reason'] ?? '') ?></td>
            <td class="small text-muted"><?= h($log['ip_address'] ?? '-') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
