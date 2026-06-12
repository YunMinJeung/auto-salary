<?php
class SignupController
{
    /** 가입 유형 선택 (/signup) */
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        render_landing('signup/index', [
            'title' => '회원가입 — 페이클락',
        ]);
    }

    /** 사장 회원가입 (/signup/owner) — 2단계 폼 */
    public function owner(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $old = [
                'name'                => trim($_POST['name']                ?? ''),
                'email'               => trim($_POST['email']               ?? ''),
                'phone'               => trim($_POST['phone']               ?? ''),
                'store_name'          => trim($_POST['store_name']          ?? ''),
                'business_number'     => trim($_POST['business_number']     ?? ''),
                'representative_name' => trim($_POST['representative_name']  ?? ''),
                'address'             => trim($_POST['address']             ?? ''),
                'employee_count'      => trim($_POST['employee_count']      ?? ''),
                'five_or_more'        => trim($_POST['five_or_more']        ?? ''),
                'pay_day'             => trim($_POST['pay_day']             ?? ''),
                'hourly_wage'         => trim($_POST['hourly_wage']         ?? ''),
                'marketing_agreed'    => !empty($_POST['marketing_agreed']) ? 1 : 0,
            ];
            $password        = $_POST['password']         ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            // ── 계정 정보 검증 ──
            if (!$old['name'])                                       $errors[] = '이름을 입력하세요.';
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))   $errors[] = '올바른 이메일을 입력하세요.';
            if (strlen($password) < 8)                               $errors[] = '비밀번호는 8자 이상이어야 합니다.';
            if ($password !== $passwordConfirm)                      $errors[] = '비밀번호 확인이 일치하지 않습니다.';
            // ── 사업장 정보 검증 ──
            if (!$old['store_name'])                                 $errors[] = '사업장명을 입력하세요.';
            if (empty($_POST['agree_terms']) || empty($_POST['agree_privacy'])) $errors[] = '필수 약관에 동의해 주세요.';
            if (!$errors && User::emailExists($old['email']))        $errors[] = '이미 사용 중인 이메일입니다.';

            if (!$errors) {
                $userId = User::create([
                    'email'    => $old['email'],
                    'password' => $password,
                    'name'     => $old['name'],
                    'role'     => 'owner',
                ]);
                if ($old['phone']) {
                    DB::query('UPDATE users SET phone = ? WHERE id = ?', [$old['phone'], $userId]);
                }
                DB::query('UPDATE users SET marketing_agreed=? WHERE id=?', [$old['marketing_agreed'], $userId]);

                $user = User::find($userId);
                Auth::login($user);

                // 5인 이상 여부 → employee_count_type (모름은 보수적으로 under5)
                $countType = $old['five_or_more'] === '예' ? 'over5' : 'under5';
                $minWage   = $old['hourly_wage'] !== '' ? (int)$old['hourly_wage'] : DEFAULT_MIN_WAGE;

                $storeId = Store::create([
                    'owner_id'            => $userId,
                    'store_name'          => $old['store_name'],
                    'business_number'     => $old['business_number']     ?: null,
                    'representative_name' => $old['representative_name'] ?: null,
                    'address'             => $old['address']             ?: null,
                    'employee_count'      => $old['employee_count'] !== '' ? (int)$old['employee_count'] : null,
                    'pay_day'             => $old['pay_day'] !== '' ? (int)$old['pay_day'] : null,
                    'employee_count_type' => $countType,
                    'minimum_wage'        => $minWage,
                ]);

                // 기본 설정 생성 (settings)
                DB::query(
                    "INSERT INTO settings (owner_id, store_id, business_name, employee_count_type, minimum_wage_year, minimum_wage)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$userId, $storeId, $old['store_name'], $countType, (int)date('Y'), $minWage]
                );

                Auth::setStoreSession($storeId);
                flash('success', '사업장이 생성되었습니다. 이제 직원을 초대해 보세요.');
                redirect(url());
            }
        }

        render_landing('signup/owner', [
            'title'  => '사업장 만들기 — 페이클락',
            'errors' => $errors,
            'old'    => $old,
        ]);
    }

    /** 알바 회원가입 (/signup/employee) */
    public function employee(): void
    {
        if (Auth::check()) {
            $this->redirectByRole();
        }

        $errors = [];
        $old    = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $old = [
                'name'  => trim($_POST['name']  ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'marketing_agreed' => !empty($_POST['marketing_agreed']) ? 1 : 0,
            ];
            $password        = $_POST['password']         ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (!$old['name'])                                       $errors[] = '이름을 입력하세요.';
            if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL))   $errors[] = '올바른 이메일을 입력하세요.';
            if (strlen($password) < 8)                               $errors[] = '비밀번호는 8자 이상이어야 합니다.';
            if ($password !== $passwordConfirm)                      $errors[] = '비밀번호 확인이 일치하지 않습니다.';
            if (empty($_POST['agree_terms']) || empty($_POST['agree_privacy'])) $errors[] = '필수 약관에 동의해 주세요.';
            if (!$errors && User::emailExists($old['email']))        $errors[] = '이미 사용 중인 이메일입니다.';

            if (!$errors) {
                $userId = User::create([
                    'email'    => $old['email'],
                    'password' => $password,
                    'name'     => $old['name'],
                    'role'     => 'employee',
                ]);
                if ($old['phone']) {
                    DB::query('UPDATE users SET phone = ? WHERE id = ?', [$old['phone'], $userId]);
                }
                DB::query('UPDATE users SET marketing_agreed=? WHERE id=?', [$old['marketing_agreed'], $userId]);

                Auth::login(User::find($userId));
                redirect(url('employee'));
            }
        }

        render_landing('signup/employee', [
            'title'  => '알바로 가입하기 — 페이클락',
            'errors' => $errors,
            'old'    => $old,
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
