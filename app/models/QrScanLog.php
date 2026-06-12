<?php
class QrScanLog
{
    public static function record(
        int $storeId,
        int $employeeUserId,
        int $tokenId,
        string $action,
        bool $success,
        ?string $failureReason = null
    ): void {
        DB::query(
            'INSERT INTO qr_scan_logs
             (store_id, employee_user_id, token_id, action, ip_address, user_agent, success, failure_reason)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $storeId,
                $employeeUserId,
                $tokenId,
                $action,
                $_SERVER['REMOTE_ADDR']     ?? null,
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 500),
                $success ? 1 : 0,
                $failureReason,
            ]
        );
    }
}
