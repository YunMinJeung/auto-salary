<?php
class ClockQrController
{
    public function scan(): void
    {
        $rawToken = trim($_GET['token'] ?? '');
        if (!$rawToken) {
            $this->showError('유효하지 않은 QR 코드입니다.');
            return;
        }

        $tokenRow = StoreQrToken::findActiveByHash(hash('sha256', $rawToken));
        if (!$tokenRow) {
            $this->showError('만료되었거나 폐기된 QR 코드입니다. 매장에 새 QR 발급을 요청하세요.');
            return;
        }

        $storeId = (int)$tokenRow['store_id'];

        // 미로그인 → 이 페이지 URL을 보존하고 로그인으로
        if (!Auth::check()) {
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            redirect(url('auth', 'login'));
        }

        if (Auth::isOwner()) {
            $this->showError('사장님 계정으로는 QR 출퇴근을 사용할 수 없습니다.');
            return;
        }

        // 이 매장에 소속된 직원인지 확인 (세션 기준)
        $myStoreId  = Auth::storeId();
        $myMemberId = Auth::storeMemberId();

        if (!$myMemberId || (int)$myStoreId !== $storeId) {
            QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'view', false, '매장 소속 불일치');
            $this->showError('이 매장에 등록된 직원만 출퇴근할 수 있습니다.');
            return;
        }

        $working  = AttendanceLog::currentlyWorking($myMemberId);
        $store    = Store::find($storeId);

        // ── POST: 실제 출퇴근 처리 ────────────────────────────────
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            verify_csrf();
            $gps = $this->validateGps($storeId);
            $now = date('H:i');

            if ($working) {
                $logId = (int)$working['id'];
                $ok = AttendanceLog::clockOut($logId, $storeId, $myMemberId);
                if ($ok) {
                    AttendanceLog::saveGpsSnapshot($logId, $gps + ['source' => 'QR']);
                    // attendance_logs → work_logs 자동 동기화
                    AttendanceSyncService::sync($logId);
                }
                QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_out', $ok);
                $action  = 'clock_out';
                $success = $ok;
            } else {
                $newId = AttendanceLog::clockIn($storeId, $myMemberId, 'qr', Auth::id());
                if ($newId) {
                    AttendanceLog::saveGpsSnapshot($newId, $gps + ['source' => 'QR']);
                }
                QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_in', true);
                $action  = 'clock_in';
                $success = true;
            }

            render('qr/scan_result', [
                'title'   => $action === 'clock_in' ? '출근 완료' : '퇴근 완료',
                'action'  => $action,
                'success' => $success,
                'store'   => $store,
                'now'     => $now,
                'token'   => $rawToken,
            ], 'employee_mobile_layout');
            return;
        }

        // ── GET: 확인 화면만 렌더 (상태 변경 없음) ────────────────
        QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'view', true);
        render('qr/scan_confirm', [
            'title'   => 'QR 출퇴근',
            'action'  => $working ? 'clock_out' : 'clock_in',
            'working' => $working,
            'store'   => $store,
            'token'   => $rawToken,
        ], 'employee_mobile_layout');
    }

    public function clockIn(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(url('employee')); return; }
        verify_csrf();

        $rawToken = trim($_POST['token'] ?? '');
        $tokenRow = $this->resolveToken($rawToken);
        if (!$tokenRow) { redirect(url('employee')); return; }

        $storeId  = (int)$tokenRow['store_id'];
        if (!$this->assertMembership($storeId, (int)$tokenRow['id'], 'clock_in')) return;
        $gps      = $this->validateGps($storeId);
        $memberId = Auth::storeMemberId();

        if (AttendanceLog::currentlyWorking($memberId)) {
            QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_in', false, '이미 출근 중');
            flash('error', '이미 출근 처리되어 있습니다.');
            $this->backToScan($rawToken);
            return;
        }

        $newId = AttendanceLog::clockIn($storeId, $memberId, 'qr', Auth::id());
        if ($newId) {
            AttendanceLog::saveGpsSnapshot($newId, $gps + ['source' => 'QR']);
        }
        QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_in', true);
        flash('success', '출근이 기록되었습니다.');
        $this->backToScan($rawToken);
    }

    public function clockOut(): void
    {
        Auth::requireEmployee();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(url('employee')); return; }
        verify_csrf();

        $rawToken = trim($_POST['token'] ?? '');
        $tokenRow = $this->resolveToken($rawToken);
        if (!$tokenRow) { redirect(url('employee')); return; }

        $storeId  = (int)$tokenRow['store_id'];
        if (!$this->assertMembership($storeId, (int)$tokenRow['id'], 'clock_out')) return;
        $gps      = $this->validateGps($storeId);
        $memberId = Auth::storeMemberId();
        $working  = AttendanceLog::currentlyWorking($memberId);

        if (!$working) {
            QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_out', false, '출근 기록 없음');
            flash('error', '출근 기록이 없습니다.');
            $this->backToScan($rawToken);
            return;
        }

        $logId = (int)$working['id'];
        $ok = AttendanceLog::clockOut($logId, $storeId, $memberId);
        if ($ok) {
            AttendanceLog::saveGpsSnapshot($logId, $gps + ['source' => 'QR']);
            // attendance_logs → work_logs 자동 동기화
            AttendanceSyncService::sync($logId);
            QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_out', true);
            flash('success', '퇴근이 기록되었습니다.');
        } else {
            QrScanLog::record($storeId, Auth::id(), (int)$tokenRow['id'], 'clock_out', false, '퇴근 처리 실패');
            flash('error', '퇴근 처리에 실패했습니다. 잠시 후 다시 시도하세요.');
        }
        $this->backToScan($rawToken);
    }

    // ── private helpers ──────────────────────────────────────────

    private function resolveToken(string $rawToken): ?array
    {
        if (!$rawToken) return null;
        return StoreQrToken::findActiveByHash(hash('sha256', $rawToken)) ?: null;
    }

    private function assertMembership(int $storeId, int $tokenId, string $action): bool
    {
        $myStoreId  = Auth::storeId();
        $myMemberId = Auth::storeMemberId();

        if (!$myMemberId || (int)$myStoreId !== $storeId) {
            QrScanLog::record($storeId, Auth::id(), $tokenId, $action, false, '매장 소속 불일치');
            redirect(url('employee'));
            return false;
        }
        return true;
    }

    private function backToScan(string $rawToken): void
    {
        redirect(BASE_URL . 'index.php?c=clock&a=scan&token=' . urlencode($rawToken));
    }

    private function showError(string $msg): void
    {
        render('qr/scan_error', [
            'title'   => 'QR 출퇴근',
            'message' => $msg,
        ], 'employee_mobile_layout');
    }

    /** GPS 인증이 켜진 매장이면 검증. 실패 시 에러 렌더 후 종료. 성공 시 GPS 데이터 반환. */
    private function validateGps(int $storeId): array
    {
        $gps = gps_validate($storeId);
        if ($gps['error'] !== null) {
            $this->showGpsError($gps['error']);
            exit;
        }
        return $gps;
    }

    private function showGpsError(string $message): void
    {
        render('qr/scan_error', [
            'title'   => '위치 인증 실패',
            'message' => $message,
        ], 'employee_mobile_layout');
    }
}
