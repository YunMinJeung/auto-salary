<div class="card shadow-sm border-0">
  <div class="card-body p-4 text-center">
    <div class="mb-3" style="font-size:2.5rem">🔗</div>
    <h5 class="fw-bold mb-2">아직 매장에 연결되지 않았어요</h5>
    <p class="text-muted small mb-4">
      사장님에게 초대 링크를 요청하거나,<br>
      사장님이 직원 목록에서 계정을 연결해 주셔야 합니다.
    </p>
    <p class="text-muted small mb-4">
      가입한 이메일: <strong><?= h(Auth::user()['email'] ?? '') ?></strong>
    </p>
    <a href="<?= url('auth', 'logout') ?>" class="btn btn-outline-secondary btn-sm">로그아웃</a>
  </div>
</div>
