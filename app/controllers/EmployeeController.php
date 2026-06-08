<?php
class EmployeeController
{
    public function index(): void
    {
        $employees = Employee::allIncludeRetired();
        render('employees/index', ['employees' => $employees, 'title' => '직원 관리']);
    }

    public function create(): void
    {
        $settings = Setting::get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST, $settings);

            if (empty($errors)) {
                Employee::create($_POST);
                flash('success', '직원이 등록되었습니다.');
                redirect(url('employees'));
            }

            render('employees/form', [
                'title'    => '직원 등록',
                'employee' => $_POST,
                'errors'   => $errors,
                'settings' => $settings,
                'action'   => 'create',
            ]);
            return;
        }

        render('employees/form', [
            'title'    => '직원 등록',
            'employee' => ['weekly_holiday_enabled' => 1, 'hourly_wage' => $settings['minimum_wage']],
            'errors'   => [],
            'settings' => $settings,
            'action'   => 'create',
        ]);
    }

    public function edit(): void
    {
        $id       = (int) ($_GET['id'] ?? 0);
        $employee = Employee::find($id);
        $settings = Setting::get();

        if (!$employee) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('employees'));
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $errors = $this->validate($_POST, $settings);

            if (empty($errors)) {
                Employee::update($id, $_POST);
                flash('success', '직원 정보가 수정되었습니다.');
                redirect(url('employees'));
            }

            $employee = array_merge($employee, $_POST);
        }

        render('employees/form', [
            'title'    => '직원 수정',
            'employee' => $employee,
            'errors'   => $errors ?? [],
            'settings' => $settings,
            'action'   => 'edit',
        ]);
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('employees'));
        }
        verify_csrf();
        $id = (int) ($_POST['id'] ?? 0);
        Employee::delete($id);
        flash('success', '직원이 삭제되었습니다.');
        redirect(url('employees'));
    }

    private function validate(array $data, array $settings): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = '이름을 입력하세요.';
        }

        $wage = (int) ($data['hourly_wage'] ?? 0);
        if ($wage <= 0) {
            $errors['hourly_wage'] = '시급은 0보다 커야 합니다.';
        } elseif ($wage < $settings['minimum_wage']) {
            $errors['hourly_wage'] = "2026년 최저시급({$settings['minimum_wage']}원) 미만입니다. 저장은 가능하지만 법적 위반 소지가 있습니다.";
        }

        if (empty($data['employment_start_date'])) {
            $errors['employment_start_date'] = '입사일을 입력하세요.';
        }

        $hours = (float) ($data['weekly_scheduled_hours'] ?? 0);
        if ($hours < 0) {
            $errors['weekly_scheduled_hours'] = '소정근로시간은 0 이상이어야 합니다.';
        }

        return $errors;
    }
}
