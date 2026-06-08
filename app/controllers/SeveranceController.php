<?php
class SeveranceController
{
    public function index(): void
    {
        $employees  = Employee::allIncludeRetired();
        $settings   = Setting::get();
        $result     = null;
        $employee   = null;

        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $retireDate = $_GET['retire_date'] ?? date('Y-m-d');

        if ($employeeId) {
            $employee = Employee::find($employeeId);
            if (!$employee) {
                flash('error', '직원을 찾을 수 없습니다.');
                redirect(url('severance'));
            }
            // 퇴직일 기본값: 퇴사일이 있으면 그것, 없으면 오늘
            if (!$_GET['retire_date'] ?? false) {
                $retireDate = $employee['employment_end_date'] ?? date('Y-m-d');
            }
            $calc   = new SeveranceCalculator();
            $result = $calc->calculate($employee, $retireDate, $settings);
        }

        render('severance/index', [
            'title'      => '퇴직금 계산',
            'employees'  => $employees,
            'employeeId' => $employeeId,
            'retireDate' => $retireDate,
            'employee'   => $employee,
            'result'     => $result,
            'settings'   => $settings,
        ]);
    }
}
