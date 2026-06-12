<?php
class LaborRiskController
{
    /** 점주: 노무 리스크 알림 목록. */
    public function index(): void
    {
        Auth::requireOwner();
        $ownerId = Auth::ownerId();

        $filters = [
            'severity' => $_GET['severity'] ?? '',
            'status'   => $_GET['status']   ?? 'active',
        ];

        $alerts = LaborRiskAlert::forOwner($ownerId, $filters, 60);
        $counts = LaborRiskAlert::countOpen($ownerId);

        render('labor_risk/index', [
            'title'   => '노무 리스크 알림',
            'alerts'  => $alerts,
            'counts'  => $counts,
            'filters' => $filters,
        ]);
    }

    /** 점주: 전체 직원·최근 90일 근무기록 리스크 재스캔. */
    public function scan(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('labor_risk'));
        }
        verify_csrf();
        $result = LaborRiskEngine::scanAllForOwner(Auth::ownerId(), Auth::storeId());
        flash('success', sprintf(
            '전체 스캔 완료: 직원 %d명, 최근 90일 근무기록 %d건 검사했습니다.',
            $result['member_count'], $result['log_count']
        ));
        redirect(url('labor_risk'));
    }

    public function acknowledge(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('labor_risk'));
        }
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        LaborRiskAlert::acknowledge($id, Auth::id());
        flash('success', '알림을 확인 처리했습니다.');
        redirect(url('labor_risk'));
    }

    public function resolve(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('labor_risk'));
        }
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        LaborRiskAlert::resolve($id, Auth::ownerId());
        flash('success', '알림을 해결 처리했습니다.');
        redirect(url('labor_risk'));
    }

    public function ignore(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('labor_risk'));
        }
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        LaborRiskAlert::ignore($id, Auth::ownerId());
        flash('success', '알림을 무시 처리했습니다.');
        redirect(url('labor_risk'));
    }

    /** 직원: 공개된 기록에 대한 확인/이의제기 응답. */
    public function employeeRespond(): void
    {
        Auth::requireLogin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employee'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = Auth::storeMemberId();
        $member   = StoreMember::find($memberId, $storeId);
        if (!$member) {
            redirect(url('employee'));
        }

        $responseType = ($_POST['response_type'] ?? '') === 'objection' ? 'objection' : 'acknowledged';
        $message      = trim((string) ($_POST['message'] ?? '')) ?: null;

        if ($responseType === 'objection' && !$message) {
            flash('error', '이의제기 사유를 입력하세요.');
            redirect(url('employee'));
        }

        EmployeeRecordResponse::create([
            'owner_id'        => (int) $member['owner_id'],
            'store_member_id' => $memberId,
            'related_type'    => $_POST['related_type'] ?? 'labor_risk_alert',
            'related_id'      => (int) ($_POST['related_id'] ?? 0),
            'response_type'   => $responseType,
            'message'         => $message,
        ]);

        flash(
            'success',
            $responseType === 'objection'
                ? '이의제기를 제출했습니다. 사장님이 확인 후 처리합니다.'
                : '확인 처리되었습니다.'
        );
        redirect(url('employee'));
    }
}
