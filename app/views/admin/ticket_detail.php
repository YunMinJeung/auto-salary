<?php $statuses = ['OPEN','IN_PROGRESS','HOLD','ANSWERED','CLOSED']; ?>
<a href="<?= url('admin', 'tickets') ?>" class="text-decoration-none small d-inline-block mb-3"><i class="bi bi-arrow-left"></i> 문의 목록</a>

<div class="row g-3">
  <div class="col-lg-8">
    <div class="admin-card mb-3">
      <div class="admin-card-header">
        <span><i class="bi bi-chat-square-text me-1"></i><?= h($ticket['title']) ?></span>
        <span><?= adminBadge($ticket['status'] ?? 'OPEN') ?></span>
      </div>
      <div class="admin-card-body">
        <div class="small text-muted mb-2">
          <?= h($ticket['user_name'] ?? '-') ?> (<?= h($ticket['user_email'] ?? '') ?>)
          · <?= h($ticket['store_name'] ?? '-') ?>
          · <?= h($ticket['ticket_type'] ?? '') ?>
          · <?= h(substr((string)($ticket['created_at'] ?? ''), 0, 19)) ?>
        </div>
        <div class="p-3 bg-light rounded" style="white-space:pre-wrap"><?= h($ticket['content']) ?></div>

        <?php if (!empty($ticket['admin_reply'])): ?>
        <div class="mt-3">
          <div class="small fw-semibold text-success mb-1"><i class="bi bi-reply me-1"></i>관리자 답변 (<?= h(substr((string)($ticket['replied_at'] ?? ''), 0, 19)) ?>)</div>
          <div class="p-3 rounded" style="white-space:pre-wrap;background:#f0fdf4;border:1px solid #dcfce7"><?= h($ticket['admin_reply']) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 답변 폼 -->
    <div class="admin-card">
      <div class="admin-card-header"><span><i class="bi bi-reply-fill me-1"></i>답변 작성</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'ticket_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$ticket['id'] ?>">
          <input type="hidden" name="action" value="reply">
          <div class="mb-2">
            <label class="form-label small">답변 내용</label>
            <textarea name="admin_reply" class="form-control" rows="5" placeholder="고객에게 전달될 답변"><?= h($ticket['admin_reply'] ?? '') ?></textarea>
          </div>
          <div class="mb-2">
            <label class="form-label small">내부 메모 (고객 비공개)</label>
            <textarea name="admin_memo" class="form-control form-control-sm" rows="2"><?= h($ticket['admin_memo'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-sm" style="background:var(--admin-accent);color:#fff"><i class="bi bi-send me-1"></i>답변 저장 (상태→ANSWERED)</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="admin-card">
      <div class="admin-card-header"><span><i class="bi bi-toggle-on me-1"></i>상태 변경</span></div>
      <div class="admin-card-body">
        <form method="POST" action="<?= url('admin', 'ticket_update') ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int)$ticket['id'] ?>">
          <input type="hidden" name="action" value="status">
          <select name="status" class="form-select form-select-sm mb-2">
            <?php foreach ($statuses as $st): ?>
              <option value="<?= h($st) ?>" <?= ($ticket['status'] ?? '') === $st ? 'selected' : '' ?>><?= adminLabel($st) ?></option>
            <?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-sm btn-outline-secondary w-100">상태 변경</button>
        </form>
      </div>
    </div>
  </div>
</div>
