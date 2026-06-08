<?php
class SettingController
{
    public function index(): void
    {
        $settings   = Setting::get();
        $minWages   = MinimumWage::all();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Setting::update($settings['id'], $_POST);
            flash('success', '사업장 설정이 저장되었습니다.');
            redirect(url('settings'));
        }

        render('settings/index', [
            'title'    => '사업장 설정',
            'settings' => $settings,
            'minWages' => $minWages,
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
