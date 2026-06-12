<div class="d-flex justify-content-between align-items-center mb-4">
  <h1 class="h3 mb-0"><i class="bi bi-people-fill me-2 text-primary"></i>직원 관리</h1>
  <a href="<?= url('members', 'add') ?>" class="btn btn-primary">
    <i class="bi bi-plus-circle me-1"></i>직원 등록
  </a>
</div>

<?php if (empty($members)): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-person-x fs-1 d-block mb-2"></i>
    등록된 직원이 없습니다.
    <a href="<?= url('members', 'add') ?>" class="d-block mt-2">첫 직원 등록하기</a>
  </div>
</div>
<?php else: ?>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small">
      <thead class="table-light">
        <tr>
          <th>이름</th>
          <th>연락처</th>
          <th>시급</th>
          <th>계정 상태</th>
          <th>근무 상태</th>
          <th>4대보험</th>
          <th class="text-end pe-3">액션</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($members as $m):
          $ins     = $insuranceByMember[$m['id']] ?? null;
          $summary = $actualSummary[$m['id']]     ?? null;

          // 계약 vs 실제 경고 계산
          $contractWeekly  = (float)($m['weekly_scheduled_hours'] ?? 0);
          $contractMonthly = round($contractWeekly * 4.345, 1);
          $hasActual       = !empty($summary['has_data']);
          $avgWeekly       = $summary['avg_weekly_hours']    ?? 0;
          $actualMonth     = $summary['current_month_hours'] ?? 0;

          $timeWarn15h   = $hasActual && $contractWeekly < 15 && $avgWeekly >= 15;
          $timeWarn60h   = $hasActual && $contractMonthly < 60 && $actualMonth >= 60;
          $timeMismatch  = $hasActual && $contractWeekly > 0 && abs($avgWeekly - $contractWeekly) >= 5;
          $hasTimeWarn   = $timeWarn15h || $timeWarn60h || $timeMismatch;

          $warnTitle = '';
          if ($timeWarn15h)  $warnTitle .= '계약 주15h미만→실제'.number_format($avgWeekly,1).'h (주휴/4대보험 재검토) ';
          if ($timeWarn60h)  $warnTitle .= '계약월'.number_format($contractMonthly,1).'h미만→실제'.number_format($actualMonth,1).'h (4대보험 재검토) ';
          if ($timeMismatch) $warnTitle .= '계약'.number_format($contractWeekly,1).'h vs 실제'.number_format($avgWeekly,1).'h/주 불일치 ';
        ?>
        <tr>
          <td class="fw-semibold">
            <a href="<?= url('members', 'edit', ['id' => $m['id']]) ?>"
               class="text-decoration-none" style="color:var(--c-dark)">
              <?= h($m['name']) ?>
            </a>
            <?php if ($m['is_minor'] ?? 0): ?>
            <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem"
                  data-bs-toggle="tooltip" title="연소근로자 — 친권자 동의서 필요">연소</span>
            <?php endif; ?>
            <?php if ($hasTimeWarn): ?>
            <span class="ms-1" data-bs-toggle="tooltip" data-bs-placement="right"
                  title="<?= h(trim($warnTitle)) ?>">
              <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size:.85rem"></i>
            </span>
            <?php endif; ?>
            <?php if (($m['works_at_other_business'] ?? 'UNKNOWN') === 'YES'): ?>
            <span class="badge ms-1" style="background:var(--c-amber);color:#000;font-size:.65rem">타사업장 겸직</span>
            <?php endif; ?>
            <?php if (($m['other_business_insurance_enrolled'] ?? 'UNKNOWN') === 'YES'): ?>
            <span class="badge bg-danger ms-1" style="font-size:.65rem">고용보험 확인 필요</span>
            <?php endif; ?>
          </td>
          <td class="text-muted"><?= h($m['phone'] ?: '—') ?></td>
          <td><?= number_format($m['hourly_wage']) ?>원</td>
          <td>
            <?php
              $as = $m['account_status'] ?? ($m['user_id'] ? 'linked' : 'no_account');
            ?>
            <?php if ($as === 'linked'): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-person-check me-1"></i>연결됨
              </span>
            <?php elseif ($as === 'invited'): ?>
              <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle">
                <i class="bi bi-send me-1"></i>초대 대기
              </span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border">계정 없음</span>
            <?php endif; ?>
          </td>
          <td>
            <?php $es = $m['employment_status'] ?? ($m['is_active'] ? 'active' : 'resigned'); ?>
            <?php if ($es === 'active'): ?>
              <span class="badge bg-primary-subtle text-primary border">근무중</span>
            <?php elseif ($es === 'on_leave'): ?>
              <span class="badge bg-warning-subtle text-warning-emphasis border">휴직</span>
            <?php elseif ($es === 'resigned'): ?>
              <span class="badge bg-danger-subtle text-danger border">퇴사</span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border">종료</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$ins): ?>
              <span class="badge bg-light text-muted border" data-bs-toggle="tooltip" title="직원 수정에서 설정">미확인</span>
            <?php elseif ($ins['user_selected_status'] === 'enrolled'): ?>
              <span class="badge bg-success-subtle text-success border border-success-subtle">
                <i class="bi bi-shield-check me-1"></i>가입
              </span>
            <?php elseif ($ins['user_selected_status'] === 'not_enrolled' && $ins['warning_acknowledged']): ?>
              <span class="badge bg-danger-subtle text-danger border border-danger-subtle" data-bs-toggle="tooltip" title="리스크 확인 완료">
                <i class="bi bi-shield-x me-1"></i>미가입⚠
              </span>
            <?php elseif ($ins['user_selected_status'] === 'not_enrolled'): ?>
              <span class="badge bg-warning-subtle text-warning-emphasis border">
                <i class="bi bi-shield-exclamation me-1"></i>미가입
              </span>
            <?php else: ?>
              <span class="badge bg-secondary-subtle text-secondary border">확인필요</span>
            <?php endif; ?>
          </td>
          <td class="text-end pe-3">
            <div class="d-flex gap-1 justify-content-end">
              <a href="<?= url('work_logs', 'index', ['employee_id' => $m['id']]) ?>"
                 class="btn btn-sm btn-outline-secondary"
                 data-bs-toggle="tooltip" title="근무 기록">
                <i class="bi bi-calendar3"></i>
              </a>
              <a href="<?= url('payroll', 'index', ['employee_id' => $m['id'], 'week_date' => date('Y-m-d')]) ?>"
                 class="btn btn-sm btn-outline-success"
                 data-bs-toggle="tooltip" title="주간 급여 계산">
                <i class="bi bi-calculator"></i>
              </a>
              <a href="<?= url('severance', 'index', ['employee_id' => $m['id']]) ?>"
                 class="btn btn-sm btn-outline-warning"
                 data-bs-toggle="tooltip" title="퇴직금 계산">
                <i class="bi bi-bank"></i>
              </a>
              <a href="<?= url('members', 'contract', ['id' => $m['id']]) ?>"
                 target="_blank"
                 class="btn btn-sm btn-outline-secondary"
                 data-bs-toggle="tooltip" title="근로계약서">
                <i class="bi bi-file-earmark-text"></i>
              </a>
              <?php if ($m['is_minor'] ?? 0): ?>
              <a href="<?= url('members', 'minor_consent', ['id' => $m['id']]) ?>"
                 target="_blank"
                 class="btn btn-sm btn-outline-warning"
                 data-bs-toggle="tooltip" title="친권자 동의서">
                <i class="bi bi-file-earmark-person"></i>
              </a>
              <?php endif; ?>
              <a href="<?= url('members', 'edit', ['id' => $m['id']]) ?>"
                 class="btn btn-sm btn-outline-primary"
                 data-bs-toggle="tooltip" title="수정">
                <i class="bi bi-pencil"></i>
              </a>
              <form method="post" action="<?= url('members', 'delete') ?>"
                    onsubmit="return confirm('<?= h(addslashes($m['name'])) ?> 직원을 삭제하면 출퇴근 기록도 함께 삭제됩니다.\n정말 삭제하시겠습니까?')">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= $m['id'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger ms-1"
                        data-bs-toggle="tooltip" title="삭제">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php endif; ?>
