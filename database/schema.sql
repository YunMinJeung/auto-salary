-- 소규모 자영업자용 급여·주휴수당 계산 시스템
-- MySQL / MariaDB  —  멀티테넌트 구조 (owner_id 기반 데이터 격리)

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ─── 사용자 (점주) ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `email` VARCHAR(255) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `role` ENUM('owner','admin') NOT NULL DEFAULT 'owner',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 사업장 설정 (점주별 1건) ────────────────────────────────
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `business_name` VARCHAR(100) NOT NULL DEFAULT '내 사업장',
  `employee_count_type` ENUM('under5','over5') NOT NULL DEFAULT 'over5',
  `minimum_wage_year` INT NOT NULL DEFAULT 2026,
  `minimum_wage` INT NOT NULL DEFAULT 10320,
  `apply_overtime_premium` TINYINT(1) NOT NULL DEFAULT 1,
  `apply_night_premium` TINYINT(1) NOT NULL DEFAULT 1,
  `apply_holiday_premium` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_break_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `auto_weekly_holiday_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uq_owner_settings` (`owner_id`),
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 직원 ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `name` VARCHAR(100) NOT NULL,
  `hourly_wage` INT NOT NULL DEFAULT 10320,
  `employment_start_date` DATE NOT NULL,
  `employment_end_date` DATE NULL,
  `weekly_scheduled_days` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `weekly_scheduled_hours` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  `weekly_holiday_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `memo` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 근무 기록 ──────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `work_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `work_date` DATE NOT NULL,
  `start_time` TIME NOT NULL,
  `end_time` TIME NOT NULL,
  `break_minutes` INT NOT NULL DEFAULT 0,
  `break_auto` TINYINT(1) NOT NULL DEFAULT 1,
  `is_holiday` TINYINT(1) NOT NULL DEFAULT 0,
  `is_absent` TINYINT(1) NOT NULL DEFAULT 0,
  `is_late` TINYINT(1) NOT NULL DEFAULT 0,
  `is_early_leave` TINYINT(1) NOT NULL DEFAULT 0,
  `memo` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── 급여 계산 결과 ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `payroll_results` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `owner_id` INT NOT NULL,
  `employee_id` INT NOT NULL,
  `period_start` DATE NOT NULL,
  `period_end` DATE NOT NULL,
  `total_work_minutes` INT NOT NULL DEFAULT 0,
  `break_minutes` INT NOT NULL DEFAULT 0,
  `paid_work_minutes` INT NOT NULL DEFAULT 0,
  `night_minutes` INT NOT NULL DEFAULT 0,
  `overtime_minutes` INT NOT NULL DEFAULT 0,
  `holiday_minutes` INT NOT NULL DEFAULT 0,
  `base_pay` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `weekly_holiday_hours` DECIMAL(5,2) NOT NULL DEFAULT 0,
  `weekly_holiday_pay` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `night_premium` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `overtime_premium` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `holiday_premium` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total_pay` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `calculation_detail_json` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
