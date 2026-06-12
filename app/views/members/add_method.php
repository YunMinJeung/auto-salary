<div class="d-flex align-items-center mb-4">
  <a href="<?= url('members') ?>" class="btn btn-sm btn-outline-secondary me-3">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h1 class="h3 mb-0"><i class="bi bi-person-plus-fill me-2 text-primary"></i>직원 추가 방법 선택</h1>
</div>

<div class="row g-3" style="max-width:720px">

  <!-- 방식 1: 직접 등록 -->
  <div class="col-md-4">
    <a href="<?= url('members', 'create') ?>" class="text-decoration-none">
      <div class="card border-0 shadow-sm h-100 text-center p-4" style="cursor:pointer;transition:.15s"
           onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <i class="bi bi-person-fill-add fs-1 text-primary mb-3"></i>
        <h6 class="fw-bold mb-2">직접 등록</h6>
        <p class="text-muted small mb-0">
          앱 계정 없이 직원 카드만 생성합니다.<br>
          고령 직원, 단기 알바, 지문기록 전용 직원에 적합합니다.
        </p>
      </div>
    </a>
  </div>

  <!-- 방식 2: 초대 링크 -->
  <div class="col-md-4">
    <a href="<?= url('invite', 'form') ?>" class="text-decoration-none">
      <div class="card border-0 shadow-sm h-100 text-center p-4" style="cursor:pointer;transition:.15s"
           onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <i class="bi bi-link-45deg fs-1 text-success mb-3"></i>
        <h6 class="fw-bold mb-2">초대 링크로 추가</h6>
        <p class="text-muted small mb-0">
          알바가 본인 계정으로 가입하거나 로그인해 초대를 수락합니다.<br>
          모바일 앱 사용이 가능한 직원에 적합합니다.
        </p>
      </div>
    </a>
  </div>

  <!-- 방식 3: 가입 도와주기 -->
  <div class="col-md-4">
    <a href="<?= url('invite', 'form') ?>?guided=1" class="text-decoration-none">
      <div class="card border-0 shadow-sm h-100 text-center p-4" style="cursor:pointer;transition:.15s"
           onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform=''">
        <i class="bi bi-qr-code fs-1 text-warning mb-3"></i>
        <h6 class="fw-bold mb-2">가입 도와주기</h6>
        <p class="text-muted small mb-0">
          현장에서 직원 휴대폰으로 QR을 스캔해 직접 가입하게 합니다.<br>
          비밀번호·동의는 직원 본인이 직접 처리합니다.
        </p>
      </div>
    </a>
  </div>

</div>

<div class="mt-4">
  <div class="alert alert-info small py-2" style="max-width:720px">
    <i class="bi bi-info-circle me-1"></i>
    <strong>개인정보 보호:</strong> 직원 개인 계정은 직원 본인이 직접 생성하고 소유합니다.
    사장님은 직원의 로그인 비밀번호를 설정하거나 알 수 없습니다.
  </div>
</div>
