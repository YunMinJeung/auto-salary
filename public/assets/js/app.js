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

  // 날짜 입력 한국어 요일 표시
  document.querySelectorAll('input[type=date]').forEach(function (input) {
    function showDow() {
      if (!input.value) return;
      const days = ['일', '월', '화', '수', '목', '금', '토'];
      const d = new Date(input.value + 'T00:00:00');
      const hint = input.parentElement.querySelector('.dow-hint');
      if (hint) hint.textContent = '(' + days[d.getDay()] + ')';
    }
    const hint = document.createElement('small');
    hint.className = 'dow-hint text-muted ms-1';
    input.insertAdjacentElement('afterend', hint);
    input.addEventListener('change', showDow);
    showDow();
  });
});
