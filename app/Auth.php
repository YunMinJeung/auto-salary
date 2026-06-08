<?php
/**
 * 인증 헬퍼. 세션 기반 로그인 상태 관리.
 * owner_id / store_id는 반드시 이 클래스를 통해서만 읽어야 합니다.
 * 프론트엔드 입력값에서 절대 받지 마세요.
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

    public static function id(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    /** 점주 급여 데이터 격리용 owner_id */
    public static function ownerId(): int
    {
        return (int) ($_SESSION['owner_id'] ?? 0);
    }

    /** 사업장 ID (점주: 본인 store, 알바생: 소속 store) — 세션에 없으면 DB에서 lazy-load */
    public static function storeId(): int
    {
        if (empty($_SESSION['store_id']) && self::isOwner()) {
            $store = Store::findByOwner(self::ownerId());
            if ($store) $_SESSION['store_id'] = $store['id'];
        }
        return (int) ($_SESSION['store_id'] ?? 0);
    }

    /** 알바생 본인의 store_members.id */
    public static function storeMemberId(): int
    {
        return (int) ($_SESSION['store_member_id'] ?? 0);
    }

    public static function isAdmin(): bool
    {
        return (self::user()['role'] ?? '') === 'admin';
    }

    public static function isOwner(): bool
    {
        $role = self::user()['role'] ?? '';
        return $role === 'owner' || $role === 'admin';
    }

    public static function isEmployee(): bool
    {
        return (self::user()['role'] ?? '') === 'employee';
    }

    /** 로그인 처리 — session_regenerate_id로 세션 고정 공격 방지 */
    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['owner_id'] = $user['id'];
        $_SESSION['auth_user'] = [
            'id'    => $user['id'],
            'name'  => $user['name'],
            'email' => $user['email'],
            'role'  => $user['role'],
        ];
    }

    /** 로그인 후 사업장 세션 설정 (AuthController에서 호출) */
    public static function setStoreSession(int $storeId, ?int $storeMemberId = null): void
    {
        $_SESSION['store_id'] = $storeId;
        if ($storeMemberId !== null) {
            $_SESSION['store_member_id'] = $storeMemberId;
        }
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

    public static function requireLogin(): void
    {
        if (!self::check()) {
            redirect(url('auth', 'login'));
        }
    }

    public static function requireOwner(): void
    {
        self::requireLogin();
        if (!self::isOwner()) {
            redirect(url('employee'));
        }
    }

    public static function requireEmployee(): void
    {
        self::requireLogin();
        if (!self::isEmployee()) {
            redirect(url());
        }
    }
}
