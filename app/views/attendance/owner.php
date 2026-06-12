<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-door-open me-2 text-teal"></i>오늘 출퇴근 현황</h1>
  <div class="d-flex gap-2">
    <?php if ($pendingCnt > 0): ?>
    <a href="<?= url('attendance', 'corrections') ?>" class="btn btn-sm btn-outline-warning">
      <i class="bi bi-bell-fill me-1"></i>수정 요청
      <span class="badge bg-warning text-dark"><?= $pendingCnt ?></span>
    </a>
    <?php endif; ?>
    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addLogModal">
      <i class="bi bi-plus-circle me-1"></i>수동 추가
    </button>
    <span class="text-muted small align-self-center" id="refresh-stamp"></span>
  </div>
</div>

<!-- 요약 카드 -->
<div class="row g-3 mb-3">
  <?php
  $cards = [
    ['근무 중',   $summary['working'],   'bg-success-subtle',   'text-success',   'bi-person-workspace'],
    ['퇴근 완료', $summary['completed'], 'bg-secondary-subtle', 'text-secondary', 'bi-door-open'],
    ['미출근',    $summary['absent'],    'bg-danger-subtle',    'text-danger',    'bi-person-dash'],
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

<!-- 이번달 예상 인건비 -->
<?php
$laborCost = AttendanceLog::monthLaborCostForStore(Auth::storeId(), (int)date('Y'), (int)date('m'));
?>
<div class="card border-0 shadow-sm mb-4" style="background:var(--c-cream)">
  <div class="card-body d-flex justify-content-between align-items-center py-3 px-4">
    <div>
      <div class="small text-muted"><?= date('Y년 m월') ?> 예상 인건비 합계</div>
      <div class="fw-bold fs-4" style="color:var(--c-dark)"><?= formatWon($laborCost) ?></div>
      <div class="small text-muted">퇴근 완료 기준 누계</div>
    </div>
    <i class="bi bi-wallet2 fs-1 text-muted opacity-50"></i>
  </div>
</div>

<?php
if (!function_exists('_gPct')) {
    function _gPct(int $min, int $s, int $r): float { return max(0.0, min(100.0, ($min - $s) / $r * 100)); }
    function _hm(string $t): int { [$h,$m] = explode(':', substr($t,0,5)); return (int)$h*60+(int)$m; }
}
$GS = 7*60; $GE = 24*60; $GR = $GE - $GS;
?>
<style>@keyframes gpulse{0%,100%{opacity:.82}50%{opacity:.4}}</style>

<!-- 타임라인 -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold small"><i class="bi bi-bar-chart-steps me-1 text-primary"></i>오늘 근무 타임라인</span>
    <span style="font-size:.72rem;color:#868e96"><?= date('n월 j일', strtotime($today)) ?> · 빨간선=현재</span>
  </div>
  <div class="card-body p-0">
    <div style="display:flex;align-items:center;padding:3px 12px;background:#f8f9fa;border-bottom:1px solid #e9ecef">
      <div style="width:108px;flex-shrink:0"></div>
      <div style="flex:1;position:relative;height:16px">
        <?php for ($gh=8;$gh<=24;$gh+=2): $gp=_gPct($gh*60,$GS,$GR); ?>
        <span style="position:absolute;left:<?=$gp?>%;transform:translateX(-50%);font-size:.65rem;color:#adb5bd;white-space:nowrap"><?=$gh?>시</span>
        <?php endfor; ?>
      </div>
      <div style="width:72px;flex-shrink:0"></div>
    </div>

    <?php
    $nowPct = _gPct((int)date('H')*60+(int)date('i'), $GS, $GR);
    foreach ($allMembers as $m):
      $sched   = $m['schedule'] ?? null;
      $log     = $m['today_logs'][0] ?? null;
      $effIn   = $log ? ($log['effective_clock_in_at']  ?? $log['original_clock_in_at']  ?? null) : null;
      $effOut  = $log ? ($log['effective_clock_out_at'] ?? $log['original_clock_out_at'] ?? null) : null;
      $lateMin = (int)($m['late_minutes'] ?? 0);
      $status  = $m['today_status'] ?? 'absent';
      $barColor = $status==='working' ? '#3b82f6' : ($lateMin>5 ? '#f59e0b' : '#10b981');
    ?>
    <div style="display:flex;align-items:center;padding:4px 12px;border-bottom:1px solid #f1f3f5">
      <div style="width:108px;flex-shrink:0;font-size:.78rem;font-weight:600;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?=h($m['name'])?>"><?=h($m['name'])?></div>
      <div style="flex:1;height:26px;background:#f3f4f6;border-radius:4px;position:relative;overflow:hidden">
        <?php for ($gh=8;$gh<=24;$gh+=2): $gp=_gPct($gh*60,$GS,$GR); ?>
        <div style="position:absolute;top:0;bottom:0;left:<?=$gp?>%;width:1px;background:rgba(0,0,0,.06)"></div>
        <?php endfor; ?>
        <?php if ($sched): $sl=_gPct(_hm($sched['start_time']),$GS,$GR); $sw=_gPct(_hm($sched['end_time']),$GS,$GR)-$sl; ?>
        <div title="예정 <?=substr($sched['start_time'],0,5)?>~<?=substr($sched['end_time'],0,5)?>"
             style="position:absolute;top:4px;height:18px;left:<?=number_format($sl,2)?>%;width:<?=number_format(max(.4,$sw),2)?>%;background:rgba(15,118,110,.13);border:1.5px solid rgba(15,118,110,.35);border-radius:3px"></div>
        <?php endif; ?>
        <?php if ($effIn): $al=_gPct(_hm(date('H:i',strtotime($effIn))),$GS,$GR); $aw=_gPct(_hm($effOut?date('H:i',strtotime($effOut)):date('H:i')),$GS,$GR)-$al; ?>
        <div title="실제 <?=date('H:i',strtotime($effIn))?>~<?=$effOut?date('H:i',strtotime($effOut)):'근무중'?>"
             style="position:absolute;top:1px;height:24px;left:<?=number_format($al,2)?>%;width:<?=number_format(max(.4,$aw),2)?>%;background:<?=$barColor?>;border-radius:3px;opacity:.82<?=$status==='working'?';animation:gpulse 2s ease-in-out infinite':''?>"></div>
        <?php endif; ?>
        <?php if ($nowPct>=0&&$nowPct<=100): ?>
        <div style="position:absolute;top:0;bottom:0;left:<?=number_format($nowPct,2)?>%;width:2px;background:rgba(220,38,38,.65);z-index:5"></div>
        <?php endif; ?>
      </div>
      <div style="width:72px;flex-shrink:0;text-align:right;line-height:1.3;padding-left:6px">
        <?php if ($status==='working'): ?>
          <span class="badge bg-success" style="font-size:.65rem">근무중</span>
          <?php if ($lateMin>5): ?><div style="font-size:.64rem;color:#f59e0b;margin-top:1px">지각 <?=$lateMin?>분</div><?php endif; ?>
        <?php elseif ($status==='completed'): ?>
          <span class="badge bg-secondary" style="font-size:.65rem">퇴근</span>
          <?php if ($lateMin>5): ?><div style="font-size:.64rem;color:#f59e0b;margin-top:1px">지각 <?=$lateMin?>분</div><?php endif; ?>
        <?php elseif ($sched): ?>
          <span class="badge bg-danger-subtle text-danger border border-danger-subtle" style="font-size:.65rem">미출근</span>
        <?php else: ?>
          <span style="color:#adb5bd;font-size:.67rem">예정 없음</span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>

    <div style="display:flex;gap:14px;padding:6px 12px 6px 132px;background:#f8f9fa;font-size:.7rem;color:#6c757d">
      <span><i style="display:inline-block;width:12px;height:8px;background:rgba(15,118,110,.25);border:1.5px solid rgba(15,118,110,.4);border-radius:2px;margin-right:3px"></i>예정</span>
      <span><i style="display:inline-block;width:12px;height:8px;background:#10b981;border-radius:2px;margin-right:3px"></i>정상</span>
      <span><i style="display:inline-block;width:12px;height:8px;background:#3b82f6;border-radius:2px;margin-right:3px"></i>근무중</span>
      <span><i style="display:inline-block;width:12px;height:8px;background:#f59e0b;border-radius:2px;margin-right:3px"></i>지각</span>
    </div>
  </div>
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
          <th></th>
        </tr>
      </thead>
      <tbody id="att-tbody">
        <?php foreach ($allMembers as $m):
          $log        = $m['today_logs'][0] ?? null;
          $isLocked   = $log && AttendanceLog::isLocked($log);
          $isAdjusted = $log && ($log['adjustment_count'] ?? 0) > 0;
          $effIn      = $log['effective_clock_in_at']  ?? null;
          $effOut     = $log['effective_clock_out_at'] ?? null;
        ?>
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
            <?php if ($isAdjusted): ?>
              <span class="badge bg-warning-subtle text-warning border border-warning-subtle ms-1"
                    data-bs-toggle="tooltip" title="정정된 기록이 있습니다">정정</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($effIn): ?>
              <?php if ($isAdjusted && $log['adjusted_clock_in_at']): ?>
                <span class="text-decoration-line-through text-muted me-1" style="font-size:.75rem">
                  <?= date('H:i', strtotime($log['original_clock_in_at'])) ?>
                </span>
                <span class="fw-semibold text-warning-emphasis"><?= date('H:i', strtotime($effIn)) ?></span>
              <?php else: ?>
                <?= date('H:i', strtotime($effIn)) ?>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td>
            <?php if ($effOut): ?>
              <?php if ($isAdjusted && $log['adjusted_clock_out_at']): ?>
                <span class="text-decoration-line-through text-muted me-1" style="font-size:.75rem">
                  <?= $log['original_clock_out_at'] ? date('H:i', strtotime($log['original_clock_out_at'])) : '—' ?>
                </span>
                <span class="fw-semibold text-warning-emphasis"><?= date('H:i', strtotime($effOut)) ?></span>
              <?php else: ?>
                <?= date('H:i', strtotime($effOut)) ?>
              <?php endif; ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><?= $log ? minutesToHoursStr((int)$log['duration_minutes']) : '—' ?></td>
          <td>
            <?php if ($log && $log['duration_minutes'] > 0): ?>
              <?= formatWon(round($log['duration_minutes'] / 60 * $m['hourly_wage'])) ?>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td class="pe-2">
            <?php if ($log && !$isLocked): ?>
            <button class="btn btn-xs btn-outline-warning py-0 px-2"
                    onclick="openEditModal(
                      <?= $log['id'] ?>,
                      '<?= $effIn  ? date('Y-m-d\TH:i', strtotime($effIn))  : '' ?>',
                      '<?= $effOut ? date('Y-m-d\TH:i', strtotime($effOut)) : '' ?>'
                    )"
                    data-bs-toggle="tooltip" title="시간 정정 (원본 보존)">
              <i class="bi bi-pencil-square"></i>
            </button>
            <?php elseif ($isLocked): ?>
            <span class="text-muted" data-bs-toggle="tooltip" title="급여 확정 후 수정 불가">
              <i class="bi bi-lock-fill"></i>
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ── 수동 추가 모달 ─────────────────────────────── -->
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
            <label class="form-label fw-semibold">퇴근 시각
              <span class="text-muted small fw-normal">(없으면 비워두세요)</span>
            </label>
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

