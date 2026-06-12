<div class="card shadow-sm border-0">
  <div class="card-body p-4">
    <h4 class="fw-bold mb-4" style="color:var(--c-dark)">로그인</h4>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
      <div><i class="bi bi-exclamation-circle me-1"></i><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('auth', 'login') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">이메일</label>
        <input type="email" name="email" class="form-control"
               value="<?= h($_POST['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">비밀번호</label>
        <input type="password" name="password" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-box-arrow-in-right me-1"></i>로그인
      </button>
    </form>

    <hr class="my-4">

    <p class="text-center text-muted mb-0 small">
      계정이 없으신가요?
      <a href="<?= url('signup') ?>" class="fw-semibold text-decoration-none" style="color:var(--c-teal)">
        회원가입
      </a>
    </p>
  </div>
</div>
