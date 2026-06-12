<?php
$fd = $formData; // alias for brevity
$ins = $insuranceSetting;

// Pre-fill from member record
$defWage  = $member['hourly_wage']               ?? '';
$defWH    = $member['weekly_scheduled_hours']    ?? '';
$defWD    = $member['weekly_scheduled_days']     ?? '';
$defStart = $member['employment_start_date']     ?? '';
$defBiz   = $store['store_name']                 ?? '';

function fv(array $fd, string $key, string $default = ''): string {
    return h($fd[$key] ?? $default);
}
function fvInt(array $fd, string $key, $default = ''): string {
    $v = $fd[$key] ?? $default;
    return $v !== '' ? h((string)$v) : '';
}
function timePicker(string $id, string $name, string $val = ''): void {
    $ampm = 'am'; $selH = 9; $selM = 0;
    if (preg_match('/^(\d{2}):(\d{2})/', $val, $p)) {
        $hh = (int)$p[1]; $mm = (int)$p[2];
        $ampm = $hh >= 12 ? 'pm' : 'am';
        $selH = $hh % 12; if ($selH === 0) $selH = 12;
        $selM = (int)round($mm / 10) * 10 % 60;
    }
    ?>
    <div class="d-flex align-items-center gap-1" id="tp_<?= $id ?>">
      <select class="form-select form-select-sm tp-ampm" style="width:76px"
              onchange="tpSync('<?= $id ?>')">
        <option value="am" <?= $ampm==='am'?'selected':'' ?>>오전</option>
        <option value="pm" <?= $ampm==='pm'?'selected':'' ?>>오후</option>
      </select>
      <select class="form-select form-select-sm tp-hour" style="width:60px"
              onchange="tpSync('<?= $id ?>')">
        <?php foreach ([12,1,2,3,4,5,6,7,8,9,10,11] as $hr): ?>
        <option value="<?= $hr ?>" <?= $selH===$hr?'selected':'' ?>><?= $hr ?></option>
        <?php endforeach; ?>
      </select>
      <span class="fw-semibold">:</span>
      <select class="form-select form-select-sm tp-min" style="width:66px"
              onchange="tpSync('<?= $id ?>')">
        <?php foreach ([0,10,20,30,40,50] as $mn): ?>
        <option value="<?= $mn ?>" <?= $selM===$mn?'selected':'' ?>><?= str_pad($mn,2,'0',STR_PAD_LEFT) ?></option>
        <?php endforeach; ?>
      </select>
      <input type="hidden" name="<?= $name ?>" id="tp_<?= $id ?>_val" value="<?= h($val) ?>">
    </div>
    <?php
}
$workDaysArr = array_filter(explode(',', $fd['work_days'] ?? ''));
$dayOpts = ['월','화','수','목','금','토','일'];
?>
<div class="container py-4" style="max-width:820px">

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h4 class="mb-0">근로계약서 작성</h4>
      <p class="text-muted small mb-0 mt-1">
        <?= h($member['name']) ?> · 시급 <?= number_format((int)($member['hourly_wage'] ?? 0)) ?>원
      </p>
    </div>
    <a href="<?= url('members','edit',['id'=>$member['id']]) ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>직원 정보로 돌아가기
    </a>
  </div>

  <?php if ($errors): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <?php if ($history): ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-light fw-semibold small">이전 계약서 이력</div>
    <div class="card-body p-0">
      <table class="table table-sm mb-0">
        <thead class="table-light">
          <tr><th>계약 시작일</th><th>계약 종료일</th><th>작성일시</th><th></th></tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h2): ?>
          <tr>
            <td><?= h($h2['contract_start_date'] ?? '') ?></td>
            <td><?= $h2['contract_end_date'] ? h($h2['contract_end_date']) : '기간의 정함 없음' ?></td>
            <td><?= h(substr($h2['created_at'], 0, 16)) ?></td>
            <td>
              <a href="<?= url('members','contract_view',['id'=>$member['id'],'contract_id'=>$h2['id']]) ?>"
                 class="btn btn-xs btn-outline-primary btn-sm py-0 px-2" target="_blank">
                <i class="bi bi-printer me-1"></i>인쇄
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <form method="POST">
    <?= csrf_field() ?>

    <!-- 사업장 정보 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-dark);color:#fff">
        <i class="bi bi-building me-2"></i>사업장 정보
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">사업장명 <span class="text-muted fw-normal">(상호)</span></label>
            <input type="text" name="business_name" class="form-control"
                   value="<?= fv($fd,'business_name',$defBiz) ?>" placeholder="예: 카페하루">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">사업자등록번호</label>
            <input type="text" name="business_registration_number" class="form-control"
                   value="<?= fv($fd,'business_registration_number') ?>" placeholder="000-00-00000">
            <div class="form-text">분쟁 시 사용자 특정을 위해 기재 권장</div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">사업주명 <span class="text-danger">*</span></label>
            <input type="text" name="employer_name" class="form-control"
                   value="<?= fv($fd,'employer_name') ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">사업주 연락처</label>
            <input type="text" name="employer_phone" class="form-control"
                   value="<?= fv($fd,'employer_phone') ?>" placeholder="010-0000-0000">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">사업장 주소</label>
            <input type="text" name="employer_address" class="form-control"
                   value="<?= fv($fd,'employer_address') ?>" placeholder="예: 서울특별시 강남구 ...">
          </div>
        </div>
      </div>
    </div>

    <!-- 근로자 정보 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-teal);color:#fff">
        <i class="bi bi-person me-2"></i>근로자 정보
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">성명 (실명) <span class="text-danger">*</span></label>
            <input type="text" name="employee_name" class="form-control"
                   value="<?= fv($fd,'employee_name') ?>" required>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">주소</label>
            <input type="text" name="employee_address" class="form-control"
                   value="<?= fv($fd,'employee_address') ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">연락처</label>
            <input type="text" name="employee_phone" class="form-control"
                   value="<?= fv($fd,'employee_phone') ?>" placeholder="010-0000-0000">
          </div>
        </div>
      </div>
    </div>

    <!-- 근무 조건 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-amber);color:#212529">
        <i class="bi bi-clock me-2"></i>근무 조건
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">계약 시작일 <span class="text-danger">*</span></label>
            <input type="date" name="contract_start_date" class="form-control"
                   value="<?= fv($fd,'contract_start_date',$defStart) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">계약 종료일 <span class="text-muted fw-normal">(비워두면 기간의 정함 없음)</span></label>
            <input type="date" name="contract_end_date" class="form-control"
                   value="<?= fv($fd,'contract_end_date') ?>">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">소정근로일 (주)</label>
            <div class="d-flex flex-wrap gap-2 mt-1">
              <?php foreach ($dayOpts as $day): ?>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="checkbox" name="work_days[]"
                       id="wd_<?= $day ?>" value="<?= $day ?>"
                       <?= in_array($day, $workDaysArr) ? 'checked' : '' ?>>
                <label class="form-check-label" for="wd_<?= $day ?>"><?= $day ?>요일</label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="col-md-5">
            <label class="form-label small fw-semibold">근무 시작시간 <span class="text-danger">*</span></label>
            <?php timePicker('wst', 'work_start_time', $fd['work_start_time'] ?? '') ?>
          </div>
          <div class="col-md-5">
            <label class="form-label small fw-semibold">근무 종료시간 <span class="text-danger">*</span></label>
            <?php timePicker('wet', 'work_end_time', $fd['work_end_time'] ?? '') ?>
          </div>
          <div class="col-md-2">
            <label class="form-label small fw-semibold">휴게 (분)</label>
            <input type="number" name="break_minutes" class="form-control" min="0" step="5"
                   value="<?= fvInt($fd,'break_minutes',30) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">주 소정근로시간</label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_hours" class="form-control" step="any" min="0"
                     value="<?= fv($fd,'weekly_scheduled_hours',$defWH) ?>">
              <span class="input-group-text">시간</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">주 소정근로일</label>
            <div class="input-group">
              <input type="number" name="weekly_scheduled_days" class="form-control" step="1" min="0" max="7"
                     value="<?= fv($fd,'weekly_scheduled_days',$defWD) ?>">
              <span class="input-group-text">일</span>
            </div>
          </div>
          <!-- 불일치 경고 -->
          <div class="col-12" id="mismatch-warn" style="display:none">
            <div class="alert alert-warning mb-0 py-2 small">
              <strong><i class="bi bi-exclamation-triangle-fill me-1"></i>입력 불일치</strong>
              <ul class="mb-0 mt-1" id="mismatch-list"></ul>
            </div>
          </div>

          <div class="col-md-6">
            <label class="form-label small fw-semibold">근무 장소</label>
            <input type="text" name="work_location" class="form-control"
                   value="<?= fv($fd,'work_location',$store['store_name'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">업무 내용</label>
            <input type="text" name="job_duties" class="form-control"
                   value="<?= fv($fd,'job_duties') ?>" placeholder="예: 매장 서빙 및 청소">
          </div>
        </div>
      </div>
    </div>

    <!-- 임금 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold" style="background:var(--c-pink);color:#212529">
        <i class="bi bi-currency-dollar me-2"></i>임금
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label small fw-semibold">시급 (원)</label>
            <input type="number" name="hourly_wage" class="form-control" min="0"
                   value="<?= fvInt($fd,'hourly_wage',$defWage) ?>">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">임금 지급일</label>
            <div class="input-group">
              <span class="input-group-text">매월</span>
              <input type="number" name="pay_day" class="form-control" min="1" max="31"
                     value="<?= fvInt($fd,'pay_day',25) ?>">
              <span class="input-group-text">일</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">지급 방법</label>
            <select name="pay_method" class="form-select">
              <?php foreach(['계좌이체','현금'] as $pm): ?>
              <option value="<?= $pm ?>" <?= fv($fd,'pay_method','계좌이체') === $pm ? 'selected' : '' ?>><?= $pm ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
      </div>
    </div>

    <!-- 기타 -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header fw-semibold bg-light">
        <i class="bi bi-list-check me-2"></i>기타
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">사회보험 가입 여부</label>
            <div class="d-flex flex-wrap gap-3 mt-1">
              <?php
              $insKeys = [
                  'insurance_pension'    => ['국민연금', 'national_pension_status'],
                  'insurance_health'     => ['건강보험', 'health_insurance_status'],
                  'insurance_employment' => ['고용보험', 'employment_insurance_status'],
              ];
              foreach ($insKeys as $iKey => [$iLabel, $dbKey]):
                  // 이전 계약서 값이 있으면 그대로, 없으면 판단 결과 'likely_required'이면 자동 체크
                  $checked = array_key_exists($iKey, $fd)
                      ? (bool)$fd[$iKey]
                      : (($ins[$dbKey] ?? '') === 'likely_required');
              ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="<?= $iKey ?>" id="<?= $iKey ?>"
                       value="1" <?= $checked ? 'checked' : '' ?>>
                <label class="form-check-label" for="<?= $iKey ?>"><?= $iLabel ?></label>
              </div>
              <?php endforeach; ?>
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="insurance_accident" id="insurance_accident"
                       value="1" checked disabled>
                <label class="form-check-label text-muted" for="insurance_accident">산재보험 (의무 적용)</label>
              </div>
            </div>
            <div id="insContractWarning" class="alert alert-danger small py-2 mt-2 mb-0" style="display:none">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>
              <span id="insContractWarningText"></span>
              계약서 미기재 시 분쟁 발생 시 불리할 수 있습니다. 관할 공단에 가입 여부를 확인하세요.
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">계약서 작성일</label>
            <input type="date" name="issue_date" class="form-control"
                   value="<?= fv($fd,'issue_date',date('Y-m-d')) ?>">
          </div>
          <div class="col-12">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="include_annual_leave"
                     id="include_annual_leave" value="1"
                     <?= !empty($fd['include_annual_leave']) ? 'checked' : '' ?>>
              <label class="form-check-label" for="include_annual_leave">
                연차 유급휴가 조항 포함 (「근로기준법」 제60조)
              </label>
            </div>
            <div class="form-text text-muted">
              5인 미만 사업장은 제60조 연차가 의무 적용되지 않습니다. 계약서에 넣으면 약정 연차로 인정됩니다.
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="d-flex gap-2 justify-content-end">
      <a href="<?= url('members','edit',['id'=>$member['id']]) ?>" class="btn btn-outline-secondary">취소</a>
      <button type="submit" class="btn text-white" style="background:var(--c-teal)">
        <i class="bi bi-save me-1"></i>계약서 저장 후 인쇄 화면으로
      </button>
    </div>
  </form>

  <script>
  // 4대보험 자동 판단 결과 (서버에서 전달)
  var _insJudgment = {
    'insurance_pension':    '<?= $ins['national_pension_status']     ?? '' ?>',
    'insurance_health':     '<?= $ins['health_insurance_status']     ?? '' ?>',
    'insurance_employment': '<?= $ins['employment_insurance_status'] ?? '' ?>'
  };
  var _insNames = {
    'insurance_pension':    '국민연금',
    'insurance_health':     '건강보험',
    'insurance_employment': '고용보험'
  };

  function checkInsuranceWarning() {
    var unchecked = [];
    Object.keys(_insJudgment).forEach(function (key) {
      if (_insJudgment[key] === 'likely_required') {
        var cb = document.getElementById(key);
        if (cb && !cb.checked) unchecked.push(_insNames[key]);
      }
    });
    var warnEl = document.getElementById('insContractWarning');
    var textEl = document.getElementById('insContractWarningText');
    if (!warnEl) return;
    if (unchecked.length) {
      textEl.textContent = unchecked.join(', ') + ' — 가입 대상 가능성이 높습니다. ';
      warnEl.style.display = '';
    } else {
      warnEl.style.display = 'none';
    }
  }

  // 직원 등록 정보 (불일치 비교 기준)
  var _mWage    = <?= (int)($member['hourly_wage']            ?? 0) ?>;
  var _mWeeklyH = <?= (float)($member['weekly_scheduled_hours'] ?? 0) ?>;
  var _mWeeklyD = <?= (int)($member['weekly_scheduled_days']   ?? 0) ?>;
  var _minWage  = <?= (int)($minWage ?? 0) ?>;

  function checkMismatch() {
    var warns = [];

    var startVal = (document.getElementById('tp_wst_val') || {}).value || '';
    var endVal   = (document.getElementById('tp_wet_val') || {}).value || '';
    var breakMin = parseInt((document.querySelector('[name=break_minutes]') || {}).value) || 0;
    var weeklyH  = parseFloat((document.querySelector('[name=weekly_scheduled_hours]') || {}).value) || 0;
    var weeklyD  = parseInt((document.querySelector('[name=weekly_scheduled_days]') || {}).value)    || 0;
    var wage     = parseInt((document.querySelector('[name=hourly_wage]') || {}).value)              || 0;
    var daysChk  = document.querySelectorAll('[name="work_days[]"]:checked').length;

    // 1. 시급 불일치
    if (_mWage > 0 && wage > 0 && wage !== _mWage) {
      warns.push('시급: 직원 등록 정보(' + _mWage.toLocaleString() + '원) ≠ 계약서(' + wage.toLocaleString() + '원)');
    }

    // 2. 주 소정근로시간 vs 직원 등록 정보
    if (_mWeeklyH > 0 && weeklyH > 0 && Math.abs(weeklyH - _mWeeklyH) > 0.1) {
      warns.push('주 소정근로시간: 직원 등록 정보(' + _mWeeklyH + '시간) ≠ 계약서(' + weeklyH + '시간)');
    }

    // 2b. 주 소정근로일 vs 직원 등록 정보
    if (_mWeeklyD > 0 && weeklyD > 0 && weeklyD !== _mWeeklyD) {
      warns.push('주 소정근로일: 직원 등록 정보(' + _mWeeklyD + '일) ≠ 계약서(' + weeklyD + '일)');
    }

    // 3. 근무 시간 계산치 vs 주 소정근로시간
    if (startVal && endVal && daysChk > 0 && weeklyH > 0) {
      var sp = startVal.split(':').map(Number);
      var ep = endVal.split(':').map(Number);
      var dailyMin = (ep[0] * 60 + ep[1]) - (sp[0] * 60 + sp[1]) - breakMin;
      if (dailyMin > 0) {
        var computed = Math.round((dailyMin / 60) * daysChk * 10) / 10;
        if (Math.abs(computed - weeklyH) > 0.4) {
          warns.push(
            '근무 시간 계산(' + startVal + '~' + endVal + ', 휴게 ' + breakMin + '분, ' +
            daysChk + '일) → 주 ' + computed + '시간 — 소정근로시간(' + weeklyH + '시간)과 다릅니다'
          );
        }
      }
    }

    // 4. 휴게시간 법정 기준 (근로기준법 제54조)
    if (startVal && endVal) {
      var bsp = startVal.split(':').map(Number);
      var bep = endVal.split(':').map(Number);
      var totalMin = (bep[0] * 60 + bep[1]) - (bsp[0] * 60 + bsp[1]);
      if (totalMin >= 480 && breakMin < 60) {
        warns.push('근로기준법 제54조 위반: 8시간 이상 근무 시 휴게 1시간 이상 필요 (현재 ' + breakMin + '분)');
      } else if (totalMin >= 240 && breakMin < 30) {
        warns.push('근로기준법 제54조 위반: 4시간 이상 근무 시 휴게 30분 이상 필요 (현재 ' + breakMin + '분)');
      }
    }

    // 5. 최저임금 미달
    if (_minWage > 0 && wage > 0 && wage < _minWage) {
      warns.push('최저임금 미달: 최저시급 ' + _minWage.toLocaleString() + '원 이상이어야 합니다 (현재 ' + wage.toLocaleString() + '원)');
    }

    var box  = document.getElementById('mismatch-warn');
    var list = document.getElementById('mismatch-list');
    if (warns.length) {
      list.innerHTML = warns.map(function(w) { return '<li>' + w + '</li>'; }).join('');
      box.style.display = '';
    } else {
      box.style.display = 'none';
    }
  }

  function tpSync(id) {
    var g    = document.getElementById('tp_' + id);
    var ampm = g.querySelector('.tp-ampm').value;
    var h    = parseInt(g.querySelector('.tp-hour').value, 10);
    var m    = parseInt(g.querySelector('.tp-min').value, 10);
    var h24  = h;
    if (ampm === 'am' && h === 12) h24 = 0;
    else if (ampm === 'pm' && h !== 12) h24 = h + 12;
    document.getElementById('tp_' + id + '_val').value =
      String(h24).padStart(2, '0') + ':' + String(m).padStart(2, '0');
    checkMismatch();
  }
  document.addEventListener('DOMContentLoaded', function () {
    ['wst', 'wet'].forEach(function (id) {
      tpSync(id);
    });

    // 불일치 실시간 검사 이벤트
    ['[name=hourly_wage]', '[name=weekly_scheduled_hours]', '[name=weekly_scheduled_days]',
     '[name=break_minutes]'].forEach(function (sel) {
      var el = document.querySelector(sel);
      if (el) el.addEventListener('input', checkMismatch);
    });
    document.querySelectorAll('[name="work_days[]"]').forEach(function (cb) {
      cb.addEventListener('change', checkMismatch);
    });
    checkMismatch();

    // 4대보험 체크박스 경고
    ['insurance_pension', 'insurance_health', 'insurance_employment'].forEach(function (id) {
      var cb = document.getElementById(id);
      if (cb) cb.addEventListener('change', checkInsuranceWarning);
    });
    checkInsuranceWarning();

    // 폼 제출 전 시간 필드 검증
    document.querySelector('form').addEventListener('submit', function (e) {
      var missing = [];
      if (!document.getElementById('tp_wst_val').value) missing.push('근무 시작시간');
      if (!document.getElementById('tp_wet_val').value) missing.push('근무 종료시간');
      if (missing.length) {
        e.preventDefault();
        alert(missing.join(', ') + '을(를) 입력하세요.');
        return;
      }
      var warnBox = document.getElementById('mismatch-warn');
      if (warnBox && warnBox.style.display !== 'none') {
        e.preventDefault();
        if (confirm('경고 항목이 있습니다.\n\n내용을 확인한 후 저장하거나,\n먼저 해당 항목을 수정하세요.\n\n그래도 저장하시겠습니까?')) {
          this.submit();
        }
      }
    });
  });
  </script>

  <?php if ($member['is_minor']): ?>
  <div class="alert alert-warning mt-4 d-flex gap-3">
    <i class="bi bi-person-exclamation fs-5 flex-shrink-0 mt-1"></i>
    <div>
      <strong>연소근로자(미성년자)</strong> 직원입니다.
      근로계약서 외에 <strong>친권자(후견인) 동의서</strong>도 별도로 작성이 필요합니다.
      <a href="<?= url('members','minor_consent',['id'=>$member['id']]) ?>" class="ms-2 btn btn-sm btn-warning">
        <i class="bi bi-file-earmark-text me-1"></i>동의서 작성
      </a>
    </div>
  </div>
  <?php endif; ?>
</div>
