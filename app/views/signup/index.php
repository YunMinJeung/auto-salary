<section class="landing-section">
  <div class="container signup-wrap">
    <h2 class="section-title">어떻게 시작하시겠어요?</h2>
    <p class="section-lead">역할에 맞는 가입 방식을 선택하세요.</p>

    <div class="row g-4 justify-content-center">
      <div class="col-md-6">
        <div class="choice-card owner text-center d-flex flex-column">
          <div class="choice-icon mb-3"><i class="bi bi-shop"></i></div>
          <h4 class="fw-bold mb-2">사장님으로 시작하기</h4>
          <p class="text-muted flex-grow-1">사업장을 만들고 직원의 출퇴근, 급여, 급여명세서를 관리합니다.</p>
          <a href="<?= url('signup', 'owner') ?>" class="btn btn-cta w-100 mt-2">사업장 만들기</a>
        </div>
      </div>
      <div class="col-md-6">
        <div class="choice-card employee text-center d-flex flex-column">
          <div class="choice-icon mb-3"><i class="bi bi-person-badge"></i></div>
          <h4 class="fw-bold mb-2">알바님으로 시작하기</h4>
          <p class="text-muted flex-grow-1">초대받은 사업장에 연결하고 내 출퇴근, 근무기록, 급여명세서를 확인합니다.</p>
          <a href="<?= url('signup', 'employee') ?>" class="btn btn-outline-teal w-100 mt-2">알바로 가입하기</a>
        </div>
      </div>
    </div>

    <div class="text-center mt-4">
      <p class="text-muted small mb-1">
        <i class="bi bi-info-circle me-1"></i>알바는 보통 사장님이 보낸 초대 링크를 통해 가입하면 더 빠르게 연결됩니다.
      </p>
      <p class="text-muted small mb-0">
        이미 계정이 있으신가요?
        <a href="<?= url('auth', 'login') ?>" class="fw-semibold text-decoration-none">로그인하기</a>
      </p>
    </div>
  </div>
</section>
