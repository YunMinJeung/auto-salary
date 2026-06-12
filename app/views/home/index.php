<?php
$features = [
    ['bi-qr-code-scan',       'QR 출퇴근',              '매장에 붙인 QR을 스캔하면 출퇴근 기록 완료. 별도 앱 설치 없이 스마트폰 카메라만으로 가능합니다.'],
    ['bi-clock-history',      '근무시간 자동 계산',      '출퇴근 기록을 기반으로 근무시간·휴게시간을 자동 계산합니다. 손으로 더할 필요가 없어요.'],
    ['bi-calendar-check',     '주휴수당 자동 확인',      '주 15시간 이상 근무 시 주휴수당을 자동 계산해 급여에 반영합니다.'],
    ['bi-file-earmark-text',  '급여명세서 즉시 발급',    '확정 급여로 법정 양식의 급여명세서를 원클릭 발급하고 이력도 보관합니다.'],
    ['bi-pencil-square',      '근무 수정 요청',          '근무시간 정정 요청을 카톡 대신 앱 안에서 주고받고 기록으로 남깁니다.'],
    ['bi-shield-check',       '노동법 리스크 알림',      '최저임금 미달·연장근로 위반 가능성을 자동으로 감지해 미리 알려드립니다.'],
];

$ownerItems    = ['직원 등록 및 초대 링크 발송', 'QR 출퇴근 기록 관리', '주간·월간 급여 자동 계산', '급여명세서 원클릭 발급', '노동법 리스크 자동 알림'];
$employeeItems = ['스마트폰 QR 출퇴근', '내 근무기록 실시간 확인', '예상 급여 자동 계산', '급여명세서 열람·저장', '근무시간 수정 요청'];

$steps = [
    ['bi-shop',           '사업장 등록',    "이름·주소만 입력하면\n30초 만에 완료"],
    ['bi-send',           '알바 초대',      "링크 전송 → 알바생이\n스마트폰으로 가입"],
    ['bi-qr-code',        'QR 출퇴근 시작', "QR 코드 출력해서 붙이면\n바로 출퇴근 기록"],
    ['bi-file-earmark-check', '명세서 발급', "월말에 버튼 하나로\n전원 명세서 발급"],
];

$faqs = [
    ['주휴수당도 자동 계산되나요?', '네. 주 15시간 이상 근무 시 주휴수당을 자동 계산해 급여에 포함합니다. 근무 형태에 따른 발생 여부도 안내해 드립니다.'],
    ['급여명세서를 직원이 직접 볼 수 있나요?', '네. 발급된 명세서는 알바생 계정에서 언제든 열람·저장할 수 있습니다.'],
    ['알바가 여러 곳에서 일해도 되나요?', '네. 알바 계정 하나로 여러 사업장에 연결할 수 있도록 설계되어 있습니다.'],
    ['4대보험도 자동 계산되나요?', '가입 여부에 따른 공제액 계산을 지원합니다. 가입 확인이 필요한 항목은 별도로 안내해 드립니다.'],
    ['개인정보는 안전한가요?', '주민등록번호를 수집하지 않으며, 이름·생년월일 등 수집 정보는 암호화 저장됩니다. 자세한 내용은 개인정보처리방침을 확인해 주세요.'],
];
?>

