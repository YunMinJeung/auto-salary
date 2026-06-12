<?php
class InviteController
{
    /** 점주: 초대 생성 폼 (GET) */
    public function form(): void
    {
        Auth::requireOwner();
        $storeId  = Auth::storeId();
        $memberId = (int)($_GET['store_member_id'] ?? 0);
        $member   = $memberId ? StoreMember::find($memberId, $storeId) : null;

        render('invitations/create', [
            'title'  => '직원 초대',
            'member' => $member,
        ]);
    }

    /** 점주: 초대 생성 (POST) */
    public function create(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('invite', 'form'));
        }
        verify_csrf();

        $storeId  = Auth::storeId();
        $memberId = (int)($_POST['store_member_id'] ?? 0);
        $member   = $memberId ? StoreMember::find($memberId, $storeId) : null;

        // 기존 직원 카드에서 왔고 이미 계정 연결된 경우 차단
        if ($member && $member['user_id']) {
            flash('error', '이미 앱 계정이 연결된 직원입니다.');
            redirect(url('members', 'edit', ['id' => $memberId]));
        }

        $name = trim($_POST['invited_name'] ?? ($member['name'] ?? ''));
        if (!$name) {
            flash('error', '직원 이름을 입력하세요.');
            redirect(url('invite', 'form') . ($memberId ? "&store_member_id={$memberId}" : ''));
        }

        $token = Invitation::create([
            'store_id'                  => $storeId,
            'store_member_id'           => $memberId ?: null,
            'invited_name'              => $name,
            'hourly_wage'               => $_POST['hourly_wage']           ?? ($member['hourly_wage'] ?? null),
            'weekly_contract_hours'     => $_POST['weekly_contract_hours'] ?? ($member['weekly_scheduled_hours'] ?? null),
            'weekly_contract_days'      => $_POST['weekly_contract_days']  ?? ($member['weekly_scheduled_days']  ?? null),
            'weekly_holiday_pay_enabled'=> (int)($_POST['weekly_holiday_pay_enabled'] ?? ($member['weekly_holiday_enabled'] ?? 1)),
            'hire_date'                 => $_POST['hire_date']             ?? ($member['employment_start_date'] ?? null),
            'invited_phone'             => trim($_POST['invited_phone']    ?? ''),
            'invited_email'             => trim($_POST['invited_email']    ?? ''),
            'created_by'                => Auth::id(),
        ]);

        $invLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                 . BASE_URL . 'index.php?c=invite&a=accept&token=' . $token;

        $_SESSION['last_invite_link']   = $invLink;
        $_SESSION['last_invite_name']   = $name;
        $_SESSION['last_invite_token']  = $token;
        $_SESSION['last_invite_member'] = $memberId;

        if ($memberId) {
            redirect(url('members', 'edit', ['id' => $memberId]));
        } else {
            redirect(url('invite', 'show'));
        }
    }

    /** 점주: 생성된 초대 링크/QR 표시 */
    public function show(): void
    {
        Auth::requireOwner();
        $link = $_SESSION['last_invite_link'] ?? null;
        $name = $_SESSION['last_invite_name'] ?? '';
        if (!$link) {
            redirect(url('members'));
        }

        render('invitations/show', [
            'title'     => '초대 링크 생성 완료',
            'invLink'   => $link,
            'invName'   => $name,
        ]);
    }

    /** 점주: 초대 취소 */
    public function cancel(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('members'));
        }
        verify_csrf();

        $invId    = (int)($_POST['invitation_id'] ?? 0);
        $memberId = (int)($_POST['store_member_id'] ?? 0);
        Invitation::cancel($invId, Auth::storeId());

        flash('success', '초대를 취소했습니다.');
        if ($memberId) {
            redirect(url('members', 'edit', ['id' => $memberId]));
        } else {
            redirect(url('members'));
        }
    }

    /** 알바: 초대 수락 페이지 (공개) */
    public function accept(): void
    {
        $token = trim($_GET['token'] ?? '');
        if (!$token) {
            redirect(url('auth', 'login'));
        }

        $inv = Invitation::findByToken($token);
        if (!$inv) {
            render_auth('invitations/invalid', ['title' => '유효하지 않은 초대']);
            return;
        }

        $errors = [];
        $mode   = 'choose';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $action = $_POST['action'] ?? '';

            if ($action === 'register') {
                $name     = trim($_POST['name']     ?? '');
                $email    = trim($_POST['email']    ?? '');
                $password = trim($_POST['password'] ?? '');

                if (!$name)                                           $errors[] = '이름을 입력하세요.';
                if (!filter_var($email, FILTER_VALIDATE_EMAIL))       $errors[] = '올바른 이메일을 입력하세요.';
                if (strlen($password) < 8)                            $errors[] = '비밀번호는 8자 이상이어야 합니다.';
                if (!$errors && User::emailExists($email))            $errors[] = '이미 사용 중인 이메일입니다.';

                if (!$errors) {
                    $userId = User::create(['email' => $email, 'password' => $password, 'name' => $name, 'role' => 'employee']);
                    Invitation::accept($token, $userId);
                    Auth::login(User::find($userId));
                    $this->finishEmployeeLogin($userId, $inv);
                }
                $mode = 'register';

            } elseif ($action === 'login') {
                $email    = trim($_POST['email']    ?? '');
                $password = trim($_POST['password'] ?? '');
                $user     = User::findByEmail($email);
                if (!$user || !User::verifyPassword($password, $user['password_hash'])) {
                    $errors[] = '이메일 또는 비밀번호가 올바르지 않습니다.';
                    $mode = 'login';
                } else {
                    Invitation::accept($token, $user['id']);
                    Auth::login($user);
                    $this->finishEmployeeLogin((int)$user['id'], $inv);
                }
            }
        }

        render_auth('invitations/accept', [
            'title'  => '초대 수락',
            'inv'    => $inv,
            'errors' => $errors,
            'mode'   => $mode,
            'token'  => $token,
        ]);
    }

    /**
     * 초대 수락 후 직원 로그인 마무리.
     * 소속 매장이 2개 이상이면 매장 선택 화면으로, 1개면 바로 해당 매장 세션 설정.
     */
    private function finishEmployeeLogin(int $userId, array $inv): void
    {
        $members = StoreMember::allByUserId($userId);

        if (count($members) > 1) {
            $_SESSION['pending_store_members'] = $members;
            redirect(url('employee', 'select_store'));
        }

        if (count($members) === 1) {
            Auth::setStoreSession((int)$members[0]['store_id'], (int)$members[0]['id']);
        }
        flash('success', h($inv['store_name']) . '에 합류했습니다!');
        redirect(url('employee'));
    }
}
