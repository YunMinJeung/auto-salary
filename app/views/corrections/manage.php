<div class="d-flex align-items-center mb-4 gap-3">
  <a href="<?= url('attendance') ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0"><i class="bi bi-pencil-square me-2 text-warning"></i>수정 요청 관리</h1>
</div>

<?php if (empty($requests)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-check-circle-fill fs-1 text-success d-block mb-2"></i>
    처리 대기 중인 수정 요청이 없습니다.
  </div>
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>직원</th>
          <th>원본 기록</th>
          <th>요청 시각</th>
          <th>요청 내용</th>
          <th>사유</th>
          <th>상태</th>
          <th>처리</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($requests as $req): ?>
        <tr>
          <td class="fw-semibold"><?= h($req['member_name']) ?></td>
          <td class="text-muted">
            <?php if ($req['original_in']): ?>
              <?= date('m/d H:i', strtotime($req['original_in'])) ?>
              ~ <?= $req['original_out'] ? date('H:i', strtotime($req['original_out'])) : '?' ?>
            <?php else: ?>
              <span class="text-danger small">누락 기록</span>
            <?php endif; ?>
          </td>
          <td class="text-muted"><?= date('m/d H:i', strtotime($req['created_at'])) ?></td>
          <td>
            <?php if ($req['requested_clock_in_at']): ?>
              <?= date('m/d H:i', strtotime($req['requested_clock_in_at'])) ?>
              ~ <?= $req['requested_clock_out_at'] ? date('H:i', strtotime($req['requested_clock_out_at'])) : '?' ?>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td style="max-width:160px"><?= h($req['reason']) ?></td>
          <td>
            <?php
            $statusMap = [
              'pending'  => ['bg-warning text-dark', '대기'],
              'approved' => ['bg-success', '승인'],
              'rejected' => ['bg-danger', '반려'],
            ];
            [$bc, $bl] = $statusMap[$req['status']] ?? ['bg-secondary', $req['status']];
            ?>
            <span class="badge <?= $bc ?>"><?= $bl ?></span>
            <?php if ($req['owner_comment']): ?>
            <div class="text-muted" style="font-size:.7rem"><?= h($req['owner_comment']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($req['status'] === 'pending'): ?>
            <div class="d-flex gap-1">
              <button class="btn btn-xs btn-outline-success"
                      onclick="handleRequest(<?= $req['id'] ?>, 'approve')"
                      style="font-size:.75rem;padding:.2rem .5rem">승인</button>
              <button class="btn btn-xs btn-outline-danger"
                      onclick="handleRequest(<?= $req['id'] ?>, 'reject')"
                      style="font-size:.75rem;padding:.2rem .5rem">반려</button>
            </div>
            <?php else: ?>
            <span class="text-muted small">완료</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>

<!-- 승인/반려 폼 (hidden) -->
<form id="action-form" method="post" style="display:none">
  <?= csrf_field() ?>
  <input type="hidden" name="id" id="action-id">
  <input type="hidden" name="owner_comment" id="action-comment">
</form>

<script>
function handleRequest(id, action) {
  var comment = '';
  if (action === 'reject') {
    comment = prompt('반려 사유를 입력하세요 (선택사항)');
    if (comment === null) return; // 취소
  }
  var form = document.getElementById('action-form');
  form.action = '<?= BASE_URL ?>index.php?c=attendance&a=' + (action === 'approve' ? 'approve_correction' : 'reject_correction');
  document.getElementById('action-id').value      = id;
  document.getElementById('action-comment').value = comment || '';
  form.submit();
}
</script>
