<div class="card shadow-sm border-0">
  <div class="card-body p-4">
    <h4 class="fw-bold mb-4" style="color:var(--c-dark)">회원가입</h4>

    <?php if ($errors): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
      <div><i class="bi bi-exclamation-circle me-1"></i><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('auth', 'register') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">사업장 이름</label>
        <input type="text" name="business_name" class="form-control"
               value="<?= h($old['business_name'] ?? '') ?>" required placeholder="예) 홍길동 편의점">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">담당자 이름</label>
        <input type="text" name="name" class="form-control"
               value="<?= h($old['name'] ?? '') ?>" required placeholder="예) 홍길동">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">이메일</label>
        <input type="email" name="email" class="form-control"
               value="<?= h($old['email'] ?? '') ?>" required autofocus>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">비밀번호 <small class="text-muted fw-normal">(8자 이상)</small></label>
        <input type="password" name="password" class="form-control" required minlength="8">
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">비밀번호 확인</label>
        <input type="password" name="password_confirm" class="form-control" required>
      </div>

      <button type="submit" class="btn btn-primary w-100">
        <i class="bi bi-person-plus-fill me-1"></i>가입하기
      </button>
    </form>

    <hr class="my-4">

    <p class="text-center text-muted mb-0 small">
      이미 계정이 있으신가요?
      <a href="<?= url('auth', 'login') ?>" class="fw-semibold text-decoration-none" style="color:var(--c-teal)">
        로그인
      </a>
    </p>
  </div>
</div>
