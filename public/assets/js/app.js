document.addEventListener('DOMContentLoaded', function () {
  // Bootstrap 툴팁 초기화
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
    new bootstrap.Tooltip(el, { trigger: 'hover focus' });
  });

  // 5인 이상/미만 선택 시 가산수당 체크박스 자동 토글
  const countTypeInputs = document.querySelectorAll('[name=employee_count_type]');
  if (countTypeInputs.length) {
    function applyCountType() {
      const isOver5 = document.querySelector('[name=employee_count_type]:checked')?.value === 'over5';
      ['apply_overtime_premium', 'apply_night_premium', 'apply_holiday_premium'].forEach(function (id) {
        const el = document.getElementById(id);
        if (el) el.checked = isOver5;
      });
    }
    countTypeInputs.forEach(function (el) {
      el.addEventListener('change', applyCountType);
    });
  }

  // 날짜 입력 한국어 요일 표시 — 레이블 옆에 붙여 컬럼 높이에 영향 없음
  document.querySelectorAll('input[type=date]').forEach(function (input) {
    var hint = document.createElement('small');
    hint.className = 'dow-hint text-muted ms-1 fw-normal';

    // 가장 가까운 컨테이너에서 label 찾기
    var container = input.closest('.col, .col-12, .col-sm-5, .col-sm-4, .col-md-4, .col-md-5, .mb-3, .col-6');
    var label = container ? container.querySelector('label') : null;
    if (label) {
      label.appendChild(hint);
    } else {
      input.insertAdjacentElement('afterend', hint);
    }

    function showDow() {
      if (!input.value) { hint.textContent = ''; return; }
      var days = ['일', '월', '화', '수', '목', '금', '토'];
      var d = new Date(input.value + 'T00:00:00');
      hint.textContent = '(' + days[d.getDay()] + ')';
    }
    input.addEventListener('change', showDow);
    showDow();
  });
});
