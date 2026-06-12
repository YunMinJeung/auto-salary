<?php
class OwnerAttendanceController
{
    public function index(): void
    {
        Auth::requireOwner();
        $storeId  = Auth::storeId();
        $today    = date('Y-m-d');

        $logs        = AttendanceLog::todayForStore($storeId, $today);
        $allMembers  = StoreMember::allForStore($storeId, true);
        $pendingCnt  = AttendanceCorrectionRequest::pendingCount($storeId);

        $logByMember = [];
        foreach ($logs as $log) {
            $mid = $log['store_member_id'];
            if (!isset($logByMember[$mid])) $logByMember[$mid] = [];
            $logByMember[$mid][] = $log;
        }

        $summary = ['working' => 0, 'completed' => 0, 'absent' => 0];
        foreach ($allMembers as &$m) {
            $m['today_logs'] = $logByMember[$m['id']] ?? [];
            $latest = $m['today_logs'][0] ?? null;
            if (!$latest) {
                $m['today_status'] = 'absent';
                $summary['absent']++;
            } elseif ($latest['status'] === 'working') {
                $m['today_status'] = 'working';
                $summary['working']++;
            } else {
                $m['today_status'] = 'completed';
                $summary['completed']++;
            }
        }
        unset($m);

        render('attendance/owner', [
            'title'      => '오늘 출퇴근 현황',
            'today'      => $today,
            'allMembers' => $allMembers,
            'summary'    => $summary,
            'pendingCnt' => $pendingCnt,
        ]);
    }

    /** 점주 수동 출퇴근 추가 (신규 기록 — original 필드에 저장) */
    public function addLog(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = (int)($_POST['store_member_id'] ?? 0);
        $clockIn  = trim($_POST['clock_in_at']  ?? '');
        $clockOut = trim($_POST['clock_out_at'] ?? '') ?: null;

        $member = StoreMember::find($memberId, $storeId);
        if ($member && $clockIn) {
            AttendanceLog::ownerCreate($storeId, $memberId, $clockIn, $clockOut);
            flash('success', h($member['name']) . ' 출퇴근 기록을 추가했습니다.');
        }
        redirect(url('attendance'));
    }

    /**
     * 점주 출퇴근 정정 요청
     * - 즉시 반영하지 않고 attendance_change_requests 에 직원 검토 대기 요청으로 저장
     * - 직원 수락(또는 점주 강제 확정) 시점에 adjusted_* 필드에 반영됨
     * - 수정 사유 필수 / 원본(original_*) 절대 변경 안 함
     * - 급여 확정/지급 완료 또는 이미 대기 중인 요청이 있으면 잠금
     */
    public function editLog(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance'));
        }
        verify_csrf();

        $storeId     = Auth::storeId();
        $logId       = (int)($_POST['log_id'] ?? 0);
        $adjClockIn  = trim($_POST['adjusted_clock_in_at']  ?? '');
        $adjClockOut = trim($_POST['adjusted_clock_out_at'] ?? '') ?: null;
        $breakRaw    = trim($_POST['break_minutes'] ?? '');
        $breakMin    = $breakRaw === '' ? null : (int)$breakRaw;
        $reason      = trim($_POST['reason'] ?? '');

        if (!$reason) {
            flash('error', '수정 사유는 필수입니다.');
            redirect(url('attendance'));
        }
        if (!$adjClockIn) {
            flash('error', '정정 출근 시각을 입력하세요.');
            redirect(url('attendance'));
        }

        $log = AttendanceLog::find($logId, $storeId);
        if (!$log) {
            flash('error', '기록을 찾을 수 없습니다.');
            redirect(url('attendance'));
        }

        if (AttendanceLog::isLocked($log)) {
            flash('error', '급여 확정/지급 완료 상태이거나 이미 직원 확인 대기 중인 수정 요청이 있습니다.');
            redirect(url('attendance'));
        }

        // 수정 전 유효 시각 (adjusted 우선, 없으면 original)
        $beforeIn  = $log['adjusted_clock_in_at']  ?? $log['original_clock_in_at'];
        $beforeOut = $log['adjusted_clock_out_at'] ?? $log['original_clock_out_at'];

        $requestId = AttendanceChangeRequest::create(
            $storeId,
            $logId,
            (int)$log['store_member_id'],
            Auth::id(),
            [
                'clock_in'  => $beforeIn,
                'clock_out' => $beforeOut,
                'break_min' => $log['break_minutes'] ?? null,
            ],
            [
                'clock_in'  => $adjClockIn,
                'clock_out' => $adjClockOut,
                'break_min' => $breakMin,
            ],
            $reason
        );

        AttendanceLog::markPending($logId, $storeId, $requestId);

        flash('success', '수정 요청이 직원에게 전달되었습니다. 직원 수락 후 반영됩니다.');
        redirect(url('attendance'));
    }

    /** 수정 요청 관리 */
    public function corrections(): void
    {
        Auth::requireOwner();
        $storeId  = Auth::storeId();
        $requests = AttendanceCorrectionRequest::allForStore($storeId);

        render('corrections/manage', [
            'title'    => '수정 요청 관리',
            'requests' => $requests,
        ]);
    }

    public function approveCorrection(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance', 'corrections'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $comment = trim($_POST['owner_comment'] ?? '');

        AttendanceCorrectionRequest::approve($id, $storeId, $comment);
        flash('success', '수정 요청을 승인했습니다. 정정 이력이 기록되었습니다.');
        redirect(url('attendance', 'corrections'));
    }

    public function rejectCorrection(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance', 'corrections'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $comment = trim($_POST['owner_comment'] ?? '');

        AttendanceCorrectionRequest::reject($id, $storeId, $comment);
        flash('success', '수정 요청을 반려했습니다.');
        redirect(url('attendance', 'corrections'));
    }

    public function reapproveCorrection(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance', 'corrections'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $comment = trim($_POST['owner_comment'] ?? '');

        $req = AttendanceCorrectionRequest::find($id, $storeId);
        if ($req && $req['status'] === 'objected') {
            AttendanceCorrectionRequest::approve($id, $storeId, $comment);
            flash('success', '이의제기를 수락하고 승인 처리했습니다.');
        }
        redirect(url('attendance', 'corrections'));
    }

    public function finalRejectCorrection(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance', 'corrections'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $comment = trim($_POST['owner_comment'] ?? '');

        DB::query(
            "UPDATE attendance_correction_requests
             SET status = 'final_rejected', owner_comment = ?, updated_at = NOW()
             WHERE id = ? AND store_id = ? AND status = 'objected'",
            [$comment, $id, $storeId]
        );
        flash('success', '이의제기를 최종 반려했습니다.');
        redirect(url('attendance', 'corrections'));
    }
}
