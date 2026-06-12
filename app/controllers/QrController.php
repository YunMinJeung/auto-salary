<?php
class QrController
{
    private const TOKEN_KEY = 'qr_plain_%d';
    private const EXP_KEY   = 'qr_exp_%d';
    private const TTL       = 3600; // 1시간

    public function index(): void
    {
        Auth::requireOwner();
        $storeId = Auth::storeId();

        $activeToken = StoreQrToken::findActiveForStore($storeId);
        $plainToken  = $this->sessionToken($storeId);
        $scanUrl     = $plainToken
            ? BASE_URL . 'index.php?c=clock&a=scan&token=' . urlencode($plainToken)
            : null;

        render('qr/index', [
            'title'       => 'QR 출퇴근 관리',
            'activeToken' => $activeToken,
            'plainToken'  => $plainToken,
            'scanUrl'     => $scanUrl,
            'history'     => StoreQrToken::history($storeId, 10),
            'store'       => Store::findOwned(Auth::storeId(), Auth::ownerId()),
        ]);
    }

    public function generate(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(url('qr')); return; }
        verify_csrf();

        $storeId    = Auth::storeId();
        $plainToken = bin2hex(random_bytes(32));

        StoreQrToken::issue($storeId, $plainToken, Auth::id());
        $this->saveSessionToken($storeId, $plainToken);

        flash('success', '새 QR을 발급했습니다. 1시간 내에 저장해 두세요.');
        redirect(url('qr'));
    }

    public function revoke(): void
    {
        Auth::requireOwner();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect(url('qr')); return; }
        verify_csrf();

        $storeId = Auth::storeId();
        $active  = StoreQrToken::findActiveForStore($storeId);
        if ($active) {
            StoreQrToken::revoke((int)$active['id'], $storeId);
        }
        $this->clearSessionToken($storeId);

        flash('success', 'QR을 폐기했습니다. 기존 QR 코드는 더 이상 작동하지 않습니다.');
        redirect(url('qr'));
    }

    public function pdf(): void
    {
        Auth::requireOwner();
        $storeId    = Auth::storeId();
        $plainToken = $this->sessionToken($storeId);

        if (!$plainToken) {
            flash('error', 'QR 유효 시간이 만료되었습니다. 새로 발급해 주세요.');
            redirect(url('qr'));
        }

        $scanUrl = BASE_URL . 'index.php?c=clock&a=scan&token=' . urlencode($plainToken);

        render('qr/pdf', [
            'title'   => 'QR 출퇴근 코드 — 인쇄용',
            'scanUrl' => $scanUrl,
            'store'   => Store::findOwned(Auth::storeId(), Auth::ownerId()),
        ], 'payslip_layout');
    }

    // ── session helpers ──────────────────────────────────────────

    private function sessionToken(int $storeId): ?string
    {
        $key    = sprintf(self::TOKEN_KEY, $storeId);
        $expKey = sprintf(self::EXP_KEY,   $storeId);
        if (empty($_SESSION[$key])) return null;
        if (time() > (int)($_SESSION[$expKey] ?? 0)) {
            unset($_SESSION[$key], $_SESSION[$expKey]);
            return null;
        }
        return $_SESSION[$key];
    }

    private function saveSessionToken(int $storeId, string $plain): void
    {
        $_SESSION[sprintf(self::TOKEN_KEY, $storeId)] = $plain;
        $_SESSION[sprintf(self::EXP_KEY,   $storeId)] = time() + self::TTL;
    }

    private function clearSessionToken(int $storeId): void
    {
        unset(
            $_SESSION[sprintf(self::TOKEN_KEY, $storeId)],
            $_SESSION[sprintf(self::EXP_KEY,   $storeId)]
        );
    }
}
