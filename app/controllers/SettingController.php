<?php
class SettingController
{
    public function index(): void
    {
        $settings = Setting::get();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            Setting::update($settings['id'], $_POST);
            flash('success', '사업장 설정이 저장되었습니다.');
            redirect(url('settings'));
        }

        render('settings/index', ['settings' => $settings, 'title' => '사업장 설정']);
    }
}
