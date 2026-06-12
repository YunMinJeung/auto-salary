<?php
class AccessLog
{
    public static function record(
        int $viewerId,
        string $viewerRole,
        string $targetType,
        int $targetId,
        string $action
    ): void {
        try {
            DB::query(
                'INSERT INTO access_logs
                 (viewer_user_id, viewer_role, target_type, target_id, action, ip_address, user_agent, created_at)
                 VALUES (?,?,?,?,?,?,?,NOW())',
                [
                    $viewerId,
                    $viewerRole,
                    $targetType,
                    $targetId,
                    $action,
                    $_SERVER['REMOTE_ADDR'] ?? null,
                    substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
                ]
            );
        } catch (Exception $e) {
            // 로그 실패는 무시 (서비스 영향 없음)
            error_log('AccessLog::record failed: ' . $e->getMessage());
        }
    }
}
