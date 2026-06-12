<?php
$cards = [
    ['label' => '전체 사업장', 'value' => $stats['total_stores'],     'icon' => 'bi-building',          'color' => '#006C67'],
    ['label' => '활성 사업장', 'value' => $stats['active_stores'],     'icon' => 'bi-building-check',    'color' => '#166534'],
    ['label' => '전체 사장',   'value' => $stats['total_owners'],      'icon' => 'bi-person-badge',      'color' => '#1d4ed8'],
    ['label' => '전체 알바',   'value' => $stats['total_employees'],   'icon' => 'bi-people',            'color' => '#0891b2'],
    ['label' => '오늘 출퇴근', 'value' => $stats['today_attendance'],  'icon' => 'bi-clock-history',     'color' => '#92400e'],
    ['label' => '미처리 문의', 'value' => $stats['open_tickets'],      'icon' => 'bi-chat-left-dots',    'color' => '#991b1b'],
];
?>
<div class="row g-3 mb-4">
  <?php foreach ($cards as $card): ?>
  <div class="col-6 col-lg-2">
    <div class="stat-card h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="stat-card-value"><?= number_format((int)$card['value']) ?></div>
          <div class="stat-card-label"><?= h($card['label']) ?></div>
        </div>
        <i class="bi <?= h($card['icon']) ?> stat-card-icon" style="color:<?= h($card['color']) ?>"></i>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <!-- 최근 가입 사업장 -->
  <div class="col-lg-7">
    <div class="admin-card h-100">
      <div class="admin-card-header">
        <span><i class="bi bi-building me-1"></i>최근 가입 사업장</span>
        <a href="<?= url('admin', 'businesses') ?>" class="small text-decoration-none">전체 보기 →</a>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr><th>사업장명</th><th>대표</th><th>가입일</th><th>상태</th></tr>
          </thead>
          <tbody>
            <?php if (empty($recentStores)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">데이터가 없습니다.</td></tr>
            <?php else: foreach ($recentStores as $s): ?>
              <tr>
                <td>
                  <a href="<?= url('admin', 'business_detail', ['id' => $s['id']]) ?>" class="text-decoration-none fw-semibold">
                    <?= h($s['store_name']) ?>
                  </a>
                </td>
                <td><?= h($s['owner_name'] ?? '-') ?><br><span class="small text-muted"><?= h($s['owner_email'] ?? '') ?></span></td>
                <td class="small text-muted"><?= h(substr((string)($s['created_at'] ?? ''), 0, 10)) ?></td>
                <td><?= adminBadge($s['status'] ?? 'ACTIVE') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 최근 문의 -->
  <div class="col-lg-5">
    <div class="admin-card h-100">
      <div class="admin-card-header">
        <span><i class="bi bi-chat-left-text me-1"></i>최근 문의</span>
        <a href="<?= url('admin', 'tickets') ?>" class="small text-decoration-none">전체 보기 →</a>
      </div>
      <div class="table-responsive">
        <table class="admin-table">
          <thead>
            <tr><th>제목</th><th>상태</th></tr>
          </thead>
          <tbody>
            <?php if (empty($recentTickets)): ?>
              <tr><td colspan="2" class="text-center text-muted py-4">문의가 없습니다.</td></tr>
            <?php else: foreach ($recentTickets as $t): ?>
              <tr>
                <td>
                  <a href="<?= url('admin', 'ticket_detail', ['id' => $t['id']]) ?>" class="text-decoration-none">
                    <?= h($t['title']) ?>
                  </a>
                  <div class="small text-muted"><?= h($t['user_name'] ?? '') ?> · <?= h($t['store_name'] ?? '') ?></div>
                </td>
                <td><?= adminBadge($t['status'] ?? 'OPEN') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