<!-- ── 히어로 ────────────────────────────────────────────── -->
<section class="hero">
  <div class="container">
    <div class="hero-grid">

      <!-- 왼쪽: 카피 -->
      <div>
        <div class="hero-badge">
          <i class="bi bi-patch-check-fill"></i> 베타 무료 &middot; 지금 바로 시작
        </div>
        <h1>알바 근태와 급여,<br>이제 <em>자동으로</em> 관리하세요.</h1>
        <p class="hero-sub">
          QR 출퇴근부터 주휴수당 계산, 급여명세서 발급까지<br>
          복잡한 알바 관리를 한곳에서 처리할 수 있어요.
        </p>
        <div class="hero-btns">
          <a href="<?= url('signup', 'owner') ?>" class="btn-hero-primary">
            <i class="bi bi-shop"></i>사업장 만들기
          </a>
          <a href="<?= url('signup', 'employee') ?>" class="btn-hero-outline">
            <i class="bi bi-person-badge"></i>알바로 시작하기
          </a>
        </div>
      </div>

      <!-- 오른쪽: 대시보드 목업 -->
      <div class="hero-visual">
        <div class="dash-card">
          <div class="dash-card-header">
            <span class="title"><i class="bi bi-grid-1x2-fill me-1 text-teal"></i>오늘의 근태 현황</span>
            <span class="date">2025년 5월</span>
          </div>
          <div class="dash-stats">
            <div class="dash-stat green">
              <div class="val">3명</div>
              <div class="lbl">출근 완료</div>
            </div>
            <div class="dash-stat red">
              <div class="val">1명</div>
              <div class="lbl">미출근</div>
            </div>
            <div class="dash-stat gold">
              <div class="val">주휴</div>
              <div class="lbl">지급 예정</div>
            </div>
          </div>
          <div class="dash-rows">
            <div class="dash-row">
              <div class="dash-row-left">
                <span class="dash-row-dot on"></span>
                <div>
                  <div class="dash-row-name">김철수</div>
                  <div class="dash-row-time">09:01 출근</div>
                </div>
              </div>
              <span class="dash-row-badge on">근무중</span>
            </div>
            <div class="dash-row">
              <div class="dash-row-left">
                <span class="dash-row-dot on"></span>
                <div>
                  <div class="dash-row-name">이영희</div>
                  <div class="dash-row-time">10:30 출근</div>
                </div>
              </div>
              <span class="dash-row-badge on">근무중</span>
            </div>
            <div class="dash-row">
              <div class="dash-row-left">
                <span class="dash-row-dot off"></span>
                <div>
                  <div class="dash-row-name">박민준</div>
                  <div class="dash-row-time">13:00 예정</div>
                </div>
              </div>
              <span class="dash-row-badge off">대기</span>
            </div>
          </div>
          <div class="dash-pay-summary">
            <div>
              <div class="pay-label">이번 주 예상 인건비</div>
              <div class="pay-val">₩ 486,000</div>
            </div>
            <div class="badge-wrap">
              <div class="pay-note">주휴수당 포함</div>
              <div class="pay-note">3명 합산</div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ── 주요 기능 ───────────────────────────────────────── -->
<section id="features" class="landing-section alt">
  <div class="container">
    <div class="section-eyebrow">FEATURES</div>
    <h2 class="section-title">필요한 기능을 한곳에</h2>
    <p class="section-lead">출퇴근 기록부터 급여명세서까지, 알바 관리에 필요한 모든 것을 담았습니다.</p>
    <div class="row g-4">
      <?php foreach ($features as $f): ?>
      <div class="col-md-6 col-lg-4">
        <div class="feature-card">
          <div class="feature-icon"><i class="bi <?= h($f[0]) ?>"></i></div>
          <h5><?= h($f[1]) ?></h5>
          <p><?= h($f[2]) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── 제품 미리보기 ────────────────────────────────────── -->
<section id="product" class="landing-section">
  <div class="container">
    <div class="section-eyebrow">PRODUCT</div>
    <h2 class="section-title">한 눈에 파악하는 사장님 대시보드</h2>
    <p class="section-lead">출퇴근 현황, 리스크 알림, 직원 관리, 인건비 차트까지 한 화면에서 바로 확인하세요.</p>
    <div class="product-window">
      <div class="product-chrome">
        <div class="chrome-dots">
          <span class="chrome-dot chrome-red"></span>
          <span class="chrome-dot chrome-yellow"></span>
          <span class="chrome-dot chrome-green"></span>
        </div>
        <div class="chrome-url">페이클락 · 사장님 대시보드</div>
        <div style="width:52px"></div>
      </div>
      <div class="product-viewport">
        <iframe
          class="product-frame"
          src="<?= BASE_URL ?>preview/dashboard.html"
          scrolling="no"
          loading="lazy"
          title="페이클락 대시보드 미리보기"
        ></iframe>
      </div>
    </div>
  </div>
</section>

<!-- ── 사용자별 안내 (롤 스플릿) ─────────────────────── -->
<div class="role-split">
  <div class="role-panel owner">
    <div class="role-label">사장님이라면</div>
    <h3>사업장을 만들고<br>알바를 초대하세요</h3>
    <p class="role-desc">
      사업장 등록 → 알바 초대 → 자동 급여 계산.<br>
      모든 과정이 5분 안에 완료됩니다.
    </p>
    <ul class="role-list">
      <?php foreach ($ownerItems as $it): ?>
      <li><?= h($it) ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="<?= url('signup', 'owner') ?>" class="btn-role-owner">
      <i class="bi bi-shop"></i>사업장 만들기
    </a>
  </div>
  <div class="role-panel employee">
    <div class="role-label">알바생이라면</div>
    <h3>초대 링크로 가입하고<br>급여명세서를 확인하세요</h3>
    <p class="role-desc">
      사장님이 보낸 초대 링크 → 가입 → 급여 확인.<br>
      내 근무시간과 급여를 언제든 조회할 수 있어요.
    </p>
    <ul class="role-list">
      <?php foreach ($employeeItems as $it): ?>
      <li><?= h($it) ?></li>
      <?php endforeach; ?>
    </ul>
    <a href="<?= url('signup', 'employee') ?>" class="btn-role-employee">
      <i class="bi bi-person-badge"></i>알바로 시작하기
    </a>
  </div>
