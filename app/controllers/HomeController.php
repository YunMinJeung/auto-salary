<?php
class HomeController
{
    /** 서비스 소개 랜딩 페이지. 로그인 사용자는 역할별 대시보드로 리디렉트. */
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        render_landing('home/index', [
            'title' => '페이클락 — 알바 근태와 급여를 자동으로',
        ]);
    }

    private function redirectByRole(): void
    {
        if (Auth::isSuperAdmin()) {
            redirect(url('admin'));
        } elseif (Auth::isEmployee()) {
            redirect(url('employee'));
        } else {
            redirect(url());
        }
    }
}
