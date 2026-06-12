<?php
/**
 * 점주의 출퇴근/휴게 수정 요청 — 직원 검토 대기 큐.
 *
 * 흐름:
 *   점주 수정 → create() (pending_employee_review)
 *   직원 수락 → accept()       → adjusted_* 반영
 *   직원 이의 → object()       → objected (adjusted_* 미반영)
 *   점주 강제확정 → forceConfirm() → adjusted_* 반영 (직원에게 공개)
 *   점주 협의확정 → resolve()   → 원본 유지 (corrected_confirmed)
 *
 * attendance_correction_requests(직원→점주)와는 별개 테이블이며 혼용하지 않는다.
 */
class AttendanceChangeRequest
{
    public static function create(
        int $storeId, int $logId, int $memberId, int $userId,
        array $original, array $proposed, string $reason
    ): int {
        DB::query(
            "INSERT INTO attendance_change_requests
             (store_id, attendance_log_id, store_member_id, requested_by_user_id,
              status,
              original_clock_in, original_clock_out, original_break_min,
              proposed_clock_in, proposed_clock_out, proposed_break_min,
              change_reason)
             VALUES (?, ?, ?, ?, 'pending_employee_review', ?, ?, ?, ?, ?, ?, ?)",
            [
                $storeId, $logId, $memberId, $userId,
                $original['clock_in']  ?? null,
                $original['clock_out'] ?? null,
                $original['break_min'] ?? null,
                $proposed['clock_in']  ?? null,
                $proposed['clock_out'] ?? null,
                $proposed['break_min'] ?? null,
                $reason,
            ]
        );
        return DB::lastInsertId();
    }

    /** 점주용: 직원 응답 대기/이의 제기 목록 */
    public static function findPending(int $storeId): array
    {
        return DB::fetchAll(
            "SELECT cr.*, sm.name AS member_name,
                    DATE(cr.original_clock_in) AS work_date
             FROM attendance_change_requests cr
             JOIN store_members sm ON sm.id = cr.store_member_id
             WHERE cr.store_id = ?
               AND cr.status IN ('pending_employee_review', 'objected')
             ORDER BY cr.created_at DESC",
            [$storeId]
        );
    }

    /** 특정 출퇴근 기록의 모든 요청 이력 (최신순) */
    public static function findByLog(int $logId): array
    {
        return DB::fetchAll(
            "SELECT * FROM attendance_change_requests
             WHERE attendance_log_id = ?
             ORDER BY created_at DESC",
            [$logId]
        );
    }

    public static function find(int $id, int $storeId): ?array
    {
        return DB::fetchOne(
            "SELECT * FROM attendance_change_requests WHERE id = ? AND store_id = ?",
            [$id, $storeId]
        );
    }

    /** 직원용: 본인에게 온 수락 대기/이의 제기/재수정 제안 요청 + 최근 처리완료 건 */
    public static function forMember(int $storeMemberId, int $storeId): array
    {
        return DB::fetchAll(
            "SELECT cr.*, u.name AS requester_name
             FROM attendance_change_requests cr
             LEFT JOIN users u ON u.id = cr.requested_by_user_id
             WHERE cr.store_member_id = ? AND cr.store_id = ?
               AND (
                     cr.status IN ('pending_employee_review', 'objected', 'counter_proposed')
                     OR (cr.objection_status IN ('accepted', 'rejected')
                         AND cr.owner_response_at > DATE_SUB(NOW(), INTERVAL 3 DAY))
                   )
             ORDER BY cr.created_at DESC",
            [$storeMemberId, $storeId]
        );
    }

