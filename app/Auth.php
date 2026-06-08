<?php
/**
 * 인증 헬퍼. 세션 기반 로그인 상태 관리.
 * owner_id는 반드시 이 클래스를 통해서만 읽어야 합니다.
 * 프론트엔드 입력값에서 절대 owner_id를 받지 마세요.
 */
class Auth
{
    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['auth_user'] ?? null;
    }

    /** 현재 로그인 사용자 ID */
    public static function id(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /**
     * 데이터 격리용 owner_id.
     * 모든 Model 쿼리의 WHERE 조건에 반드시 사용해야 합니다.
     */
    public static function ownerId(): int
    {
        return (int) ($_SESSION['owner_id'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    /** 로그인 처리 — session_regenerate_id로 세션 고정 공격 방지 */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['owner_id'] = $user['id']; // owner_id = users.id
        $_SESSION['auth_user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    /** 로그인 안 된 경우 로그인 페이지로 강제 이동 */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect(url('auth', 'login'));
        }
    }
}
