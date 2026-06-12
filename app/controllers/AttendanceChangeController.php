<?php
class AttendanceChangeController
{
    /** 점주: 수정 요청 목록 */
    public function index(): void
    {
        Auth::requireOwner();
        $storeId  = Auth::storeId();
        $requests = AttendanceChangeRequest::findPending($storeId);

        render('attendance/change_requests', [
            'title'    => '출퇴근 수정 요청',
            'requests' => $requests,
        ]);
    }

    /** 점주: 강제 확정 */
    public function forceConfirm(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance_change'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $reason  = trim($_POST['force_reason'] ?? '');

        if (!$reason) {
            flash('error', '강제 확정 사유는 필수입니다.');
            redirect(url('attendance_change'));
        }

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req) {
            flash('error', '요청을 찾을 수 없습니다.');
            redirect(url('attendance_change'));
        }

        AttendanceChangeRequest::forceConfirm($id, $storeId, $reason, Auth::id());
        flash('success', '강제 확정했습니다. 직원에게 수정 내역이 공개됩니다.');
        redirect(url('attendance_change'));
    }

    /** 점주: 협의 후 확정 (원본 유지) */
    public function resolve(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance_change'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $note    = trim($_POST['resolution_note'] ?? '');

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req) {
            flash('error', '요청을 찾을 수 없습니다.');
            redirect(url('attendance_change'));
        }

        AttendanceChangeRequest::resolve($id, $storeId, $note);
        flash('success', '협의 확정 처리했습니다. 원본 기록이 유지됩니다.');
        redirect(url('attendance_change'));
    }

    /** 직원: 수락 */
    public function accept(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req || (int)$req['store_member_id'] !== Auth::storeMemberId()) {
            flash('error', '본인의 요청만 처리할 수 있습니다.');
            redirect(url('employee'));
        }

        AttendanceChangeRequest::accept($id, $storeId);
        flash('success', '수정을 수락했습니다.');
        redirect(url('employee'));
    }

    /** 직원: 이의제기 */
    public function object(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId   = Auth::storeId();
        $id        = (int)($_POST['id'] ?? 0);
        $objection = trim($_POST['objection'] ?? '');

        if (!$objection) {
            flash('error', '이의제기 사유를 입력하세요.');
            redirect(url('employee'));
        }

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req || (int)$req['store_member_id'] !== Auth::storeMemberId()) {
            flash('error', '본인의 요청만 처리할 수 있습니다.');
            redirect(url('employee'));
        }

        $reqClockIn  = !empty($_POST['requested_clock_in'])  ? $_POST['requested_clock_in']  : null;
        $reqClockOut = !empty($_POST['requested_clock_out']) ? $_POST['requested_clock_out'] : null;
        $reqBreakMin = isset($_POST['requested_break_min']) && $_POST['requested_break_min'] !== ''
                       ? (int)$_POST['requested_break_min'] : null;

        AttendanceChangeRequest::object($id, $storeId, $objection, $reqClockIn, $reqClockOut, $reqBreakMin);
        flash('success', '이의제기를 전달했습니다. 사장님 검토 후 처리됩니다.');
        redirect(url('employee'));
    }

    /** 점주: 이의제기 목록 */
    public function objections(): void
    {
        Auth::requireOwner();
        render('attendance/objections', [
            'title'      => '직원 이의제기 관리',
            'objections' => AttendanceChangeRequest::findObjections(Auth::storeId()),
        ]);
    }

    /** 점주: 이의제기 수락 */
    public function acceptObjection(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance_change', 'objections'));
        }
        verify_csrf();

        $storeId    = Auth::storeId();
        $id         = (int)($_POST['id'] ?? 0);
        $acceptType = ($_POST['accept_type'] ?? 'employee_request') === 'original'
                      ? 'original' : 'employee_request';
        $response   = trim($_POST['owner_response'] ?? '');

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req) {
            flash('error', '요청을 찾을 수 없습니다.');
            redirect(url('attendance_change', 'objections'));
        }

        AttendanceChangeRequest::acceptObjection($id, $storeId, Auth::id(), $acceptType, $response);
        flash('success', '이의제기를 수락했습니다.');
        redirect(url('attendance_change', 'objections'));
    }

    /** 점주: 이의제기 거부 (현재 수정안 유지) */
    public function rejectObjection(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance_change', 'objections'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $reason  = trim($_POST['reason'] ?? '');

        if (!$reason) {
            flash('error', '거부 사유는 필수입니다.');
            redirect(url('attendance_change', 'objections'));
        }

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req) {
            flash('error', '요청을 찾을 수 없습니다.');
            redirect(url('attendance_change', 'objections'));
        }

        AttendanceChangeRequest::rejectObjection($id, $storeId, Auth::id(), $reason);
        flash('success', '이의제기를 거부했습니다. 현재 수정안이 유지됩니다.');
        redirect(url('attendance_change', 'objections'));
    }

    /** 점주: 재수정 제안 */
    public function counterPropose(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('attendance_change', 'objections'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $id       = (int)($_POST['id'] ?? 0);
        $clockIn  = trim($_POST['counter_clock_in'] ?? '');
        $clockOut = trim($_POST['counter_clock_out'] ?? '');
        $breakMin = isset($_POST['counter_break_min']) && $_POST['counter_break_min'] !== ''
                    ? (int)$_POST['counter_break_min'] : 0;
        $reason   = trim($_POST['counter_reason'] ?? '');

        if (!$clockIn || !$clockOut || !$reason) {
            flash('error', '재수정 시간과 사유는 필수입니다.');
            redirect(url('attendance_change', 'objections'));
        }

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req) {
            flash('error', '요청을 찾을 수 없습니다.');
            redirect(url('attendance_change', 'objections'));
        }

        AttendanceChangeRequest::counterPropose($id, $storeId, Auth::id(), $clockIn, $clockOut, $breakMin, $reason);
        flash('success', '재수정안을 직원에게 전달했습니다.');
        redirect(url('attendance_change', 'objections'));
    }

    /** 직원: 재수정안 수락 (counter_proposed 상태) */
    public function acceptCounter(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);

        $req = AttendanceChangeRequest::find($id, $storeId);
        if (!$req || (int)$req['store_member_id'] !== Auth::storeMemberId()) {
            flash('error', '본인의 요청만 처리할 수 있습니다.');
            redirect(url('employee'));
        }

        if ($req['status'] !== 'counter_proposed') {
            flash('error', '처리할 수 없는 상태입니다.');
            redirect(url('employee'));
        }

        // proposed_* 가 이미 counter_* 값으로 갱신되어 있으므로 기존 accept() 재사용
        AttendanceChangeRequest::accept($id, $storeId);
        flash('success', '재수정안을 수락했습니다.');
        redirect(url('employee'));
    }
}
