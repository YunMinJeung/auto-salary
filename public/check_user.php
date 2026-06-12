<?php
require dirname(__DIR__) . '/app/config.php';
require dirname(__DIR__) . '/app/db.php';
$u = DB::fetchOne('SELECT id, email, name, role, password_hash FROM users WHERE email = ?', ['alba03@test.com']);
if ($u) {
    echo 'FOUND: id=' . $u['id'] . ' role=' . $u['role'] . ' name=' . $u['name'] . PHP_EOL;
    echo 'Hash verify test1234: ' . (password_verify('test1234', $u['password_hash']) ? 'OK' : 'FAIL') . PHP_EOL;
} else {
    echo 'NOT FOUND' . PHP_EOL;
}
