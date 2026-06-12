<?php
// migrate: store_members.daily_break_minutes
define('ROOT_PATH', dirname(__FILE__));
define('APP_PATH',  ROOT_PATH . '/app');
require APP_PATH . '/config.php';
require APP_PATH . '/db.php';

DB::query("ALTER TABLE store_members ADD COLUMN IF NOT EXISTS daily_break_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60 AFTER weekly_holiday_enabled");
echo "OK: daily_break_minutes added\n";
