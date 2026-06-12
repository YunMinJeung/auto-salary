<?php
class WorkLogController
{
    public function index(): void
    {
        Auth::requireOwner();
        $employeeId = (int) ($_GET['employee_id'] ?? 0);
        $dateFrom   = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo     = $_GET['date_to']   ?? date('Y-m-d');
        $employees  = Employee::all();

        if ($employeeId) {
            $logs     = WorkLog::forEmployeePeriod($employeeId, $dateFrom, $dateTo);
            $employee = Employee::find($employeeId);
        } else {
            $logs     = WorkLog::forPeriodAll(Auth::ownerId(), $dateFrom, $dateTo);
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

    public function export(): void
    {
        Auth::requireOwner();
        $ownerId    = Auth::ownerId();
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $dateFrom   = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo     = $_GET['date_to']   ?? date('Y-m-d');

        $logs = WorkLog::exportForOwner($ownerId, $employeeId ?: null, $dateFrom, $dateTo);

        $filename = 'work_logs_' . $dateFrom . '_' . $dateTo . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

        $fp = fopen('php://output', 'w');
        fputcsv($fp, ['날짜', '요일', '직원명', '시작', '마감', '근무시간(분)', '휴게(분)', '유급(분)', '결근', '비고']);
        $calc = new PayrollCalculator();
        foreach ($logs as $log) {
            $wm = $pm = $bm = 0;
            if (!$log['is_absent'] && $log['start_time'] && $log['end_time']) {
                $wm = $calc->calculateWorkMinutes($log['work_date'], $log['start_time'], $log['end_time']);
                $bm = $log['break_auto'] ? $calc->calculateAutoBreakMinutes($wm) : (int)$log['break_minutes'];
                $pm = $calc->calculatePaidWorkMinutes($wm, $bm);
            }
            fputcsv($fp, [
                $log['work_date'],
                dayOfWeekKo($log['work_date']),
                $log['employee_name'] ?? '',
                $log['is_absent'] ? '' : substr($log['start_time'] ?? '', 0, 5),
                $log['is_absent'] ? '' : substr($log['end_time']   ?? '', 0, 5),
                $wm,
                $bm,
                $pm,
                $log['is_absent'] ? '결근' : '',
                $log['memo'] ?? '',
            ]);
        }
        fclose($fp);
        exit;
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
                $newLogId = DB::lastInsertId();
                $newLog   = WorkLog::find($newLogId);
                if ($newLog) {
                    $emp = Employee::find((int)($newLog['employee_id'] ?? 0));
                    if ($emp) {
                        LaborRiskEngine::detectForWorkLog($newLogId, $newLog, $emp, Auth::ownerId(), false, null);
                    }
                }
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
                // $log = 수정 전 상태, $_POST = 수정 후 데이터
                $emp = Employee::find((int)($log['employee_id'] ?? 0));
                if ($emp) {
                    LaborRiskEngine::detectForWorkLog($id, $_POST, $emp, Auth::ownerId(), true, $log);
                }
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
