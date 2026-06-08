<?php
require dirname(__DIR__) . '/bootstrap.php';

if (!Auth::check() || !Auth::isOwner()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$storeId = Auth::storeId();
$today   = date('Y-m-d');

$logs       = AttendanceLog::todayForStore($storeId, $today);
$allMembers = StoreMember::allForStore($storeId, true);

// 멤버별 오늘 상태 집계
$logByMember = [];
foreach ($logs as $log) {
    $mid = $log['store_member_id'];
    if (!isset($logByMember[$mid])) $logByMember[$mid] = $log;
}

$summary = ['working' => 0, 'completed' => 0, 'absent' => 0];
$members = [];

foreach ($allMembers as $m) {
    $log = $logByMember[$m['id']] ?? null;
    if (!$log) {
        $status = 'absent';
        $summary['absent']++;
    } elseif ($log['status'] === 'working') {
        $status = 'working';
        $summary['working']++;
    } else {
        $status = 'completed';
        $summary['completed']++;
    }

    $members[] = [
        'id'               => $m['id'],
        'name'             => $m['name'],
        'hourly_wage'      => (int)$m['hourly_wage'],
        'today_status'     => $status,
        'clock_in_time'    => $log ? date('H:i', strtotime($log['clock_in_at'])) : null,
        'clock_out_time'   => ($log && $log['clock_out_at']) ? date('H:i', strtotime($log['clock_out_at'])) : null,
        'duration_minutes' => $log ? (int)$log['duration_minutes'] : 0,
    ];
}

echo json_encode([
    'success'   => true,
    'timestamp' => time(),
    'today'     => $today,
    'summary'   => $summary,
    'members'   => $members,
]);
