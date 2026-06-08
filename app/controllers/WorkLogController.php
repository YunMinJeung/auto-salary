<?php
class WorkLogController
{
    public function index(): void
    {
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $employees  = Employee::all();

        if ($employeeId) {
            $logs     = WorkLog::forEmployee($employeeId, 60);
            $employee = Employee::find($employeeId);
        } else {
            $logs     = WorkLog::recentAll(50);
            $employee = null;
        }

        render('work_logs/index', [
            'title'       => '근무 기록',
            'logs'        => $logs,
            'employees'   => $employees,
            'employee'    => $employee,
            'employeeId'  => $employeeId,
        ]);
    }

    public function create(): void
    {
        $employees = Employee::all();
        $settings  = Setting::get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST);

            if (empty($errors)) {
                WorkLog::create($_POST);
                flash('success', '근무 기록이 추가되었습니다.');
                $redirect = url('work_logs', 'index', ['employee_id' => (int)$_POST['employee_id']]);
                redirect($redirect);
            }

            render('work_logs/form', [
                'title'     => '근무 기록 추가',
                'log'       => $_POST,
                'employees' => $employees,
                'errors'    => $errors,
                'settings'  => $settings,
                'action'    => 'create',
            ]);
            return;
        }

        $prefill = [
            'employee_id' => (int) ($_GET['employee_id'] ?? 0),
            'work_date'   => $_GET['date'] ?? date('Y-m-d'),
            'break_auto'  => 1,
        ];

        render('work_logs/form', [
            'title'     => '근무 기록 추가',
            'log'       => $prefill,
            'employees' => $employees,
            'errors'    => [],
            'settings'  => $settings,
            'action'    => 'create',
        ]);
    }

    public function edit(): void
    {
        $id        = (int) ($_GET['id'] ?? 0);
        $log       = WorkLog::find($id);
        $employees = Employee::all();
        $settings  = Setting::get();

        if (!$log) {
            flash('error', '근무 기록을 찾을 수 없습니다.');
            redirect(url('work_logs'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST);

            if (empty($errors)) {
                WorkLog::update($id, $_POST);
                flash('success', '근무 기록이 수정되었습니다.');
                redirect(url('work_logs', 'index', ['employee_id' => (int)$_POST['employee_id']]));
            }

            $log = array_merge($log, $_POST);
        }

        render('work_logs/form', [
            'title'     => '근무 기록 수정',
            'log'       => $log,
            'employees' => $employees,
            'errors'    => $errors ?? [],
            'settings'  => $settings,
            'action'    => 'edit',
        ]);
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('work_logs'));
        }
        verify_csrf();
        $log = WorkLog::find((int)$_POST['id']);
        WorkLog::delete((int)$_POST['id']);
        flash('success', '근무 기록이 삭제되었습니다.');
        $empId = $log['employee_id'] ?? 0;
        redirect(url('work_logs', 'index', ['employee_id' => $empId]));
    }

    private function validate(array $data): array
    {
        $errors = [];

        if (empty($data['employee_id'])) {
            $errors['employee_id'] = '직원을 선택하세요.';
        }
        if (empty($data['work_date'])) {
            $errors['work_date'] = '근무일을 입력하세요.';
        }

        $isAbsent = isset($data['is_absent']);

        if (!$isAbsent) {
            if (empty($data['start_time'])) {
                $errors['start_time'] = '시작시간을 입력하세요.';
            }
            if (empty($data['end_time'])) {
                $errors['end_time'] = '마감시간을 입력하세요.';
            }

            if (empty($errors['start_time']) && empty($errors['end_time'])) {
                // 휴게시간 검증 (수동 입력 시)
                if (!isset($data['break_auto'])) {
                    $calc      = new PayrollCalculator();
                    $workMin   = $calc->calculateWorkMinutes($data['work_date'], $data['start_time'], $data['end_time']);
                    $breakMin  = (int)($data['break_minutes'] ?? 0);
                    if ($breakMin < 0) {
                        $errors['break_minutes'] = '휴게시간은 0 이상이어야 합니다.';
                    } elseif ($breakMin > $workMin) {
                        $errors['break_minutes'] = '휴게시간이 총 근무시간보다 클 수 없습니다.';
                    }
                }
            }
        }

        return $errors;
    }
}