    /** 직원 수락 → adjusted_* 반영 + 이력 기록 */
    public static function accept(int $id, int $storeId): void
    {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::transaction(function () use ($id, $storeId, $req) {
            DB::query(
                "UPDATE attendance_change_requests
                 SET status = 'accepted', employee_response_at = NOW()
                 WHERE id = ? AND store_id = ?",
                [$id, $storeId]
            );

            DB::query(
                "UPDATE attendance_logs
                 SET adjusted_clock_in_at  = ?,
                     adjusted_clock_out_at = ?,
                     break_minutes         = ?,
                     status                = 'corrected',
                     record_status         = 'accepted',
                     active_change_request_id = NULL,
                     updated_at            = NOW()
                 WHERE id = ? AND store_id = ?",
                [
                    $req['proposed_clock_in'],
                    $req['proposed_clock_out'],
                    $req['proposed_break_min'],
                    $req['attendance_log_id'],
                    $storeId,
                ]
            );

            AttendanceAdjustmentLog::create([
                'attendance_log_id'    => (int)$req['attendance_log_id'],
                'store_id'             => $storeId,
                'store_member_id'      => (int)$req['store_member_id'],
                'changed_by_user_id'   => (int)$req['requested_by_user_id'],
                'changed_by_role'      => 'owner',
                'before_clock_in_at'   => $req['original_clock_in'],
                'before_clock_out_at'  => $req['original_clock_out'],
                'after_clock_in_at'    => $req['proposed_clock_in'],
                'after_clock_out_at'   => $req['proposed_clock_out'],
                'before_break_minutes' => $req['original_break_min'] ?? 0,
                'after_break_minutes'  => $req['proposed_break_min'] ?? 0,
                'reason'               => $req['change_reason'],
                'change_type'          => 'employee_accepted',
                'employee_visible'     => 1,
            ]);

            AttendanceSyncService::sync((int)$req['attendance_log_id']);
        });
    }

