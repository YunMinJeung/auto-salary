<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-door-open me-2 text-teal"></i>오늘 출퇴근 현황</h1>
  <div class="d-flex gap-2">
    <?php if ($pendingCnt > 0): ?>
    <a href="<?= url('attendance', 'corrections') ?>" class="btn btn-sm btn-outline-warning">
      <i class="bi bi-bell-fill me-1"></i>수정 요청 <span class="badge bg-warning text-dark"><?= $pendingCnt ?></span>
    </a>
    <?php endif; ?>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLogModal">
      <i class="bi bi-plus-circle me-1"></i>수동 추가
    </button>
    <span class="text-muted small align-self-center" id="refresh-stamp"></span>
  </div>
</div>

<!-- 요약 카드 -->
<div class="row g-3 mb-4">
  <?php
  $cards = [
    ['근무 중',  $summary['working'],  'bg-success-subtle', 'text-success',  'bi-person-workspace'],
    ['퇴근 완료', $summary['completed'], 'bg-secondary-subtle','text-secondary','bi-door-open'],
    ['미출근',   $summary['absent'],   'bg-danger-subtle',  'text-danger',   'bi-person-dash'],
  ];
  foreach ($cards as [$lbl, $cnt, $bg, $fg, $icon]):
  ?>
  <div class="col-4">
    <div class="card border-0 shadow-sm text-center py-3 <?= $bg ?>">
      <i class="bi <?= $icon ?> fs-4 <?= $fg ?> mb-1"></i>
      <div class="fw-bold fs-3 <?= $fg ?>"><?= $cnt ?></div>
      <div class="small <?= $fg ?>"><?= $lbl ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- 직원 목록 -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold small d-flex justify-content-between">
    <span><?= date('Y년 m월 d일', strtotime($today)) ?> 기준</span>
    <span class="text-muted" id="last-refresh">자동 갱신 중...</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" id="att-table">
      <thead class="table-light">
        <tr>
          <th>이름</th>
          <th>상태</th>
          <th>출근</th>
          <th>퇴근</th>
          <th>근무시간</th>
          <th>예상 급여</th>
        </tr>
      </thead>
      <tbody id="att-tbody">
        <?php foreach ($allMembers as $m): ?>
        <?php $log = $m['today_logs'][0] ?? null; ?>
        <tr>
          <td class="fw-semibold"><?= h($m['name']) ?></td>
          <td>
            <?php if ($m['today_status'] === 'working'): ?>
              <span class="badge bg-success">근무중</span>
            <?php elseif ($m['today_status'] === 'completed'): ?>
              <span class="badge bg-secondary">퇴근</span>
            <?php else: ?>
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle">미출근</span>
            <?php endif; ?>
          </td>
          <td><?= $log ? date('H:i', strtotime($log['clock_in_at'])) : '—' ?></td>
          <td><?= ($log && $log['clock_out_at']) ? date('H:i', strtotime($log['clock_out_at'])) : '—' ?></td>
          <td>
            <?php if ($log): ?>
              <?= minutesToHoursStr((int)$log['duration_minutes']) ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($log && $log['duration_minutes'] > 0): ?>
              <?= formatWon($log['duration_minutes'] / 60 * $m['hourly_wage']) ?>
            <?php else: ?>—<?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- 수동 출퇴근 추가 모달 -->
<div class="modal fade" id="addLogModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">출퇴근 기록 수동 추가</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= url('attendance', 'add_log') ?>">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label fw-semibold">직원</label>
            <select name="store_member_id" class="form-select" required>
              <option value="">— 선택 —</option>
              <?php foreach ($allMembers as $m): ?>
              <option value="<?= $m['id'] ?>"><?= h($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">출근 시각</label>
            <input type="datetime-local" name="clock_in_at" class="form-control"
                   value="<?= date('Y-m-d') ?>T09:00" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">퇴근 시각 <span class="text-muted small">(없으면 비워두세요)</span></label>
            <input type="datetime-local" name="clock_out_at" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-primary btn-sm">추가</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// 30초마다 AJAX 갱신
function refreshAttendance() {
  fetch('<?= BASE_URL ?>api/owner/today-attendance.php', { credentials: 'same-origin' })
    .then(function(r){ return r.ok ? r.json() : null; })
    .then(function(data) {
      if (!data || !data.success) return;
      var tbody = document.getElementById('att-tbody');
      if (!tbody) return;
      var rows = '';
      data.members.forEach(function(m) {
        var statusBadge = m.today_status === 'working'
          ? '<span class="badge bg-success">근무중</span>'
          : m.today_status === 'completed'
          ? '<span class="badge bg-secondary">퇴근</span>'
          : '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">미출근</span>';
        var dur = m.duration_minutes
          ? Math.floor(m.duration_minutes/60) + '시간 ' + (m.duration_minutes%60) + '분'
          : '—';
        var est = m.duration_minutes && m.hourly_wage
          ? Math.round(m.duration_minutes / 60 * m.hourly_wage).toLocaleString() + '원'
          : '—';
        rows += '<tr>'
          + '<td class="fw-semibold">' + m.name + '</td>'
          + '<td>' + statusBadge + '</td>'
          + '<td>' + (m.clock_in_time || '—') + '</td>'
          + '<td>' + (m.clock_out_time || '—') + '</td>'
          + '<td>' + dur + '</td>'
          + '<td>' + est + '</td>'
          + '</tr>';
      });
      tbody.innerHTML = rows;
      var now = new Date();
      document.getElementById('last-refresh').textContent =
        '최종 갱신: ' + String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0') + ':' + String(now.getSeconds()).padStart(2,'0');
    })
    .catch(function(){});
}

refreshAttendance();
setInterval(refreshAttendance, 30000);
</script>
