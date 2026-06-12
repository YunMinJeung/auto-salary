<section class="landing-section">
  <div class="container signup-form-wrap">

    <h2 class="section-title">알바로 가입하기</h2>
    <p class="section-lead">가입 후 사장님이 보낸 초대 링크로 사업장에 연결할 수 있어요.</p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
      <div><i class="bi bi-exclamation-circle me-1"></i><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('signup', 'employee') ?>">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">이름 <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= h($old['name'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">이메일 <span class="text-danger">*</span></label>
        <input type="email" name="email" class="form-control" value="<?= h($old['email'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">비밀번호 <span class="text-danger">*</span>
          <small class="text-muted fw-normal">(8자 이상)</small></label>
        <input type="password" name="password" class="form-control" minlength="8" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">비밀번호 확인 <span class="text-danger">*</span></label>
        <input type="password" name="password_confirm" class="form-control" required>
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">휴대폰 번호 <small class="text-muted fw-normal">(선택)</small></label>
        <input type="tel" name="phone" class="form-control" value="<?= h($old['phone'] ?? '') ?>" placeholder="010-0000-0000">
      </div>

      <div class="mb-3 pt-2 border-top">
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="agree_terms" id="agree_terms" required>
          <label class="form-check-label small" for="agree_terms">
            <a href="<?= url('terms') ?>" target="_blank" class="fw-semibold">이용약관</a>에 동의합니다 <span class="text-danger">*</span>
          </label>
        </div>
        <div class="form-check mb-2">
          <input class="form-check-input" type="checkbox" name="agree_privacy" id="agree_privacy" required>
          <label class="form-check-label small" for="agree_privacy">
            <a href="<?= url('privacy') ?>" target="_blank" class="fw-semibold">개인정보 수집·이용</a>에 동의합니다 <span class="text-danger">*</span>
          </label>
        </div>
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="marketing_agreed" id="marketing_agreed" <?= !empty($old['marketing_agreed']) ? 'checked' : '' ?>>
          <label class="form-check-label small text-muted" for="marketing_agreed">
            마케팅 정보 수신에 동의합니다 (선택)
          </label>
        </div>
      </div>

      <button type="submit" class="btn btn-form-primary w-100">가입하기</button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
      이미 계정이 있으신가요?
      <a href="<?= BASE_URL ?>login" class="fw-semibold text-decoration-none">로그인하기</a>
    </p>
  </div>
</section>
