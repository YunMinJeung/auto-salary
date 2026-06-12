<?php
$bizName       = h($store['store_name'] ?? $settings['business_name'] ?? '');
$startDate     = $member['employment_start_date'] ?? '';
$endDate       = $member['employment_end_date']   ?? '';
$weeklyHours   = (float)($member['weekly_scheduled_hours'] ?? 40);
$weeklyDays    = (int)($member['weekly_scheduled_days']    ?? 5);
$monthlyHours  = round($weeklyHours * 4.345, 1);
$hourlyWage    = (int)($member['hourly_wage'] ?? 0);
$monthlyRef    = (int) round($hourlyWage * $monthlyHours);
$weeklyHoliday = (bool)($member['weekly_holiday_enabled'] ?? true);
$issueDate     = date('Y년 m월 d일');

$insStatus = $insuranceSetting['user_selected_status'] ?? 'needs_review';
$insStatusLabel = ['enrolled' => '가입', 'not_enrolled' => '미가입', 'needs_review' => '확인 필요'];

function insLabel(string $key, array $insCheck, array $insStatus): string {
    // 판단 결과 기준 표시
    $status = $insCheck[$key] ?? 'needs_review';
    return match($status) {
        'likely_required' => '가입 대상',
        'possibly_exempt' => '가입 제외 가능',
        default           => '확인 필요',
    };
}
?>

<!-- ── 제목 ───────────────────────────────────────── -->
<div class="slip-head text-center mb-4">
  <div class="slip-title">근 로 계 약 서</div>
  <div class="small text-muted mt-1">표준근로계약서 (시간제 근로자)</div>
</div>

<p class="small text-muted mb-4">
  아래와 같이 근로계약을 체결하고 이를 성실히 이행할 것을 확약한다.
</p>

<!-- ── 당사자 정보 ─────────────────────────────────── -->
<table class="slip-table mb-4">
  <tbody>
    <tr>
      <th style="width:140px">사업주 (갑)</th>
      <td>
        상호: <?= $bizName ?> &nbsp;&nbsp;
        대표자: <span style="display:inline-block;min-width:80px;border-bottom:1px solid #999">&nbsp;</span>
      </td>
    </tr>
    <tr>
      <th>소재지</th>
      <td><span style="display:inline-block;min-width:300px;border-bottom:1px solid #999">&nbsp;</span></td>
    </tr>
    <tr>
      <th>근로자 (을)</th>
      <td>
        성명: <strong><?= h($member['name']) ?></strong> &nbsp;&nbsp;
        연락처: <?= h($member['phone'] ?: '—') ?>
      </td>
    </tr>
  </tbody>
</table>

<!-- ── 제1조 근로계약기간 ──────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제1조 근로계약기간</div>
  <div class="ps-3 small">
    <?php if ($startDate): ?>
    <?= date('Y년 m월 d일', strtotime($startDate)) ?> 부터
    <?php if ($endDate): ?>
    <?= date('Y년 m월 d일', strtotime($endDate)) ?> 까지
    <?php else: ?>
    기간의 정함이 없음 (상시 고용)
    <?php endif; ?>
    <?php else: ?>
    <span style="display:inline-block;min-width:200px;border-bottom:1px solid #999">&nbsp;</span> 부터
    <span style="display:inline-block;min-width:200px;border-bottom:1px solid #999">&nbsp;</span> 까지
    <?php endif; ?>
  </div>
</div>

<!-- ── 제2조 근무장소 ──────────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제2조 근무 장소</div>
  <div class="ps-3 small">
    <?= $bizName ?> &nbsp;
    <span style="display:inline-block;min-width:220px;border-bottom:1px solid #999">&nbsp;</span>
  </div>
</div>

<!-- ── 제3조 업무내용 ──────────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제3조 업무 내용</div>
  <div class="ps-3 small">
    <span style="display:inline-block;min-width:300px;border-bottom:1px solid #999">&nbsp;</span>
  </div>
