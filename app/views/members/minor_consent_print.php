<?php
// $fd = form_data decoded array
// $consent = DB row

function _ch($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
function _cblank($v, int $minW = 100): string {
    $text = trim((string)($v ?? ''));
    $style = "border-bottom:1px solid #000;display:inline-block;min-width:{$minW}px;padding:0 4px";
    if ($text !== '') return "<span style=\"{$style}\">" . htmlspecialchars($text, ENT_QUOTES, 'UTF-8') . '</span>';
    return "<span style=\"{$style}\">&nbsp;</span>";
}
function _cymd(string $d): string {
    if (!$d) return '';
    $p = explode('-', $d);
    return count($p) === 3 ? $p[0] . '년 ' . (int)$p[1] . '월 ' . (int)$p[2] . '일' : $d;
}
?>

<style>
.consent-wrap { font-family:'맑은 고딕','Apple SD Gothic Neo',sans-serif; font-size:11.5pt; color:#000; }
.consent-wrap h2 { font-size:19pt; text-align:center; letter-spacing:.2em; border-bottom:3px double #000; padding-bottom:8px; margin-bottom:8px; }
.consent-wrap .sub { text-align:center; font-size:9.5pt; margin-bottom:20px; color:#444; }
.consent-wrap table.tbl { width:100%; border-collapse:collapse; margin-bottom:14px; font-size:10.5pt; }
.consent-wrap table.tbl th, .consent-wrap table.tbl td { border:1px solid #666; padding:5px 7px; vertical-align:middle; }
.consent-wrap table.tbl th { background:#f0f0f0; white-space:nowrap; width:27%; font-weight:600; }
.consent-wrap .sec { font-weight:700; font-size:10.5pt; border-left:4px solid #003844; padding:3px 8px; background:#f8f8f8; margin:14px 0 6px; }
.consent-wrap .consent-box { border:2px solid #003844; padding:14px; margin:16px 0; line-height:1.8; }
.consent-wrap .sign-area { display:flex; justify-content:space-between; gap:16px; margin-top:28px; }
.consent-wrap .sign-box { flex:1; border:1px solid #888; padding:12px; text-align:center; }
.consent-wrap .sign-box .slbl { font-size:9.5pt; color:#555; margin-bottom:6px; }
.consent-wrap .sign-box .sval { font-size:10.5pt; font-weight:600; }
.consent-wrap .sign-box .sline { border-bottom:1px solid #000; height:36px; margin-top:12px; }
.consent-wrap .law-note { font-size:8.5pt; color:#666; margin-top:12px; }
</style>

<div class="consent-wrap">

  <h2>친권자(후견인) 동의서</h2>
  <p class="sub">「근로기준법」 제66조에 따른 연소근로자 근로계약 관련 동의서</p>

  <div class="sec">1. 근로자 정보</div>
  <table class="tbl">
    <tr><th>성명</th><td><?= _cblank($fd['employee_name'] ?? '') ?></td><th>생년월일</th><td><?= _cymd($fd['date_of_birth'] ?? '') ?></td></tr>
    <tr><th>주소</th><td colspan="3"><?= _cblank($fd['employee_address'] ?? '', 240) ?></td></tr>
  </table>

  <div class="sec">2. 사업장 및 근로 조건</div>
  <table class="tbl">
    <tr><th>사업장명</th><td><?= _cblank($fd['business_name'] ?? '') ?></td><th>사업주명</th><td><?= _cblank($fd['employer_name'] ?? '') ?></td></tr>
    <tr><th>근무 장소</th><td><?= _cblank($fd['work_location'] ?? '') ?></td><th>업무 내용</th><td><?= _cblank($fd['job_duties'] ?? '') ?></td></tr>
    <tr>
      <th>계약 기간</th>
      <td colspan="3">
        <?= _cymd($fd['contract_start_date'] ?? '') ?>
        ~
        <?= !empty($fd['contract_end_date']) ? _cymd($fd['contract_end_date']) : '기간의 정함이 없음' ?>
      </td>
    </tr>
    <tr>
      <th>근무 시간</th>
      <td><?= _ch($fd['work_start_time'] ?? '') ?> ~ <?= _ch($fd['work_end_time'] ?? '') ?></td>
      <th>시급</th>
      <td><?= $fd['hourly_wage'] ? number_format((int)$fd['hourly_wage']) . ' 원' : '&nbsp;' ?></td>
    </tr>
  </table>

  <div class="sec">3. 친권자 / 후견인</div>
  <table class="tbl">
    <tr><th>성명</th><td><?= _cblank($fd['guardian_name'] ?? '') ?></td><th>관계</th><td><?= _cblank($fd['guardian_relation'] ?? '') ?></td></tr>
    <tr><th>연락처</th><td><?= _cblank($fd['guardian_phone'] ?? '') ?></td><th>주소</th><td><?= _cblank($fd['guardian_address'] ?? '', 160) ?></td></tr>
  </table>

  <div class="sec">4. 동의 사항</div>
  <div class="consent-box">
    <p>
      본인은 위 근로자의 친권자(후견인)로서 위 근로조건에 따라 해당 사업장에서 근로하는 것에 동의합니다.
    </p>
    <p style="margin-top:10px;font-size:10pt;color:#444">
      ※ 「근로기준법」 제67조에 따라 친권자 또는 후견인은 미성년자의 근로계약을 대리할 수 없으며,
      미성년자 본인이 직접 근로계약을 체결해야 합니다. 본 동의서는 해당 근로계약에 대한
      친권자(후견인)의 동의만을 나타냅니다.
    </p>
  </div>

  <div class="sign-area">
    <div class="sign-box">
      <div class="slbl">친권자 / 후견인</div>
      <div class="sval"><?= _ch($fd['guardian_name'] ?? '') ?></div>
      <div style="font-size:9.5pt;color:#555"><?= _ch($fd['guardian_relation'] ?? '') ?></div>
      <div class="sline"></div>
      <div style="font-size:9.5pt;color:#555;margin-top:4px">서명 (인)</div>
    </div>
    <div class="sign-box">
      <div class="slbl">근로자 (본인)</div>
      <div class="sval"><?= _ch($fd['employee_name'] ?? '') ?></div>
      <div style="font-size:9.5pt;color:#555">
        <?php if ($consent['age_at_signing']): ?>
        만 <?= (int)$consent['age_at_signing'] ?>세
        <?php else: ?>&nbsp;<?php endif; ?>
      </div>
      <div class="sline"></div>
      <div style="font-size:9.5pt;color:#555;margin-top:4px">서명 (인)</div>
    </div>
    <div class="sign-box">
      <div class="slbl">사업주</div>
      <div class="sval"><?= _ch($fd['employer_name'] ?? '') ?></div>
      <div style="font-size:9.5pt;color:#555"><?= _ch($fd['business_name'] ?? '') ?></div>
      <div class="sline"></div>
      <div style="font-size:9.5pt;color:#555;margin-top:4px">서명 (인)</div>
    </div>
  </div>

  <div style="text-align:center;margin-top:16px;font-size:9.5pt;color:#888">
    작성일: <?= _cymd($fd['issue_date'] ?? date('Y-m-d')) ?>
  </div>

  <p class="law-note">
    ※ 본 동의서는 사업장과 근로자 각 1부씩 보관합니다.<br>
    ※ 「근로기준법」 제66조: 사용자는 18세 미만인 사람에 대하여는 연령 증명서와 친권자 또는 후견인의 동의서를 사업장에 갖추어 두어야 합니다.
  </p>

</div>
