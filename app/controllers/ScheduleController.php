<?php
class ScheduleController
{
    /** 주간 근무표 + 예정 인건비. */
    public function index(): void
    {
        Auth::requireOwner();
        $weekDate = $_GET['week_date'] ?? date('Y-m-d');
        [$weekStart] = getWeekRange($weekDate);

        $schedules = Schedule::allForWeek(Auth::ownerId(), Auth::storeId(), $weekStart);
        $estimate  = Schedule::estimatedWeeklyPayroll(Auth::ownerId(), Auth::storeId(), $weekStart);
        $employees = Employee::all();

        render('schedules/index', [
            'title'     => '근무표',
            'weekStart' => $weekStart,
            'schedules' => $schedules,
            'estimate'  => $estimate,
            'employees' => $employees,
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
                Schedule::create($_POST);
                flash('success', '근무표 일정이 추가되었습니다.');
                [$weekStart] = getWeekRange($_POST['schedule_date']);
                redirect(url('schedules', 'index', ['week_date' => $weekStart]));
            }
            render('schedules/form', [
                'title'     => '근무 일정 추가',
                'schedule'  => $_POST,
                'employees' => $employees,
                'errors'    => $errors,
                'action'    => 'create',
            ]);
            return;
        }

        $prefill = [
            'employee_id'   => (int) ($_GET['employee_id'] ?? 0),
            'schedule_date' => $_GET['date'] ?? date('Y-m-d'),
            'break_minutes' => 0,
        ];
        render('schedules/form', [
            'title'     => '근무 일정 추가',
            'schedule'  => $prefill,
            'employees' => $employees,
            'errors'    => [],
            'action'    => 'create',
        ]);
    }

    public function edit(): void
    {
        Auth::requireOwner();
        $id        = (int) ($_GET['id'] ?? 0);
        $schedule  = Schedule::find($id, Auth::ownerId());
        $employees = Employee::all();

        if (!$schedule) {
            flash('error', '근무 일정을 찾을 수 없습니다.');
            redirect(url('schedules'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST);
            if (empty($errors)) {
                Schedule::update($id, Auth::ownerId(), $_POST);
                flash('success', '근무 일정이 수정되었습니다.');
                [$weekStart] = getWeekRange($_POST['schedule_date']);
                redirect(url('schedules', 'index', ['week_date' => $weekStart]));
            }
            $schedule = array_merge($schedule, $_POST);
        }

        render('schedules/form', [
            'title'     => '근무 일정 수정',
            'schedule'  => $schedule,
            'employees' => $employees,
            'errors'    => $errors ?? [],
            'action'    => 'edit',
        ]);
    }

    public function delete(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('schedules'));
        }
        verify_csrf();
        $id       = (int) ($_POST['id'] ?? 0);
        $schedule = Schedule::find($id, Auth::ownerId());
        Schedule::delete($id, Auth::ownerId());
        flash('success', '근무 일정이 삭제되었습니다.');
        $weekDate = $schedule['schedule_date'] ?? date('Y-m-d');
        [$weekStart] = getWeekRange($weekDate);
        redirect(url('schedules', 'index', ['week_date' => $weekStart]));
    }

    private function validate(array $data): array
    {
        $errors = [];
        if (empty($data['employee_id'])) {
            $errors['employee_id'] = '직원을 선택하세요.';
        }
        if (empty($data['schedule_date'])) {
            $errors['schedule_date'] = '날짜를 입력하세요.';
        }
        if (empty($data['start_time'])) {
            $errors['start_time'] = '시작시간을 입력하세요.';
        }
        if (empty($data['end_time'])) {
            $errors['end_time'] = '종료시간을 입력하세요.';
        }
        return $errors;
    }
}
