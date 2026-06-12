<?php
class LeaveController
{
    /** 전체 직원 연차 현황 + 최근 휴가 기록. */
    public function index(): void
    {
        Auth::requireOwner();
        $ownerId   = Auth::ownerId();
        $employees = Employee::all();

        $balances = [];
        $records  = [];
        foreach ($employees as $emp) {
            $empId = (int) $emp['id'];
            $balances[$empId] = LeaveRecord::annualBalance($ownerId, $empId);
            foreach (LeaveRecord::allForEmployee($ownerId, $empId) as $r) {
                $r['employee_name'] = $emp['name'];
                $records[] = $r;
            }
        }
        // 최근순 정렬
        usort($records, fn($a, $b) => strcmp($b['start_date'], $a['start_date']));

        render('leaves/index', [
            'title'     => '연차/휴가',
            'employees' => $employees,
            'balances'  => $balances,
            'records'   => array_slice($records, 0, 50),
        ]);
    }

    public function create(): void
    {
        Auth::requireOwner();
        $employees = Employee::all();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST);
            if (empty($errors)) {
                LeaveRecord::create($_POST);
                flash('success', '휴가 기록이 추가되었습니다.');
                redirect(url('leaves'));
            }
            render('leaves/form', [
                'title'     => '휴가 기록 추가',
                'leave'     => $_POST,
                'employees' => $employees,
                'errors'    => $errors,
                'action'    => 'create',
            ]);
            return;
        }

        $prefill = [
            'employee_id' => (int) ($_GET['employee_id'] ?? 0),
            'leave_type'  => 'annual',
            'start_date'  => date('Y-m-d'),
            'end_date'    => date('Y-m-d'),
            'days'        => 1.0,
            'status'      => 'approved',
        ];
        render('leaves/form', [
            'title'     => '휴가 기록 추가',
            'leave'     => $prefill,
            'employees' => $employees,
            'errors'    => [],
            'action'    => 'create',
        ]);
    }

    public function edit(): void
    {
        Auth::requireOwner();
        $id        = (int) ($_GET['id'] ?? 0);
        $leave     = LeaveRecord::find($id, Auth::ownerId());
        $employees = Employee::all();

        if (!$leave) {
            flash('error', '휴가 기록을 찾을 수 없습니다.');
            redirect(url('leaves'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST);
            if (empty($errors)) {
                LeaveRecord::update($id, Auth::ownerId(), $_POST);
                flash('success', '휴가 기록이 수정되었습니다.');
                redirect(url('leaves'));
            }
            $leave = array_merge($leave, $_POST);
        }

        render('leaves/form', [
            'title'     => '휴가 기록 수정',
            'leave'     => $leave,
            'employees' => $employees,
            'errors'    => $errors ?? [],
            'action'    => 'edit',
        ]);
    }

    public function delete(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('leaves'));
        }
        verify_csrf();
        LeaveRecord::delete((int) ($_POST['id'] ?? 0), Auth::ownerId());
        flash('success', '휴가 기록이 삭제되었습니다.');
        redirect(url('leaves'));
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['employee_id'])) {
            $errors['employee_id'] = '직원을 선택하세요.';
        }
        if (empty($data['start_date'])) {
            $errors['start_date'] = '시작일을 입력하세요.';
        }
        if (empty($data['end_date'])) {
            $errors['end_date'] = '종료일을 입력하세요.';
        }
        if (!empty($data['start_date']) && !empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $errors['end_date'] = '종료일은 시작일 이후여야 합니다.';
        }
        if (isset($data['days']) && (float) $data['days'] < 0) {
            $errors['days'] = '일수는 0 이상이어야 합니다.';
        }
        return $errors;
    }
}
