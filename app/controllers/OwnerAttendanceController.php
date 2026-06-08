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

        // 멤버별 오늘 상태 요약
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

    /** 점주 수동 출퇴근 추가 */
    public function addLog(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance'));
        }
        verify_csrf();

        $storeId      = Auth::storeId();
        $memberId     = (int)($_POST['store_member_id'] ?? 0);
        $clockIn      = trim($_POST['clock_in_at']  ?? '');
        $clockOut     = trim($_POST['clock_out_at'] ?? '') ?: null;

        $member = StoreMember::find($memberId, $storeId);
        if ($member && $clockIn) {
            AttendanceLog::ownerCreate($storeId, $memberId, $clockIn, $clockOut);
            flash('success', $member['name'] . ' 출퇴근 기록을 추가했습니다.');
        }
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
        flash('success', '수정 요청을 승인했습니다.');
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
}
