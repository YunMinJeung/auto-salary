<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

if (($_GET["token"] ?? "") !== "run_migrate_9x7k2p") {
    http_response_code(403); die("Forbidden");
}

echo "PHP OK\n";
echo "__DIR__: " . __DIR__ . "\n";
echo "dirname(__DIR__): " . dirname(__DIR__) . "\n";

$db_dir = dirname(__DIR__) . "/auto_salary/database";
echo "db_dir: $db_dir\n";
echo "db_dir exists: " . (is_dir($db_dir) ? "YES" : "NO") . "\n";

if (is_dir($db_dir)) {
    $listed = scandir($db_dir);
    echo "files in db_dir: " . implode(", ", $listed) . "\n";
}

$files = [
    "schema.sql",
    "migrate_add_owner_id.sql",
    "alter_employee_premiums.sql",
    "migrate_employee_attendance.sql",
    "migrate_minimum_wages.sql",
    "migrate_attendance_audit.sql",
    "migrate_benchmark_features.sql",
    "migrate_insurance.sql",
    "migrate_insurance_eligibility.sql",
    "migrate_contracts_qr.sql",
    "migrate_employee_member_link.sql",
    "migrate_date_of_birth.sql",
    "migrate_work_schedule.sql",
    "migrate_schema_drift.sql",
];

echo "\nConnecting to DB...\n";
$mysqli = new mysqli("localhost", "u790356737_auto_salary", "PaYcl0ck_DB#2026", "u790356737_auto_salary");
if ($mysqli->connect_error) {
    die("Connect error: " . $mysqli->connect_error . "\n");
}
echo "DB connected.\n";
$mysqli->set_charset("utf8mb4");

$total_ok = 0;
$all_errors = [];

foreach ($files as $fname) {
    $path = $db_dir . "/" . $fname;
    if (!file_exists($path)) {
        $all_errors[] = "Missing: $fname";
        echo "Missing: $fname\n";
        continue;
    }
    echo "Running: $fname ... ";
    $sql = file_get_contents($path);
    if ($mysqli->multi_query($sql)) {
        $ok = 0;
        do { $ok++; } while ($mysqli->next_result());
        $total_ok += $ok;
        echo "OK ($ok statements)\n";
    } else {
        $err = "$fname: " . $mysqli->error;
        $all_errors[] = $err;
        echo "ERROR: " . $mysqli->error . "\n";
    }
    while ($mysqli->more_results()) $mysqli->next_result();
    $mysqli->query("SELECT 1");
}

echo "\nDone: $total_ok statement(s) executed.\n";
echo "Errors: " . count($all_errors) . "\n";
foreach ($all_errors as $e) echo "  - $e\n";
$mysqli->close();
