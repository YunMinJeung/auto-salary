<?php
class AdminController
{
    public function index(): void
    {
        Auth::requireSuperAdmin();

        $stats = [
            'total_stores'    => AdminStore::totalCount(),
            'active_stores'   => AdminStore::activeCount(),
            'total_owners'    => AdminUser::countByRole('owner'),
            'total_employees' => AdminUser::countByRole('employee'),
            'today_attendance'=> $this->todayAttendanceCount(),
            'open_tickets'    => SupportTicket::countOpen(),
        ];
        $recentStores  = AdminStore::recentlyJoined(5);
        $recentTickets = SupportTicket::recent(5);

        render('admin/home', [
            'title'         => '관리자 홈',
            'stats'         => $stats,
            'recentStores'  => $recentStores,
            'recentTickets' => $recentTickets,
        ], 'admin_layout');
    }

    public function businesses(): void
    {
        Auth::requireSuperAdmin();
        $filters = [
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $stores = AdminStore::list($filters, 100);
        render('admin/businesses', [
            'title'   => '사업장 관리',
            'stores'  => $stores,
            'filters' => $filters,
        ], 'admin_layout');
    }

    public function businessDetail(): void
    {
        Auth::requireSuperAdmin();
        $id    = (int)($_GET['id'] ?? 0);
        $store = AdminStore::find($id);
        if (!$store) {
            flash('error', '사업장을 찾을 수 없습니다.');
            redirect(url('admin', 'businesses'));
        }
        $members    = AdminStore::members($id);
        $workLogs   = AdminStore::recentWorkLogs($id, 20);
        $riskAlerts = DB::fetchAll(
            "SELECT lra.*, sm.name AS employee_name
               FROM labor_risk_alerts lra
               LEFT JOIN store_members sm ON sm.id = lra.store_member_id
              WHERE lra.owner_id = (SELECT owner_id FROM stores WHERE id = ?)
                AND lra.status IN ('open','acknowledged')
              ORDER BY FIELD(lra.severity,'danger','warning','info'), lra.created_at DESC
              LIMIT 20",
            [$id]
        );
        $auditLogs = AuditLog::forTarget('store', $id);
        render('admin/business_detail', [
            'title'      => '사업장 상세 — ' . $store['store_name'],
            'store'      => $store,
            'members'    => $members,
            'workLogs'   => $workLogs,
            'riskAlerts' => $riskAlerts,
            'auditLogs'  => $auditLogs,
        ], 'admin_layout');
    }

    public function businessUpdate(): void
    {
        Auth::requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin', 'businesses'));
        verify_csrf();
        $id     = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($action === 'status' && !empty($_POST['status'])) {
            AdminStore::updateStatus($id, $_POST['status'], $reason);
            flash('success', '상태를 변경했습니다.');
        } elseif ($action === 'plan' && !empty($_POST['plan'])) {
            AdminStore::updatePlan($id, $_POST['plan'], $reason);
            flash('success', '요금제를 변경했습니다.');
        } elseif ($action === 'memo') {
            AdminStore::updateMemo($id, $_POST['admin_memo'] ?? '');
            flash('success', '메모를 저장했습니다.');
        }
        redirect(url('admin', 'business_detail', ['id' => $id]));
    }

    public function users(): void
    {
        Auth::requireSuperAdmin();
        $filters = [
            'role'   => $_GET['role']   ?? '',
            'search' => $_GET['search'] ?? '',
        ];
        $users = AdminUser::list($filters, 100);
        render('admin/users', [
            'title'   => '사용자 관리',
            'users'   => $users,
            'filters' => $filters,
        ], 'admin_layout');
    }

    public function userDetail(): void
    {
        Auth::requireSuperAdmin();
        $id   = (int)($_GET['id'] ?? 0);
        $user = AdminUser::find($id);
        if (!$user) {
            flash('error', '사용자를 찾을 수 없습니다.');
            redirect(url('admin', 'users'));
        }
        $auditLogs = AuditLog::forTarget('user', $id);
        render('admin/user_detail', [
            'title'     => '사용자 상세 — ' . ($user['name'] ?? ''),
            'user'      => $user,
            'auditLogs' => $auditLogs,
        ], 'admin_layout');
    }

    public function userUpdate(): void
    {
        Auth::requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin', 'users'));
        verify_csrf();
        $id     = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($action === 'suspend') {
            AdminUser::suspend($id, $reason);
            flash('success', '계정을 정지했습니다.');
        } elseif ($action === 'reactivate') {
            AdminUser::reactivate($id, $reason);
            flash('success', '계정을 재활성화했습니다.');
        } elseif ($action === 'memo') {
            AdminUser::updateMemo($id, $_POST['admin_memo'] ?? '');
            flash('success', '메모를 저장했습니다.');
        }
        redirect(url('admin', 'user_detail', ['id' => $id]));
    }

    public function logs(): void
    {
        Auth::requireSuperAdmin();
        $filters = [
            'type'      => $_GET['type']      ?? '',
            'store_id'  => $_GET['store_id']  ?? '',
            'date_from' => $_GET['date_from'] ?? date('Y-m-d', strtotime('-7 days')),
            'date_to'   => $_GET['date_to']   ?? date('Y-m-d'),
            'is_success'=> $_GET['is_success'] ?? '',
        ];
        $logs   = SystemLog::list($filters, 200);
        $stores = DB::fetchAll("SELECT id, store_name FROM stores ORDER BY store_name ASC");
        render('admin/logs', [
            'title'   => '시스템 로그',
            'logs'    => $logs,
            'filters' => $filters,
            'stores'  => $stores,
        ], 'admin_layout');
    }

    public function audit(): void
    {
        Auth::requireSuperAdmin();
        $logs = AuditLog::recent(100);
        render('admin/audit', [
            'title' => 'Audit Log',
            'logs'  => $logs,
        ], 'admin_layout');
    }

    public function tickets(): void
    {
        Auth::requireSuperAdmin();
        $filters = ['status' => $_GET['status'] ?? ''];
        $tickets = SupportTicket::allForAdmin($filters, 100);
        render('admin/tickets', [
            'title'   => '문의/피드백',
            'tickets' => $tickets,
            'filters' => $filters,
        ], 'admin_layout');
    }

    public function ticketDetail(): void
    {
        Auth::requireSuperAdmin();
        $id     = (int)($_GET['id'] ?? 0);
        $ticket = SupportTicket::find($id);
        if (!$ticket) {
            flash('error', '문의를 찾을 수 없습니다.');
            redirect(url('admin', 'tickets'));
        }
        render('admin/ticket_detail', [
            'title'  => '문의 상세 — ' . ($ticket['title'] ?? ''),
            'ticket' => $ticket,
        ], 'admin_layout');
    }

    public function ticketUpdate(): void
    {
        Auth::requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin', 'tickets'));
        verify_csrf();
        $id     = (int)($_POST['id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($action === 'reply') {
            SupportTicket::reply($id, $_POST['admin_reply'] ?? '', $_POST['admin_memo'] ?? '');
            flash('success', '답변을 저장했습니다.');
        } elseif ($action === 'status' && !empty($_POST['status'])) {
            SupportTicket::updateStatus($id, $_POST['status']);
            flash('success', '상태를 변경했습니다.');
        }
        redirect(url('admin', 'ticket_detail', ['id' => $id]));
    }

    public function billing(): void
    {
        Auth::requireSuperAdmin();
        $subs = DB::fetchAll(
            "SELECT sub.*, s.store_name, u.name AS owner_name
               FROM subscriptions sub
               LEFT JOIN stores s ON s.id = sub.store_id
               LEFT JOIN users u  ON u.id = s.owner_id
              ORDER BY sub.updated_at DESC"
        );
        render('admin/billing', [
            'title' => '구독/결제 관리',
            'subs'  => $subs,
        ], 'admin_layout');
    }

    public function billingUpdate(): void
    {
        Auth::requireSuperAdmin();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(url('admin', 'billing'));
        verify_csrf();
        $storeId = (int)($_POST['store_id'] ?? 0);
        $plan    = $_POST['plan']   ?? '';
        $status  = $_POST['status'] ?? '';
        $reason  = trim($_POST['reason'] ?? '');

        $before = DB::fetchOne("SELECT * FROM subscriptions WHERE store_id = ?", [$storeId]);
        DB::query(
            "INSERT INTO subscriptions (store_id, plan, status) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE plan = VALUES(plan), status = VALUES(status)",
            [$storeId, $plan, $status]
        );
        AuditLog::record('SUBSCRIPTION_CHANGE', 'store', $storeId,
            $before ? ['plan' => $before['plan'], 'status' => $before['status']] : null,
            ['plan' => $plan, 'status' => $status],
            $reason
        );
        flash('success', '구독 정보를 변경했습니다.');
        redirect(url('admin', 'billing'));
    }

    public function standards(): void
    {
        Auth::requireSuperAdmin();
        $standards = CalcStandard::all();
        render('admin/standards', [
            'title'     => '계산 기준 관리',
            'standards' => $standards,
        ], 'admin_layout');
    }

    public function standardForm(): void
    {
        Auth::requireSuperAdmin();
        $id       = (int)($_GET['id'] ?? 0);
        $standard = $id ? CalcStandard::find($id) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            if ($id && $standard) {
                CalcStandard::update($id, $_POST);
                flash('success', '계산 기준을 수정했습니다.');
            } else {
                CalcStandard::create($_POST);
                flash('success', '계산 기준을 추가했습니다.');
            }
            redirect(url('admin', 'standards'));
        }

        render('admin/standard_form', [
            'title'    => $id ? '계산 기준 수정' : '계산 기준 추가',
            'standard' => $standard,
        ], 'admin_layout');
    }

    private function todayAttendanceCount(): int
    {
        $r = DB::fetchOne(
            "SELECT COUNT(*) AS cnt FROM attendance_logs WHERE DATE(created_at) = CURDATE()"
        );
        return (int)($r['cnt'] ?? 0);
    }
}