<!-- ── 시간 정정 모달 ─────────────────────────────── -->
<div class="modal fade" id="editLogModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold">
          <i class="bi bi-pencil-square me-1 text-warning"></i>출퇴근 시간 정정
        </h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form method="post" action="<?= url('attendance', 'edit_log') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="log_id" id="editLogId">
        <div class="modal-body">
          <div class="alert alert-info small py-2 mb-3">
            <i class="bi bi-shield-check me-1"></i>
            <strong>원본 기록은 보존됩니다.</strong>
            수정 요청은 직원에게 전달되며, 직원 수락 후에만 정정 기록에 반영됩니다.
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">정정 출근 시각</label>
            <input type="datetime-local" name="adjusted_clock_in_at" id="editClockIn"
                   class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">정정 퇴근 시각
              <span class="text-muted small fw-normal">(없으면 비워두세요)</span>
            </label>
            <input type="datetime-local" name="adjusted_clock_out_at" id="editClockOut"
                   class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">휴게시간(분)
              <span class="text-muted small fw-normal">(선택)</span>
            </label>
            <input type="number" name="break_minutes" id="editBreakMinutes" min="0" step="1"
                   class="form-control" placeholder="예) 60">
          </div>
          <div class="mb-2">
            <label class="form-label fw-semibold">수정 사유 <span class="text-danger">*</span></label>
            <textarea name="reason" class="form-control" rows="2"
                      placeholder="예) 퇴근 버튼 미클릭, 출근 시각 오기록 등"
                      required></textarea>
            <div class="form-text">수정 사유는 직원 화면에도 표시됩니다.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">취소</button>
          <button type="submit" class="btn btn-warning btn-sm">
            <i class="bi bi-save me-1"></i>정정 저장
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function openEditModal(logId, clockIn, clockOut) {
  document.getElementById('editLogId').value    = logId;
  document.getElementById('editClockIn').value  = clockIn  || '';
  document.getElementById('editClockOut').value = clockOut || '';
  document.getElementById('editBreakMinutes').value = '';
  new bootstrap.Modal(document.getElementById('editLogModal')).show();
}

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
        if (m.is_adjusted) {
          statusBadge += ' <span class="badge bg-warning-subtle text-warning border border-warning-subtle">정정</span>';
        }
        var dur = m.duration_minutes
          ? Math.floor(m.duration_minutes/60) + '시간 ' + (m.duration_minutes%60) + '분'
          : '—';
        var est = m.duration_minutes && m.hourly_wage
          ? Math.round(m.duration_minutes / 60 * m.hourly_wage).toLocaleString() + '원'
          : '—';
        var inTime  = m.is_adjusted && m.original_clock_in_time && m.original_clock_in_time !== m.clock_in_time
          ? '<s class="text-muted me-1" style="font-size:.75rem">' + m.original_clock_in_time + '</s><span class="fw-semibold text-warning-emphasis">' + (m.clock_in_time || '—') + '</span>'
          : (m.clock_in_time || '—');
        var outTime = m.is_adjusted && m.original_clock_out_time && m.original_clock_out_time !== m.clock_out_time
          ? '<s class="text-muted me-1" style="font-size:.75rem">' + m.original_clock_out_time + '</s><span class="fw-semibold text-warning-emphasis">' + (m.clock_out_time || '—') + '</span>'
          : (m.clock_out_time || '—');
        rows += '<tr>'
          + '<td class="fw-semibold">' + m.name + '</td>'
          + '<td>' + statusBadge + '</td>'
          + '<td>' + inTime  + '</td>'
          + '<td>' + outTime + '</td>'
          + '<td>' + dur + '</td>'
          + '<td>' + est + '</td>'
          + '<td></td>'
          + '</tr>';
      });
      tbody.innerHTML = rows;
      var now = new Date();
      document.getElementById('last-refresh').textContent =
        '최종 갱신: ' + String(now.getHours()).padStart(2,'0') + ':' +
        String(now.getMinutes()).padStart(2,'0') + ':' +
        String(now.getSeconds()).padStart(2,'0');
    })
    .catch(function(){});
}

refreshAttendance();
setInterval(refreshAttendance, 30000);

document.addEventListener('DOMContentLoaded', function() {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function(el) {
    new bootstrap.Tooltip(el);
  });
});
</script>
