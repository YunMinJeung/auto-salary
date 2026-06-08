<?php
class StoreMemberController
{
    public function index(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $members = StoreMember::allForStore($storeId);

        render('members/index', [
            'title'   => '직원 계정 관리',
            'members' => $members,
        ]);
    }

    public function create(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $errors  = [];
        $member  = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $post   = $_POST;
            $errors = $this->validate($post);

            if (!$errors) {
                $userId = null;

                // 계정 생성 요청이 있으면 users 테이블에 추가
                $email    = trim($post['user_email']    ?? '');
                $password = trim($post['user_password'] ?? '');

                if ($email && $password) {
                    if (User::emailExists($email)) {
                        $errors[] = '이미 사용 중인 이메일입니다.';
                    } elseif (strlen($password) < 4) {
                        $errors[] = '비밀번호는 4자 이상이어야 합니다.';
                    } else {
                        $userId = User::create([
                            'email'    => $email,
                            'password' => $password,
                            'name'     => trim($post['name']),
                            'role'     => 'employee',
                        ]);
                    }
                }

                if (!$errors) {
                    StoreMember::create(array_merge($post, [
                        'store_id'  => $storeId,
                        'user_id'   => $userId,
                        'is_active' => 1,
                    ]));
                    flash('success', '직원을 등록했습니다.');
                    redirect(url('members'));
                }
            }

            $member = $post;
        }

        $settings = Setting::get();
        render('members/form', [
            'title'   => '직원 등록',
            'action'  => 'create',
            'member'  => $member,
            'errors'  => $errors,
            'settings' => $settings,
        ]);
    }

    public function edit(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();
        $id      = (int)($_GET['id'] ?? 0);
        $member  = StoreMember::find($id, $storeId);

        if (!$member) {
            flash('error', '직원을 찾을 수 없습니다.');
            redirect(url('members'));
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $post   = $_POST;
            $errors = $this->validate($post);

            if (!$errors) {
                // 새 계정 생성 요청
                $email    = trim($post['user_email']    ?? '');
                $password = trim($post['user_password'] ?? '');

                if ($email && $password && !$member['user_id']) {
                    if (User::emailExists($email)) {
                        $errors[] = '이미 사용 중인 이메일입니다.';
                    } else {
                        $newUserId = User::create([
                            'email'    => $email,
                            'password' => $password,
                            'name'     => trim($post['name']),
                            'role'     => 'employee',
                        ]);
                        $post['user_id'] = $newUserId;
                    }
                }

                if (!$errors) {
                    StoreMember::update($id, $storeId, array_merge($post, [
                        'store_id' => $storeId,
                        'user_id'  => $post['user_id'] ?? $member['user_id'],
                    ]));
                    flash('success', '직원 정보를 수정했습니다.');
                    redirect(url('members'));
                }
            }

            $member = array_merge($member, $post);
        }

        $settings = Setting::get();
        render('members/form', [
            'title'   => '직원 수정',
            'action'  => 'edit',
            'member'  => $member,
            'errors'  => $errors,
            'settings' => $settings,
        ]);
    }

    public function delete(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('members'));
        }
        verify_csrf();

        $storeId = Auth::storeId();
        $id      = (int)($_POST['id'] ?? 0);
        $member  = StoreMember::find($id, $storeId);

        if ($member) {
            StoreMember::delete($id, $storeId);
            flash('success', $member['name'] . '님을 삭제했습니다.');
        }
        redirect(url('members'));
    }

    private function validate(array $post): array
    {
        $errors = [];
        if (empty(trim($post['name'] ?? ''))) {
            $errors[] = '이름을 입력하세요.';
        }
        if (empty($post['hourly_wage']) || (int)$post['hourly_wage'] < 1) {
            $errors[] = '시급을 입력하세요.';
        }
        return $errors;
    }
}
