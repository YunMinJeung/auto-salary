<?php
class SettingController
{
    public function index(): void
    {
        $settings = Setting::get();
        $minWages = MinimumWage::all();
        $store    = Store::findOwned(Auth::storeId(), Auth::ownerId());

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Setting::update($settings['id'], $_POST);
            // 공지사항은 stores 테이블에 저장
            if ($store) {
                $allowedVisibility = ['HOURS_ONLY', 'ESTIMATED_TOTAL_ONLY', 'ESTIMATED_WITH_BREAKDOWN'];
                $visibility = $_POST['employee_pay_visibility'] ?? 'ESTIMATED_TOTAL_ONLY';
                if (!in_array($visibility, $allowedVisibility, true)) {
                    $visibility = 'ESTIMATED_TOTAL_ONLY';
                }
                // MVP: 월급(MONTHLY)만 허용. 미지원 값은 MONTHLY로 강제.
                $payPeriodType = in_array($_POST['business_pay_period_type'] ?? '', Payslip::SUPPORTED_PERIOD_TYPES, true)
                    ? $_POST['business_pay_period_type']
                    : 'MONTHLY';
                $whPolicy = in_array($_POST['weekly_holiday_pay_policy'] ?? '', ['AUTO_CHECK', 'MANUAL_CHECK'], true)
                    ? $_POST['weekly_holiday_pay_policy']
                    : 'AUTO_CHECK';
                $lat = trim($_POST['latitude']  ?? '');
                $lng = trim($_POST['longitude'] ?? '');
                Store::update($store['id'], Auth::ownerId(), [
                    'store_name'               => $store['store_name'],
                    'employee_count_type'      => $store['employee_count_type'],
                    'minimum_wage'             => $store['minimum_wage'],
                    'notice'                   => trim($_POST['notice'] ?? '') ?: null,
                    'employee_pay_visibility'  => $visibility,
                    'business_pay_period_type' => $payPeriodType,
                    'business_category'        => trim($_POST['business_category']     ?? '') ?: null,
                    'business_phone'           => trim($_POST['business_phone']        ?? '') ?: null,
                    'payroll_manager_name'     => trim($_POST['payroll_manager_name']  ?? '') ?: null,
                    'payroll_manager_phone'    => trim($_POST['payroll_manager_phone'] ?? '') ?: null,
                    'weekly_holiday_pay_policy' => $whPolicy,
                    'latitude'                 => $lat !== '' ? (float)$lat : null,
                    'longitude'                => $lng !== '' ? (float)$lng : null,
                    'gps_radius'               => max(50, (int)($_POST['gps_radius'] ?? 200)),
                    'gps_required'             => isset($_POST['gps_required']) ? 1 : 0,
                ]);
            }
            flash('success', '사업장 설정이 저장되었습니다.');
            redirect(url('settings'));
        }

        render('settings/index', [
            'title'    => '사업장 설정',
            'settings' => $settings,
            'minWages' => $minWages,
            'store'    => $store,
        ]);
    }

    /** 최저시급 연도별 데이터 저장 (추가 + 수정 공용) */
    public function minWageSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('settings'));
        }
        verify_csrf();

        $year = (int) ($_POST['year'] ?? 0);
        if ($year < 2000 || $year > 2100) {
            flash('error', '올바른 연도를 입력하세요.');
            redirect(url('settings'));
        }

        $hourly = (int) ($_POST['hourly_wage'] ?? 0);
        if ($hourly < 1) {
            flash('error', '시급을 입력하세요.');
            redirect(url('settings'));
        }

        MinimumWage::save($_POST);
        flash('success', "{$year}년 최저시급을 저장했습니다.");
        redirect(url('settings'));
    }

    /** 최저시급 연도 삭제 */
    public function minWageDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('settings'));
        }
        verify_csrf();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            MinimumWage::delete($id);
            flash('success', '삭제했습니다.');
        }
        redirect(url('settings'));
    }
}