</div>

<!-- ── 제4조 소정근로시간 ──────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제4조 소정근로시간</div>
  <div class="ps-3 small">
    <table class="slip-table mb-2" style="max-width:500px">
      <tbody>
        <tr>
          <th style="width:160px">주 소정근로시간</th>
          <td><strong><?= number_format($weeklyHours, 1) ?>시간</strong> / 주</td>
        </tr>
        <tr>
          <th>주 소정근로일</th>
          <td><strong><?= $weeklyDays ?>일</strong> / 주</td>
        </tr>
        <tr>
          <th>월 환산 근로시간</th>
          <td><?= number_format($monthlyHours, 1) ?>시간
            <span class="text-muted">(주 <?= number_format($weeklyHours, 1) ?>h × 4.345주)</span>
          </td>
        </tr>
        <tr>
          <th>근무 시간대</th>
          <td>
            <span style="display:inline-block;min-width:60px;border-bottom:1px solid #999">&nbsp;</span>
            시 ~
            <span style="display:inline-block;min-width:60px;border-bottom:1px solid #999">&nbsp;</span>
            시 (휴게
            <span style="display:inline-block;min-width:40px;border-bottom:1px solid #999">&nbsp;</span>
            분 포함)
          </td>
        </tr>
      </tbody>
    </table>
    <?php if ($weeklyHours < 15): ?>
    <div class="text-muted" style="font-size:.8rem">
      ※ 주 소정근로시간이 15시간 미만으로 주휴수당·4대보험 일부 적용 제외 가능
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── 제5조 휴일 ──────────────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제5조 휴일</div>
  <div class="ps-3 small">
    <ul class="mb-0" style="padding-left:1.2rem">
      <li>근로기준법 제55조에 따라 매주 1회 이상의 유급 휴일을 부여한다.</li>
      <?php if ($weeklyHoliday && $weeklyHours >= 15): ?>
      <li>주 소정근로시간이 15시간 이상이므로 <strong>주휴수당을 지급</strong>한다.
        <span class="text-muted">(주휴수당 = 시급 × 주휴시간, 완전 개근 요건 충족 시)</span>
      </li>
      <?php elseif ($weeklyHours < 15): ?>
      <li class="text-muted">주 소정근로시간이 15시간 미만으로 주휴수당이 발생하지 않을 수 있다.</li>
      <?php endif; ?>
    </ul>
  </div>
</div>

<!-- ── 제6조 임금 ──────────────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제6조 임금</div>
  <div class="ps-3 small">
    <table class="slip-table mb-2" style="max-width:500px">
      <tbody>
        <tr>
          <th style="width:160px">시급</th>
          <td><strong><?= number_format($hourlyWage) ?>원</strong></td>
        </tr>
        <tr>
          <th>월 기본급 (참고)</th>
          <td><?= number_format($monthlyRef) ?>원
            <span class="text-muted">(시급 × 월 환산 <?= number_format($monthlyHours, 1) ?>h)</span>
          </td>
        </tr>
        <tr>
          <th>지급일</th>
          <td>매월
            <span style="display:inline-block;min-width:30px;border-bottom:1px solid #999">&nbsp;</span>
            일
          </td>
        </tr>
        <tr>
          <th>지급 방법</th>
          <td>근로자 명의 계좌 입금</td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- ── 제7조 연차 유급 휴가 ──────────────────────────── -->
<div class="mb-3">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제7조 연차 유급 휴가</div>
  <div class="ps-3 small">
    근로기준법 제60조에 따른다.
    <?php if ($weeklyHours < 15): ?>
    <span class="text-muted">(주 소정근로시간 15시간 미만 근로자는 연차 미발생)</span>
    <?php endif; ?>
  </div>
</div>

