<section class="landing-section">
  <div class="container signup-form-wrap">

    <h2 class="section-title">사업장 만들기</h2>

    <div class="step-indicator">
      <div class="step-dot active" id="dot1"><span class="num">1</span> 계정 정보</div>
      <div class="step-dot" id="dot2"><span class="num">2</span> 사업장 정보</div>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
      <?php foreach ($errors as $e): ?>
      <div><i class="bi bi-exclamation-circle me-1"></i><?= h($e) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('signup', 'owner') ?>" id="ownerForm" novalidate>
      <?= csrf_field() ?>

      <!-- ── Step 1: 계정 정보 ── -->
      <div id="step1">
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
          <input type="password" name="password" id="pw" class="form-control" minlength="8" required>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">비밀번호 확인 <span class="text-danger">*</span></label>
          <input type="password" name="password_confirm" id="pwc" class="form-control" required>
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">휴대폰 번호 <small class="text-muted fw-normal">(선택)</small></label>
          <input type="tel" name="phone" class="form-control" value="<?= h($old['phone'] ?? '') ?>" placeholder="010-0000-0000">
        </div>

        <div id="step1Error" class="text-danger small mb-2" style="display:none"></div>
        <button type="button" class="btn btn-form-primary w-100" id="nextBtn">다음</button>
      </div>

      <!-- ── Step 2: 사업장 정보 ── -->
      <div id="step2" style="display:none">
        <div class="mb-3">
          <label class="form-label fw-semibold">사업장명 <span class="text-danger">*</span></label>
          <input type="text" name="store_name" class="form-control" value="<?= h($old['store_name'] ?? '') ?>" required placeholder="예) 홍길동 편의점">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">사업자등록번호 <small class="text-muted fw-normal">(선택)</small></label>
          <input type="text" name="business_number" class="form-control" value="<?= h($old['business_number'] ?? '') ?>" placeholder="000-00-00000">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">대표자명 <small class="text-muted fw-normal">(선택)</small></label>
          <input type="text" name="representative_name" class="form-control" value="<?= h($old['representative_name'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">주소 <small class="text-muted fw-normal">(선택)</small></label>
          <input type="text" name="address" class="form-control" value="<?= h($old['address'] ?? '') ?>">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">상시근로자 수 <small class="text-muted fw-normal">(선택)</small></label>
          <input type="number" name="employee_count" class="form-control" value="<?= h($old['employee_count'] ?? '') ?>" min="0">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold d-block">5인 이상 여부 <small class="text-muted fw-normal">(선택)</small></label>
          <?php $fom = $old['five_or_more'] ?? ''; ?>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="five_or_more" id="fom_yes" value="예" <?= $fom === '예' ? 'checked' : '' ?>>
              <label class="form-check-label" for="fom_yes">예</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="five_or_more" id="fom_no" value="아니오" <?= $fom === '아니오' ? 'checked' : '' ?>>
              <label class="form-check-label" for="fom_no">아니오</label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="five_or_more" id="fom_unknown" value="모름" <?= $fom === '모름' ? 'checked' : '' ?>>
              <label class="form-check-label" for="fom_unknown">모름</label>
            </div>
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">급여 지급일 <small class="text-muted fw-normal">(선택, 1~31)</small></label>
          <input type="number" name="pay_day" class="form-control" value="<?= h($old['pay_day'] ?? '') ?>" min="1" max="31">
        </div>
        <div class="mb-4">
          <label class="form-label fw-semibold">기본 시급 <small class="text-muted fw-normal">(선택)</small></label>
          <input type="number" name="hourly_wage" class="form-control" value="<?= h($old['hourly_wage'] ?? '') ?>" min="0" placeholder="10320">
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

        <div class="d-flex gap-2">
          <button type="button" class="btn btn-outline-secondary" id="prevBtn">이전</button>
          <button type="submit" class="btn btn-form-primary flex-grow-1">가입 완료</button>
        </div>
      </div>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
      이미 계정이 있으신가요?
      <a href="<?= BASE_URL ?>login" class="fw-semibold text-decoration-none">로그인하기</a>
    </p>
  </div>
</section>

<script>
(function () {
  var step1 = document.getElementById('step1');
  var step2 = document.getElementById('step2');
  var dot1  = document.getElementById('dot1');
  var dot2  = document.getElementById('dot2');
  var err   = document.getElementById('step1Error');

  function showStep(n) {
    step1.style.display = n === 1 ? '' : 'none';
    step2.style.display = n === 2 ? '' : 'none';
    dot1.classList.toggle('active', n === 1);
    dot2.classList.toggle('active', n === 2);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  document.getElementById('nextBtn').addEventListener('click', function () {
    var name  = document.querySelector('[name=name]').value.trim();
    var email = document.querySelector('[name=email]').value.trim();
    var pw    = document.getElementById('pw').value;
    var pwc   = document.getElementById('pwc').value;
    var msgs  = [];

    if (!name)  msgs.push('이름을 입력하세요.');
    if (!/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(email)) msgs.push('올바른 이메일을 입력하세요.');
    if (pw.length < 8) msgs.push('비밀번호는 8자 이상이어야 합니다.');
    if (pw !== pwc) msgs.push('비밀번호 확인이 일치하지 않습니다.');

    if (msgs.length) {
      err.innerHTML = msgs.join('<br>');
      err.style.display = 'block';
      return;
    }
    err.style.display = 'none';
    showStep(2);
  });

  document.getElementById('prevBtn').addEventListener('click', function () {
    showStep(1);
  });

  // 서버 검증 실패로 사업장 항목 입력값이 남아 있으면 step2부터 보여줌
  <?php if (!empty($errors) && !empty($old['store_name'])): ?>
  showStep(2);
  <?php endif; ?>
})();
</script>
