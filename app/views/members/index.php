<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>직원 계정 관리</h1>
  <a href="<?= url('members', 'create') ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>직원 등록
  </a>
</div>

<div class="alert alert-info small mb-3">
  <i class="bi bi-info-circle me-1"></i>
  이 목록은 <strong>출퇴근 시스템</strong>에서 사용하는 직원 계정입니다.
  급여 계산용 직원 목록은 <a href="<?= url('employees') ?>">직원 관리</a>에서 따로 관리됩니다.
</div>

<?php if (empty($members)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
    등록된 직원이 없습니다.
    <a href="<?= url('members', 'create') ?>" class="d-block mt-2">첫 직원 등록하기</a>
  </div>
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>이름</th>
          <th>연락처</th>
          <th>시급</th>
          <th>앱 계정</th>
          <th>상태</th>
          <th class="text-end pe-3">액션</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
          <td class="fw-semibold">
            <a href="<?= url('members', 'edit', ['id' => $m['id']]) ?>"
               class="text-decoration-none" style="color:var(--c-dark)">
              <?= h($m['name']) ?>
            </a>
          </td>
          <td class="text-muted"><?= h($m['phone'] ?: '—') ?></td>
          <td><?= number_format($m['hourly_wage']) ?>원</td>
          <td>
            <?php if ($m['user_id']): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-person-check me-1"></i><?= h($m['user_email'] ?? '계정있음') ?>
              </span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border">미발급</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($m['is_active']): ?>
              <span class="badge bg-primary-subtle text-primary border">재직</span>
            <?php else: ?>
              <span class="badge bg-danger-subtle text-danger border">퇴직</span>
            <?php endif; ?>
          </td>
          <td class="text-end pe-3">
            <div class="d-flex gap-1 justify-content-end">
              <a href="<?= url('members', 'edit', ['id' => $m['id']]) ?>"
                 class="btn btn-sm btn-outline-primary"
                 data-bs-toggle="tooltip" title="수정">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" action="<?= url('members', 'delete') ?>"
                    onsubmit="return confirm('<?= h(addslashes($m['name'])) ?> 직원을 삭제하면 출퇴근 기록도 함께 삭제됩니다.\n정말 삭제하시겠습니까?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger ms-1"
                        data-bs-toggle="tooltip" title="삭제">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
