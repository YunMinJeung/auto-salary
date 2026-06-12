<?php
// $fd = form_data array (decoded from JSON)
// $contract = DB row
// $store = stores row

$workDays = array_filter(explode(',', $fd['work_days'] ?? ''));
$sec = 0; // 동적 섹션 번호
function secNum(int &$n, string $title): string {
    return '<div class="sec">' . (++$n) . '. ' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</div>';
}

function _h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function _blank($v, int $minW = 80): string {
    $text = trim((string)($v ?? ''));
    $style = "border-bottom:1px solid #000;display:inline-block;min-width:{$minW}px;padding:0 4px";
    if ($text !== '') return "<span style=\"{$style}\">" . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
    return "<span style=\"{$style}\">&nbsp;</span>";
}
function _ymd(string $d): string {
    if (!$d) return '';
    $p = explode('-', $d);
    return count($p) === 3 ? $p[0] . '년 ' . (int)$p[1] . '월 ' . (int)$p[2] . '일' : $d;
}
function _insFlag(int $v): string { return $v ? '□ 미가입 &nbsp;■ 가입' : '■ 미가입 &nbsp;□ 가입'; }
?>

<style>
.contract-wrap { font-family:'맑은 고딕','Apple SD Gothic Neo',sans-serif; font-size:11.5pt; color:#000; }
.contract-wrap h2 { font-size:20pt; text-align:center; letter-spacing:.25em; border-bottom:3px double #000; padding-bottom:8px; margin-bottom:8px; }
.contract-wrap .sub { text-align:center; font-size:9.5pt; margin-bottom:20px; color:#444; }
.contract-wrap table.tbl { width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10.5pt; }
.contract-wrap table.tbl th, .contract-wrap table.tbl td { border:1px solid #666; padding:5px 7px; vertical-align:middle; }
.contract-wrap table.tbl th { background:#f0f0f0; white-space:nowrap; width:27%; font-weight:600; }
.contract-wrap .sec { font-weight:700; font-size:10.5pt; border-left:4px solid #003844; padding:3px 8px; background:#f8f8f8; margin:14px 0 6px; }
.contract-wrap .clause { margin-bottom:8px; line-height:1.7; }
.contract-wrap .clause ul { margin-left:1.5em; }
.contract-wrap .sign-area { display:flex; justify-content:space-between; gap:20px; margin-top:28px; }
.contract-wrap .sign-box { flex:1; border:1px solid #888; padding:12px; text-align:center; }
.contract-wrap .sign-box .slbl { font-size:9.5pt; color:#555; margin-bottom:6px; }
.contract-wrap .sign-box .sval { font-size:10.5pt; font-weight:600; }
.contract-wrap .sign-box .sline { border-bottom:1px solid #000; height:36px; margin-top:12px; }
</style>

<div class="contract-wrap">

  <h2>근 로 계 약 서</h2>
  <p class="sub">「근로기준법」 제17조에 따라 아래와 같이 근로계약을 체결합니다.</p>

  <?= secNum($sec, '당사자') ?>
  <table class="tbl">
    <tr>
      <th>사업장명 (상호)</th>
      <td><?= _blank($fd['business_name'] ?? '') ?></td>
      <th>사업자등록번호</th>
      <td><?= _blank($fd['business_registration_number'] ?? '') ?></td>
    </tr>
    <tr>
      <th>사업주명</th>
      <td><?= _blank($fd['employer_name'] ?? '') ?></td>
      <th>사업주 연락처</th>
      <td><?= _blank($fd['employer_phone'] ?? '') ?></td>
    </tr>
    <tr><th>사업장 주소</th><td colspan="3"><?= _blank($fd['employer_address'] ?? '', 250) ?></td></tr>
    <tr><th>작성일</th><td colspan="3"><?= _ymd($fd['issue_date'] ?? '') ?></td></tr>
    <tr><th>근로자 성명</th><td><?= _blank($fd['employee_name'] ?? '') ?></td><th>연락처</th><td><?= _blank($fd['employee_phone'] ?? '') ?></td></tr>
    <tr><th>근로자 주소</th><td colspan="3"><?= _blank($fd['employee_address'] ?? '', 250) ?></td></tr>
  </table>

  <?= secNum($sec, '계약 기간') ?>
  <div class="clause">
    계약 시작일: <?= _ymd($fd['contract_start_date'] ?? '') ?>
    &nbsp;&nbsp;&nbsp;
    계약 종료일:
    <?php if (!empty($fd['contract_end_date'])): ?>
      <?= _ymd($fd['contract_end_date']) ?>
    <?php else: ?>
      기간의 정함이 없음
    <?php endif; ?>
  </div>

  <?= secNum($sec, '근무 장소 및 업무 내용') ?>
  <table class="tbl">
    <tr><th>근무 장소</th><td><?= _blank($fd['work_location'] ?? '') ?></td></tr>
    <tr><th>업무 내용</th><td><?= _blank($fd['job_duties'] ?? '') ?></td></tr>
  </table>

  <?= secNum($sec, '소정근로시간') ?>
  <div class="clause">
    <ul>
      <li>주 소정근로시간: <?= _blank($fd['weekly_scheduled_hours'] ?? '') ?> 시간 &nbsp;/&nbsp;
          주 소정근로일: <?= _blank($fd['weekly_scheduled_days'] ?? '') ?> 일</li>
      <li>근무 요일: <?= $workDays ? implode(', ', array_map(fn($d) => _h($d) . '요일', $workDays)) : _blank('', 120) ?></li>
      <li>근무 시간: <?= _blank($fd['work_start_time'] ?? '') ?> ~ <?= _blank($fd['work_end_time'] ?? '') ?>
          (휴게 <?= _blank((int)($fd['break_minutes'] ?? 0)) ?> 분)</li>
    </ul>
  </div>

  <?= secNum($sec, '임금') ?>
  <table class="tbl">
    <tr><th>시급</th><td><strong><?= _blank(number_format((int)($fd['hourly_wage'] ?? 0)), 100) ?> 원</strong></td></tr>
    <tr>
      <th>임금 지급일 / 방법</th>
      <td>매월 <?= _blank($fd['pay_day'] ?? '') ?> 일 &nbsp;/&nbsp; <?= _blank($fd['pay_method'] ?? '') ?></td>
    </tr>
  </table>
  <div class="clause" style="font-size:9.5pt;color:#555">
    ※ 주휴수당, 연장·야간·휴일 가산수당은 「근로기준법」에 따라 별도 산정하여 지급합니다.
  </div>

  <?php if (!empty($fd['include_annual_leave'])): ?>
  <?= secNum($sec, '연차 유급휴가') ?>
  <div class="clause">「근로기준법」 제60조에 따라 부여합니다 (1년 80% 이상 출근 시 15일, 미만 시 매월 1일).</div>
  <?php endif; ?>

  <?= secNum($sec, '주휴일') ?>
  <div class="clause">
    주휴일: <?= _blank($fd['weekly_holiday_day'] ?? '일요일') ?>
    <?php if (!empty($fd['other_holidays'])): ?>
    &nbsp;| 그 외 휴일: <?= _h($fd['other_holidays']) ?>
    <?php endif; ?>
  </div>

  <?= secNum($sec, '사회보험 가입') ?>
  <table class="tbl">
    <tr>
      <th>국민연금</th><td><?= _insFlag((int)($fd['insurance_pension'] ?? 0)) ?></td>
      <th>건강보험</th><td><?= _insFlag((int)($fd['insurance_health'] ?? 0)) ?></td>
    </tr>
    <tr>
      <th>고용보험</th><td><?= _insFlag((int)($fd['insurance_employment'] ?? 0)) ?></td>
      <th>산재보험</th><td>■ 가입 (의무 적용)</td>
    </tr>
  </table>

  <?= secNum($sec, '기타') ?>
  <div class="clause">이 계약에 명시되지 않은 사항은 「근로기준법」, 「최저임금법」 등 관계 법령에 따릅니다.</div>

  <div class="sign-area">
    <div class="sign-box">
      <div class="slbl">사업주</div>
      <div class="sval"><?= _h($fd['employer_name'] ?? '') ?></div>
      <div style="font-size:9.5pt;color:#555"><?= _h($fd['business_name'] ?? '') ?></div>
      <div class="sline"></div>
      <div style="font-size:9.5pt;color:#555;margin-top:4px">서명 (인)</div>
    </div>
    <div class="sign-box">
      <div class="slbl">근로자</div>
      <div class="sval"><?= _h($fd['employee_name'] ?? '') ?></div>
      <div style="font-size:9.5pt;color:#555">&nbsp;</div>
      <div class="sline"></div>
      <div style="font-size:9.5pt;color:#555;margin-top:4px">서명 (인)</div>
    </div>
  </div>

  <div style="text-align:center;margin-top:16px;font-size:9.5pt;color:#888">
    작성일: <?= _ymd($fd['issue_date'] ?? date('Y-m-d')) ?>
    &nbsp;&nbsp;|&nbsp;&nbsp;
    이 계약서는 사업주와 근로자 각 1부씩 보관합니다.
  </div>

</div>
