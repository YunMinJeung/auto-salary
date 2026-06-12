<?php
/**
 * 구 직원 관리 컨트롤러.
 * 모든 UI는 StoreMemberController(직원 관리)로 통합되었습니다.
 * 이 컨트롤러는 구 URL 진입점 호환성만 유지합니다.
 */
class EmployeeController
{
    public function index(): void
    {
        Auth::requireOwner();
        redirect(url('members'));
    }

    public function create(): void
    {
        Auth::requireOwner();
        redirect(url('members', 'create'));
    }

    public function edit(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $id      = (int)($_GET['id'] ?? 0);
        $emp     = Employee::find($id);

        if (!$emp) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('members'));
            return;
        }

        // 이미 연결된 경우 바로 이동
        if (!empty($emp['store_member_id'])) {
            redirect(url('members', 'edit', ['id' => $emp['store_member_id']]));
            return;
        }

        // 미연결 → store_members에 자동 마이그레이션 후 이동
        $memberId = StoreMember::create(array_merge($emp, [
            'store_id'  => $storeId,
            'user_id'   => null,
            'is_active' => 1,
            'is_minor'  => 0,
        ]));
        Employee::linkMember($id, $memberId);

        $checker  = new InsuranceEligibilityChecker();
        $judgment = $checker->checkAll($emp);
        EmployeeInsuranceSetting::save($storeId, $memberId, $emp, $judgment, Auth::id());

        redirect(url('members', 'edit', ['id' => $memberId]));
    }

    public function delete(): void
    {
        Auth::requireOwner();
        // 삭제는 StoreMemberController::delete()를 통해 처리
        redirect(url('members'));
    }
}