</div>

<!-- ── 이용 방법 ──────────────────────────────────────── -->
<section id="how" class="landing-section">
  <div class="container">
    <div class="section-eyebrow">HOW IT WORKS</div>
    <h2 class="section-title">딱 4단계면 시작됩니다</h2>
    <p class="section-lead">복잡한 설정 없이 누구나 쉽게 시작할 수 있어요.</p>
    <div class="step-grid">
      <?php foreach ($steps as $i => [$icon, $title, $desc]): ?>
      <div class="step-item">
        <div class="step-num-wrap">
          <div class="step-num"><?= $i + 1 ?></div>
          <?php if ($i < count($steps) - 1): ?>
          <div class="step-connector"></div>
          <?php endif; ?>
        </div>
        <h5><?= h($title) ?></h5>
        <p><?= nl2br(h($desc)) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ── 요금제 ─────────────────────────────────────────── -->
<section id="pricing" class="landing-section alt">
  <div class="container">
    <div class="section-eyebrow">PRICING</div>
    <h2 class="section-title">요금제</h2>
    <p class="section-lead">베타 기간 동안 핵심 기능을 무료로 사용해 보세요.</p>
    <div class="row g-4 justify-content-center">
      <div class="col-md-6 col-lg-4">
        <div class="plan-card featured">
          <div class="plan-name">FREE</div>
          <div class="plan-badge">베타 무료</div>
          <div class="plan-price">₩0</div>
          <div class="plan-price-note">지금 바로 시작</div>
          <div class="plan-divider"></div>
          <ul>
            <li>직원 3명까지</li>
            <li>QR 출퇴근</li>
            <li>주휴수당 자동 계산</li>
            <li>월 급여 계산</li>
            <li>급여명세서 발급</li>
          </ul>
          <a href="<?= url('signup', 'owner') ?>" class="btn-cta w-100 text-center" style="border-radius:10px;padding:.75rem">
            무료로 시작하기
          </a>
        </div>
      </div>
      <div class="col-md-6 col-lg-4">
        <div class="plan-card">
          <div class="plan-name">STARTER</div>
          <div class="plan-badge muted">준비 중</div>
          <div class="plan-price" style="color:#9CA3AF">—</div>
          <div class="plan-price-note">출시 예정</div>
          <div class="plan-divider"></div>
          <ul>
            <li>직원 10명까지</li>
            <li>근무 수정 요청</li>
            <li>노동법 리스크 알림</li>
            <li>근로계약서 발급</li>
            <li>CSV 내보내기</li>
          </ul>
          <button class="btn btn-outline-secondary w-100 rounded-3" disabled>준비 중</button>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── FAQ ───────────────────────────────────────────── -->
<section id="faq" class="landing-section">
  <div class="container">
    <div class="section-eyebrow">FAQ</div>
    <h2 class="section-title">자주 묻는 질문</h2>
    <div class="row justify-content-center mt-4">
      <div class="col-lg-7">
        <div class="accordion faq-accordion" id="faqAccordion">
          <?php foreach ($faqs as $i => $faq): ?>
          <div class="accordion-item">
            <h2 class="accordion-header">
              <button class="accordion-button <?= $i === 0 ? '' : 'collapsed' ?>" type="button"
                      data-bs-toggle="collapse" data-bs-target="#faq<?= $i ?>">
                <?= h($faq[0]) ?>
              </button>
            </h2>
            <div id="faq<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>"
                 data-bs-parent="#faqAccordion">
              <div class="accordion-body"><?= h($faq[1]) ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ── CTA 배너 ──────────────────────────────────────── -->
<section class="cta-banner">
  <div class="container">
    <h2>지금 바로 무료로 시작하세요</h2>
    <p>신용카드 없이 무료 시작 &middot; 언제든 취소 가능</p>
    <a href="<?= url('signup', 'owner') ?>" class="btn-cta-white">
      <i class="bi bi-shop"></i>사업장 만들기
    </a>
  </div>
</section>
