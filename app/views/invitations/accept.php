<div class="text-center mb-4">
  <i class="bi bi-shop fs-1 text-primary"></i>
  <h5 class="fw-bold mt-2"><?= h($inv['store_name']) ?></h5>
  <p class="text-muted">
    <strong><?= h($inv['invited_name']) ?></strong>님, 근무 초대가 도착했습니다.<br>
    <small>시급 <?= number_format((int)$inv['hourly_wage']) ?>원 · 입사일 <?= h($inv['employment_start_date']) ?></small>
  </p>
</div>

<?php if ($errors): ?>
<div class="alert alert-danger small py-2">
  <?php foreach ($errors as $e): ?><div><?= h($e) ?></div><?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($mode === 'choose'): ?>
<div class="d-grid gap-2">
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="register">
    <input type="hidden" name="_mode" value="register">
    <button type="submit" class="btn btn-primary w-100">새 계정으로 가입하기</button>
  </form>
  <form method="POST">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="login">
    <input type="hidden" name="_mode" value="login">
    <button type="submit" class="btn btn-outline-secondary w-100">기존 계정으로 로그인하기</button>
  </form>
</div>

<?php elseif ($mode === 'register'): ?>
<form method="POST">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="register">
  <div class="mb-3">
    <label class="form-label">이름</label>
    <input type="text" name="name" class="form-control" value="<?= h($_POST['name'] ?? $inv['invited_name']) ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">이메일</label>
    <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? '') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">비밀번호</label>
    <input type="password" name="password" class="form-control" placeholder="8자 이상" required>
  </div>
  <button type="submit" class="btn btn-primary w-100">가입 후 합류</button>
</form>

<?php elseif ($mode === 'login'): ?>
<form method="POST">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="login">
  <div class="mb-3">
    <label class="form-label">이메일</label>
    <input type="email" name="email" class="form-control" value="<?= h($_POST['email'] ?? '') ?>" required>
  </div>
  <div class="mb-3">
    <label class="form-label">비밀번호</label>
    <input type="password" name="password" class="form-control" required>
  </div>
  <button type="submit" class="btn btn-primary w-100">로그인 후 합류</button>
</form>
<?php endif; ?>

<div class="text-center mt-3">
  <a href="<?= url('auth', 'login') ?>" class="text-muted small">일반 로그인으로</a>
</div>