<!-- ── 제8조 사회보험 ──────────────────────────────── -->
<div class="mb-4">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제8조 사회보험 적용</div>
  <div class="ps-3 small">
    <table class="slip-table" style="max-width:480px">
      <thead>
        <tr>
          <th>보험 종류</th>
          <th class="text-center">관리 상태</th>
          <th class="text-center">시스템 판단</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $insRows = [
            ['key' => 'national_pension',     'name' => '국민연금',    'rate' => '4.75%'],
            ['key' => 'health_insurance',     'name' => '건강보험',    'rate' => '3.595%'],
            ['key' => 'employment_insurance', 'name' => '고용보험',    'rate' => '0.9%'],
            ['key' => 'industrial_accident',  'name' => '산재보험',    'rate' => '사용자 부담'],
        ];
        foreach ($insRows as $r):
            $status = $insCheck[$r['key']] ?? 'needs_review';
            $judgmentLabel = match($status) {
                'likely_required' => '<span class="text-danger fw-semibold">가입 대상 가능성 높음</span>',
                'possibly_exempt' => '<span class="text-secondary">제외 가능성 있음</span>',
                'required'        => '<span class="text-info">사용자 전액 부담</span>',
                default           => '<span class="text-muted">확인 필요</span>',
            };
        ?>
        <tr>
          <td><?= $r['name'] ?> <span class="text-muted">(<?= $r['rate'] ?>)</span></td>
          <td class="text-center">
            <?php if ($r['key'] === 'industrial_accident'): ?>
            사용자 부담
            <?php elseif ($insStatus === 'enrolled'): ?>
            <span class="badge bg-success-subtle text-success border border-success-subtle">가입</span>
            <?php elseif ($insStatus === 'not_enrolled'): ?>
            <span class="badge bg-warning-subtle text-warning-emphasis border">미가입</span>
            <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary border">확인 필요</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?= $judgmentLabel ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="text-muted mt-1" style="font-size:.78rem">
      ※ 시스템 판단은 계약상 소정근로시간(<?= number_format($monthlyHours, 1) ?>시간/월) 기준이며,
      정확한 가입 의무는 관할 공단에 확인하세요.
    </div>
  </div>
</div>

<!-- ── 제9조 기타 ──────────────────────────────────── -->
<div class="mb-4">
  <div class="fw-bold mb-1" style="color:var(--c-dark)">제9조 기타</div>
  <div class="ps-3 small">
    이 계약에서 정하지 않은 사항은 근로기준법 등 관련 법령에 따른다.
  </div>
</div>

<!-- ── 서명 ───────────────────────────────────────── -->
<div class="sign-box">
  <div class="text-center mb-3 fw-semibold" style="color:var(--c-dark)"><?= $issueDate ?> 작성</div>
  <div class="row text-center">
    <div class="col-6">
      <div class="small text-muted mb-1">사업주 (갑)</div>
      <div>사업장명: <?= $bizName ?></div>
      <div class="mt-1">대표자: <span style="display:inline-block;min-width:80px;border-bottom:1px solid #999">&nbsp;</span></div>
      <div class="small text-muted mt-1">(서명 또는 날인)</div>
    </div>
    <div class="col-6">
      <div class="small text-muted mb-1">근로자 (을)</div>
      <div>성명: <strong><?= h($member['name']) ?></strong></div>
      <div class="mt-1">서명: <span style="display:inline-block;min-width:100px;border-bottom:1px solid #999">&nbsp;</span></div>
      <div class="small text-muted mt-1">(서명 또는 날인)</div>
    </div>
  </div>
</div>

<!-- ── 법적 고지 ──────────────────────────────────── -->
<div class="notice">
  본 계약서는 근로기준법 제17조에 따라 근로계약 체결 시 교부됩니다.
  임금, 근로시간, 휴일·휴가 등 주요 근로조건 변경 시 새로운 계약서를 작성해야 합니다.
  2026년 최저시급: <?= number_format(\MinimumWage::currentHourlyWage()) ?>원 (시간급).
</div>
