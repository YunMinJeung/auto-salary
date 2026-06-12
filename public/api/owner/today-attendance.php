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

    $effIn  = $log['effective_clock_in_at']  ?? null;
    $effOut = $log['effective_clock_out_at'] ?? null;
    $origIn  = $log ? $log['original_clock_in_at']  : null;
    $origOut = $log ? ($log['original_clock_out_at'] ?? null) : null;

    $members[] = [
        'id'                    => $m['id'],
        'name'                  => $m['name'],
        'hourly_wage'           => (int)$m['hourly_wage'],
        'today_status'          => $status,
        'is_adjusted'           => $log ? (bool)($log['adjustment_count'] ?? 0) : false,
        'clock_in_time'         => $effIn  ? date('H:i', strtotime($effIn))  : null,
        'clock_out_time'        => $effOut ? date('H:i', strtotime($effOut)) : null,
        'original_clock_in_time'  => $origIn  ? date('H:i', strtotime($origIn))  : null,
        'original_clock_out_time' => $origOut ? date('H:i', strtotime($origOut)) : null,
        'duration_minutes'      => $log ? (int)$log['duration_minutes'] : 0,
    ];
}

echo json_encode([
    'success'   => true,
    'timestamp' => time(),
    'today'     => $today,
    'summary'   => $summary,
    'members'   => $members,
]);
