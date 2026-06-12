<?php
class PrivacyController
{
    public function index(): void
    {
        render_landing('privacy/index', ['title' => '개인정보처리방침 — 페이클락']);
    }

    public function terms(): void
    {
        render_landing('privacy/terms', ['title' => '이용약관 — 페이클락']);
    }
}
