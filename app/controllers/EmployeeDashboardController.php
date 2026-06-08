<?php
class EmployeeDashboardController
{
    public function index(): void
    {
        Auth::requireEmployee();
        $storeId      = Auth::storeId();
        $memberId     = Auth::storeMemberId();
        $today        = date('Y-m-d');

        $member       = StoreMember::find($memberId, $storeId);
        if (!$member) {
            Auth::logout();
            redirect(url('auth', 'login'));
        }

        $working      = AttendanceLog::currentlyWorking($memberId);
        $recentLogs   = AttendanceLog::recentForMember($memberId, 14);

        [$weekStart]  = getWeekRange($today);
        $weekSummary  = AttendanceLog::weekSummary($memberId, $weekStart);
        $monthSummary = AttendanceLog::monthSummary($memberId, date('Y'), date('m'));

        // 이번달 예상 급여 (완료된 시간 × 시급)
        $monthPayEst  = round($monthSummary['total_minutes'] / 60 * $member['hourly_wage']);

        $corrections  = AttendanceCorrectionRequest::allForMember($memberId);

        render_employee('employee/dashboard', [
            'title'        => '출퇴근',
            'member'       => $member,
            'working'      => $working,
            'recentLogs'   => $recentLogs,
            'weekSummary'  => $weekSummary,
            'monthSummary' => $monthSummary,
            'monthPayEst'  => $monthPayEst,
            'corrections'  => $corrections,
            'today'        => $today,
        ]);
    }

    public function clockIn(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();

        $already = AttendanceLog::currentlyWorking($memberId);
        if ($already) {
            flash('error', '이미 출근 중입니다.');
        } else {
            AttendanceLog::clockIn($storeId, $memberId, 'pwa');
            flash('success', '출근 완료!');
        }
        redirect(url('employee'));
    }

    public function clockOut(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $logId    = (int)($_POST['log_id'] ?? 0);

        if ($logId && AttendanceLog::clockOut($logId, $storeId, $memberId)) {
            flash('success', '퇴근 완료!');
        } else {
            flash('error', '퇴근 처리에 실패했습니다.');
        }
        redirect(url('employee'));
    }

    /** 수정 요청 제출 */
    public function requestCorrection(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $reason   = trim($_POST['reason'] ?? '');
        $logId    = (int)($_POST['attendance_log_id'] ?? 0) ?: null;
        $reqIn    = trim($_POST['requested_clock_in_at']  ?? '') ?: null;
        $reqOut   = trim($_POST['requested_clock_out_at'] ?? '') ?: null;

        if (!$reason) {
            flash('error', '수정 사유를 입력하세요.');
            redirect(url('employee'));
        }

        AttendanceCorrectionRequest::create([
            'attendance_log_id'      => $logId,
            'store_id'               => $storeId,
            'store_member_id'        => $memberId,
            'requested_clock_in_at'  => $reqIn,
            'requested_clock_out_at' => $reqOut,
            'reason'                 => $reason,
        ]);

        flash('success', '수정 요청을 접수했습니다. 점주 승인 후 반영됩니다.');
        redirect(url('employee'));
    }
}
