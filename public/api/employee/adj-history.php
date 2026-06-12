<?php
require dirname(__DIR__) . '/bootstrap.php';

if (!Auth::check() || !Auth::isEmployee()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$logId         = (int)($_GET['log_id'] ?? 0);
$storeMemberId = Auth::storeMemberId();
$storeId       = Auth::storeId();

if (!$logId || !$storeMemberId) {
    echo json_encode(['items' => []]);
    exit;
}

// 해당 기록이 이 직원의 것인지 검증
$log = DB::fetchOne(
    'SELECT id FROM attendance_logs WHERE id = ? AND store_id = ? AND store_member_id = ?',
    [$logId, $storeId, $storeMemberId]
);
if (!$log) {
    echo json_encode(['items' => []]);
    exit;
}

$rows = AttendanceAdjustmentLog::forAttendanceLog($logId, $storeId);

$items = [];
foreach ($rows as $r) {
    if (!$r['employee_visible']) continue;
    $items[] = [
        'changed_by_name'  => $r['changed_by_name'] ?? '점주',
        'created_at'       => date('Y-m-d H:i', strtotime($r['created_at'])),
        'before_clock_in'  => $r['before_clock_in_at']  ? date('H:i', strtotime($r['before_clock_in_at']))  : null,
        'before_clock_out' => $r['before_clock_out_at'] ? date('H:i', strtotime($r['before_clock_out_at'])) : null,
        'after_clock_in'   => $r['after_clock_in_at']   ? date('H:i', strtotime($r['after_clock_in_at']))   : null,
        'after_clock_out'  => $r['after_clock_out_at']  ? date('H:i', strtotime($r['after_clock_out_at']))  : null,
        'reason'           => $r['reason'],
    ];
}

echo json_encode(['items' => $items]);