    /** 직원 이의제기 → objected (adjusted_* 미반영). 직원이 생각하는 올바른 값(선택)도 저장. */
    public static function object(
        int $id, int $storeId, string $objection,
        ?string $reqClockIn = null, ?string $reqClockOut = null, ?int $reqBreakMin = null
    ): void {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::query(
            "UPDATE attendance_change_requests
             SET status = 'objected',
                 employee_objection = ?,
                 employee_response_at = NOW(),
                 employee_requested_clock_in  = ?,
                 employee_requested_clock_out = ?,
                 employee_requested_break_min = ?,
                 objection_status = 'submitted'
             WHERE id = ? AND store_id = ?",
            [$objection, $reqClockIn, $reqClockOut, $reqBreakMin, $id, $storeId]
        );

        DB::query(
            "UPDATE attendance_logs
             SET record_status = 'objected', updated_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$req['attendance_log_id'], $storeId]
        );
    }

    /** 점주용: 처리 대기 이의제기 목록 (objection_status = 'submitted') */
    public static function findObjections(int $storeId): array
    {
        return DB::fetchAll(
            "SELECT cr.*, sm.name AS member_name,
                    DATE(cr.original_clock_in) AS work_date
             FROM attendance_change_requests cr
             JOIN store_members sm ON sm.id = cr.store_member_id
             WHERE cr.store_id = ?
               AND cr.status = 'objected'
               AND cr.objection_status = 'submitted'
             ORDER BY cr.employee_response_at DESC",
            [$storeId]
        );
    }

    /**
     * 점주: 이의제기 수락 — 직원 요청값 또는 원본값을 확정에 반영.
     * @param string $acceptType 'employee_request' | 'original'
     */
    public static function acceptObjection(
        int $id, int $storeId, int $userId,
        string $acceptType, string $response
    ): void {
        $req = self::find($id, $storeId);
        if (!$req) return;

        if ($acceptType === 'original') {
            $applyIn    = $req['original_clock_in'];
            $applyOut   = $req['original_clock_out'];
            $applyBreak = $req['original_break_min'];
        } else { // employee_request
            // 직원 요청값이 비어 있으면 원본값으로 폴백
            $applyIn    = $req['employee_requested_clock_in']  ?? $req['original_clock_in'];
            $applyOut   = $req['employee_requested_clock_out'] ?? $req['original_clock_out'];
            $applyBreak = $req['employee_requested_break_min'] ?? $req['original_break_min'];
        }

        DB::transaction(function () use ($id, $storeId, $userId, $response, $req, $applyIn, $applyOut, $applyBreak) {
            DB::query(
                "UPDATE attendance_change_requests
                 SET objection_status = 'accepted',
                     status = 'accepted',
                     owner_response = ?,
                     owner_response_at = NOW(),
                     owner_processed_by = ?
                 WHERE id = ? AND store_id = ?",
                [$response, $userId, $id, $storeId]
            );

            DB::query(
                "UPDATE attendance_logs
                 SET adjusted_clock_in_at  = ?,
                     adjusted_clock_out_at = ?,
                     break_minutes         = ?,
                     status                = 'corrected',
                     record_status         = 'accepted',
                     active_change_request_id = NULL,
                     updated_at            = NOW()
                 WHERE id = ? AND store_id = ?",
                [$applyIn, $applyOut, $applyBreak, $req['attendance_log_id'], $storeId]
            );

            AttendanceAdjustmentLog::create([
                'attendance_log_id'    => (int)$req['attendance_log_id'],
                'store_id'             => $storeId,
                'store_member_id'      => (int)$req['store_member_id'],
                'changed_by_user_id'   => $userId,
                'changed_by_role'      => 'owner',
                'before_clock_in_at'   => $req['original_clock_in'],
                'before_clock_out_at'  => $req['original_clock_out'],
                'after_clock_in_at'    => $applyIn,
                'after_clock_out_at'   => $applyOut,
                'before_break_minutes' => $req['original_break_min'] ?? 0,
                'after_break_minutes'  => $applyBreak ?? 0,
                'reason'               => $response !== '' ? $response : '이의제기 수락',
                'change_type'          => 'objection_accepted',
                'employee_visible'     => 1,
            ]);

            AttendanceSyncService::sync((int)$req['attendance_log_id']);
        });
    }

    /** 점주: 이의제기 거부 — 점주 수정안(proposed_*) 유지 + 이력 기록 */
    public static function rejectObjection(int $id, int $storeId, int $userId, string $reason): void
    {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::transaction(function () use ($id, $storeId, $userId, $reason, $req) {
            DB::query(
                "UPDATE attendance_change_requests
                 SET objection_status = 'rejected',
                     status = 'owner_forced_confirmed',
                     owner_response = ?,
                     owner_response_at = NOW(),
                     owner_processed_by = ?,
                     is_force_confirmed = 1,
                     force_reason = ?,
                     force_confirmed_by = ?,
                     force_confirmed_at = NOW()
                 WHERE id = ? AND store_id = ?",
                [$reason, $userId, $reason, $userId, $id, $storeId]
            );

            DB::query(
                "UPDATE attendance_logs
                 SET adjusted_clock_in_at  = ?,
                     adjusted_clock_out_at = ?,
                     break_minutes         = ?,
                     status                = 'corrected',
                     record_status         = 'owner_forced_confirmed',
                     active_change_request_id = NULL,
                     updated_at            = NOW()
                 WHERE id = ? AND store_id = ?",
                [
                    $req['proposed_clock_in'],
                    $req['proposed_clock_out'],
                    $req['proposed_break_min'],
                    $req['attendance_log_id'],
                    $storeId,
                ]
            );

            AttendanceAdjustmentLog::create([
                'attendance_log_id'    => (int)$req['attendance_log_id'],
                'store_id'             => $storeId,
                'store_member_id'      => (int)$req['store_member_id'],
                'changed_by_user_id'   => $userId,
                'changed_by_role'      => 'owner',
                'before_clock_in_at'   => $req['original_clock_in'],
                'before_clock_out_at'  => $req['original_clock_out'],
                'after_clock_in_at'    => $req['proposed_clock_in'],
                'after_clock_out_at'   => $req['proposed_clock_out'],
                'before_break_minutes' => $req['original_break_min'] ?? 0,
                'after_break_minutes'  => $req['proposed_break_min'] ?? 0,
                'reason'               => $reason,
                'change_type'          => 'objection_rejected',
                'employee_visible'     => 1,
            ]);

            AttendanceSyncService::sync((int)$req['attendance_log_id']);
        });
    }

    /**
     * 점주: 재수정 제안 — proposed_*를 새 값으로 갱신하고 직원 재검토 대기로 전환.
     * adjusted_*는 직원 수락 후 accept()에서 반영된다.
     */
    public static function counterPropose(
        int $id, int $storeId, int $userId,
        string $clockIn, string $clockOut, int $breakMin, string $reason
    ): void {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::query(
            "UPDATE attendance_change_requests
             SET status = 'counter_proposed',
                 objection_status = 'counter_proposed',
                 counter_clock_in = ?,
                 counter_clock_out = ?,
                 counter_break_min = ?,
                 counter_reason = ?,
                 owner_response_at = NOW(),
                 owner_processed_by = ?,
                 proposed_clock_in = ?,
                 proposed_clock_out = ?,
                 proposed_break_min = ?
             WHERE id = ? AND store_id = ?",
            [
                $clockIn, $clockOut, $breakMin, $reason, $userId,
                $clockIn, $clockOut, $breakMin,
                $id, $storeId,
            ]
        );

        DB::query(
            "UPDATE attendance_logs
             SET record_status = 'pending_employee_review',
                 active_change_request_id = ?,
                 updated_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$id, $req['attendance_log_id'], $storeId]
        );
    }

    /** 점주 대시보드 배지용: 처리해야 할 이의제기 건수 */
    public static function objectionCount(int $storeId): int
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM attendance_change_requests
             WHERE store_id = ? AND status = 'objected' AND objection_status = 'submitted'",
            [$storeId]
        );
        return (int)($row['cnt'] ?? 0);
    }

    /** 점주 강제 확정 → adjusted_* 반영 (직원에게 공개) + 이력 기록 */
    public static function forceConfirm(int $id, int $storeId, string $reason, int $userId): void
    {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::transaction(function () use ($id, $storeId, $reason, $userId, $req) {
            DB::query(
                "UPDATE attendance_change_requests
                 SET status = 'owner_forced_confirmed',
                     is_force_confirmed = 1,
                     force_reason = ?,
                     force_confirmed_by = ?,
                     force_confirmed_at = NOW()
                 WHERE id = ? AND store_id = ?",
                [$reason, $userId, $id, $storeId]
            );

            DB::query(
                "UPDATE attendance_logs
                 SET adjusted_clock_in_at  = ?,
                     adjusted_clock_out_at = ?,
                     break_minutes         = ?,
                     status                = 'corrected',
                     record_status         = 'owner_forced_confirmed',
                     active_change_request_id = NULL,
                     updated_at            = NOW()
                 WHERE id = ? AND store_id = ?",
                [
                    $req['proposed_clock_in'],
                    $req['proposed_clock_out'],
                    $req['proposed_break_min'],
                    $req['attendance_log_id'],
                    $storeId,
                ]
            );

            AttendanceAdjustmentLog::create([
                'attendance_log_id'    => (int)$req['attendance_log_id'],
                'store_id'             => $storeId,
                'store_member_id'      => (int)$req['store_member_id'],
                'changed_by_user_id'   => $userId,
                'changed_by_role'      => 'owner',
                'before_clock_in_at'   => $req['original_clock_in'],
                'before_clock_out_at'  => $req['original_clock_out'],
                'after_clock_in_at'    => $req['proposed_clock_in'],
                'after_clock_out_at'   => $req['proposed_clock_out'],
                'before_break_minutes' => $req['original_break_min'] ?? 0,
                'after_break_minutes'  => $req['proposed_break_min'] ?? 0,
                'reason'               => $reason,
                'change_type'          => 'owner_force_confirmed',
                'employee_visible'     => 1,
            ]);

            AttendanceSyncService::sync((int)$req['attendance_log_id']);
        });
    }

    /** 점주 협의 후 확정 → 원본 유지 (corrected_confirmed) */
    public static function resolve(int $id, int $storeId, string $note): void
    {
        $req = self::find($id, $storeId);
        if (!$req) return;

        DB::query(
            "UPDATE attendance_change_requests
             SET status = 'corrected_confirmed', resolution_note = ?, resolved_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$note, $id, $storeId]
        );

        DB::query(
            "UPDATE attendance_logs
             SET record_status = 'corrected_confirmed',
                 active_change_request_id = NULL,
                 updated_at = NOW()
             WHERE id = ? AND store_id = ?",
            [$req['attendance_log_id'], $storeId]
        );
    }

    /** 점주 대시보드 배지용: 직원 응답 대기 건수 (재수정 제안 포함) */
    public static function pendingCount(int $storeId): int
    {
        $row = DB::fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM attendance_change_requests
             WHERE store_id = ? AND status IN ('pending_employee_review', 'counter_proposed')",
            [$storeId]
        );
        return (int)($row['cnt'] ?? 0);
    }
}
