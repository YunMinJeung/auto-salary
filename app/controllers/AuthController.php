<?php
class AuthController
{
    public function login(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $email    = trim($_POST['email']    ?? '');
            $password = trim($_POST['password'] ?? '');

            if (!$email || !$password) {
                $errors[] = '이메일과 비밀번호를 입력하세요.';
            } else {
                $user = User::findByEmail($email);
                if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
                    $errors[] = '이메일 또는 비밀번호가 올바르지 않습니다.';
                } else {
                    Auth::login($user);
                    $this->setupStoreSession($user);
                    $this->redirectByRole();
                }
            }
        }

        render_auth('auth/login', [
            'title'  => '로그인',
            'errors' => $errors,
        ]);
    }

    public function register(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $old = [
                'business_name' => trim($_POST['business_name'] ?? ''),
                'name'          => trim($_POST['name']          ?? ''),
                'email'         => trim($_POST['email']         ?? ''),
            ];
            $password        = $_POST['password']        ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (!$old['business_name']) $errors[] = '사업장 이름을 입력하세요.';
            if (!$old['name'])          $errors[] = '담당자 이름을 입력하세요.';
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = '올바른 이메일을 입력하세요.';
            if (strlen($password) < 8)  $errors[] = '비밀번호는 8자 이상이어야 합니다.';
            if ($password !== $passwordConfirm) $errors[] = '비밀번호 확인이 일치하지 않습니다.';
            if (!$errors && User::emailExists($old['email'])) $errors[] = '이미 사용 중인 이메일입니다.';

            if (!$errors) {
                $userId = User::create([
                    'email'    => $old['email'],
                    'password' => $password,
                    'name'     => $old['name'],
                    'role'     => 'owner',
                ]);

                $user = User::find($userId);
                Auth::login($user);

                // 기본 설정 (payroll config)
                DB::query(
                    "INSERT INTO settings (owner_id, business_name) VALUES (?, ?)",
                    [$userId, $old['business_name']]
                );

                // 사업장 원장 생성
                $storeId = Store::create([
                    'owner_id'   => $userId,
                    'store_name' => $old['business_name'],
                ]);

                Auth::setStoreSession($storeId);
                redirect(url());
            }
        }

        render_auth('auth/register', [
            'title'  => '회원가입',
            'errors' => $errors,
            'old'    => $old,
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        redirect(url('auth', 'login'));
    }

    // ── private helpers ──────────────────────────────────────

    private function setupStoreSession(array $user): void
    {
        if (Auth::isOwner()) {
            $store = Store::findByOwner($user['id']);
            if ($store) {
                Auth::setStoreSession($store['id']);
            }
        } elseif (Auth::isEmployee()) {
            $member = StoreMember::findByUserId($user['id']);
            if ($member) {
                Auth::setStoreSession($member['store_id'], $member['id']);
            }
        }
    }

    private function redirectByRole(): void
    {
        if (Auth::isEmployee()) {
            redirect(url('employee'));
        } else {
            redirect(url());
        }
    }
}
