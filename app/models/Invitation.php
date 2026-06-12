<?php
class Invitation
{
    /**
     * 초대 생성.
     * $data 키: store_id, invited_name, token(선택), store_member_id(선택, 기존 직원 연결용),
     *           hourly_wage, weekly_contract_hours, weekly_contract_days,
     *           weekly_holiday_pay_enabled, hire_date, invited_phone, invited_email, created_by
     */
    public static function create(array $data): string
    {
        // 평문 토큰은 URL(링크)에만 노출, DB에는 sha256 해시만 저장 (QR 토큰과 동일 패턴).
        $rawToken = bin2hex(random_bytes(32));
        $token    = hash('sha256', $rawToken);
        DB::query(
            "INSERT INTO employee_invitations
               (store_id, store_member_id, invited_name,
                hourly_wage, weekly_contract_hours, weekly_contract_days,
                weekly_holiday_pay_enabled, hire_date, invited_phone, invited_email,
                token, expires_at, created_by_user_id)
             VALUES (?,?,?,?,?,?,?,?,?,?,?, DATE_ADD(NOW(), INTERVAL 7 DAY),?)",
            [
                (int)$data['store_id'],
                $data['store_member_id'] ? (int)$data['store_member_id'] : null,
                $data['invited_name'],
                $data['hourly_wage']              ? (int)$data['hourly_wage']              : null,
                $data['weekly_contract_hours']    ? (float)$data['weekly_contract_hours']  : null,
                $data['weekly_contract_days']     ? (int)$data['weekly_contract_days']     : null,
                (int)($data['weekly_holiday_pay_enabled'] ?? 0) ? 1 : 0,
                $data['hire_date']      ?: null,
                $data['invited_phone']  ?: null,
                $data['invited_email']  ?: null,
                $token,
                (int)$data['created_by'],
            ]
        );

        // 기존 직원 카드가 있으면 account_status = invited로 업데이트
        if (!empty($data['store_member_id'])) {
            DB::query(
                "UPDATE store_members SET account_status='invited', invitation_id=LAST_INSERT_ID() WHERE id=? AND store_id=?",
                [(int)$data['store_member_id'], (int)$data['store_id']]
            );
        }

        // 호출 측(InviteController)이 링크에 사용할 평문 토큰을 반환한다.
        return $rawToken;
    }

    public static function findByToken(string $rawToken): ?array
    {
        return DB::fetchOne(
            "SELECT inv.*, s.store_name
             FROM employee_invitations inv
             JOIN stores s ON s.id = inv.store_id
             WHERE inv.token = ?
               AND inv.status = 'pending'
               AND inv.expires_at > NOW()",
            [hash('sha256', $rawToken)]
        );
    }

    /**
     * 초대 수락.
     * store_member_id가 있으면 기존 카드에 user_id 연결.
     * 없으면 초대 데이터로 새 store_members 레코드 생성.
     */
    public static function accept(string $rawToken, int $userId): bool
    {
        $inv = DB::fetchOne(
            "SELECT * FROM employee_invitations WHERE token=? AND status='pending' AND expires_at>NOW()",
            [hash('sha256', $rawToken)]
        );
        if (!$inv) return false;

        // 중복 소속 방지: 이미 active/on_leave인 소속이 있으면 스킵
        $dup = DB::fetchOne(
            "SELECT id FROM store_members WHERE store_id=? AND user_id=? AND employment_status IN ('active','on_leave')",
            [$inv['store_id'], $userId]
        );
        if ($dup) {
            // 이미 소속 → 초대만 accepted 처리
            DB::query(
                "UPDATE employee_invitations SET status='accepted', accepted_by_user_id=?, accepted_at=NOW() WHERE id=?",
                [$userId, $inv['id']]
            );
            return true;
        }

        if (!empty($inv['store_member_id'])) {
            // 기존 직원 카드에 연결
            DB::query(
                "UPDATE store_members SET user_id=?, account_status='linked', joined_at=NOW(), updated_at=NOW() WHERE id=? AND store_id=?",
                [$userId, $inv['store_member_id'], $inv['store_id']]
            );
        } else {
            // 새 직원 카드 생성
            DB::query(
                "INSERT INTO store_members
                   (store_id, user_id, name, account_status, employment_status,
                    hourly_wage, weekly_scheduled_hours, weekly_scheduled_days, weekly_holiday_enabled,
                    employment_start_date, invitation_id, joined_at, is_active,
                    created_at, updated_at)
                 VALUES (?,?,?,'linked','active',?,?,?,?,?,?,NOW(),1,NOW(),NOW())",
                [
                    $inv['store_id'], $userId, $inv['invited_name'],
                    $inv['hourly_wage'], $inv['weekly_contract_hours'], $inv['weekly_contract_days'],
                    $inv['weekly_holiday_pay_enabled'] ? 1 : 0,
                    $inv['hire_date'] ?? date('Y-m-d'),
                    $inv['id'],
                ]
            );
        }

        DB::query(
            "UPDATE employee_invitations SET status='accepted', accepted_by_user_id=?, accepted_at=NOW() WHERE id=?",
            [$userId, $inv['id']]
        );
        return true;
    }

    public static function cancel(int $id, int $storeId): void
    {
        DB::query(
            "UPDATE employee_invitations SET status='cancelled' WHERE id=? AND store_id=?",
            [$id, $storeId]
        );
        // store_member가 있으면 account_status를 no_account로 복귀
        $inv = DB::fetchOne('SELECT store_member_id FROM employee_invitations WHERE id=?', [$id]);
        if ($inv && $inv['store_member_id']) {
            DB::query(
                "UPDATE store_members SET account_status='no_account', invitation_id=NULL WHERE id=? AND store_id=? AND account_status='invited'",
                [$inv['store_member_id'], $storeId]
            );
        }
    }

    public static function forMember(int $storeMemberId): ?array
    {
        return DB::fetchOne(
            "SELECT * FROM employee_invitations
             WHERE store_member_id=? AND status='pending' AND expires_at>NOW()
             ORDER BY id DESC LIMIT 1",
            [$storeMemberId]
        );
    }

    public static function findById(int $id, int $storeId): ?array
    {
        return DB::fetchOne(
            "SELECT * FROM employee_invitations WHERE id=? AND store_id=?",
            [$id, $storeId]
        );
    }
}
