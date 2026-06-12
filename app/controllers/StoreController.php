<?php
class StoreController
{
    public function create(): void
    {
        Auth::requireOwner();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();

            $name    = trim($_POST['store_name'] ?? '');
            $type    = $_POST['employee_count_type'] ?? 'under5';
            $minWage = (int)($_POST['minimum_wage'] ?? DEFAULT_MIN_WAGE);

            if (!$name) {
                flash('error', '매장명을 입력하세요.');
                redirect(url('store', 'create'));
            }

            if (!in_array($type, ['under5', 'over5'], true)) {
                $type = 'under5';
            }

            $storeId = Store::create([
                'owner_id'            => Auth::ownerId(),
                'store_name'          => $name,
                'employee_count_type' => $type,
                'minimum_wage'        => $minWage ?: DEFAULT_MIN_WAGE,
            ]);

            Auth::switchStore($storeId);
            flash('success', '매장을 추가했습니다. 현재 매장이 전환됐습니다.');
            redirect(url());
        }

        render('stores/create', ['title' => '매장 추가']);
    }

    public function switch(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url());
        }
        verify_csrf();

        $storeId = (int)($_POST['store_id'] ?? 0);
        if (!Auth::switchStore($storeId)) {
            flash('error', '매장 전환에 실패했습니다.');
        }
        redirect(url());
    }
}
