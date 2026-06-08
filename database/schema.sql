-- 소규모 자영업자용 급여·주휴수당 계산 시스템
-- MySQL / MariaDB

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
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
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `settings` (`business_name`) VALUES ('내 사업장');

CREATE TABLE IF NOT EXISTS `employees` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `hourly_wage` INT NOT NULL DEFAULT 10320,
  `employment_start_date` DATE NOT NULL,
  `employment_end_date` DATE NULL,
  `weekly_scheduled_days` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `weekly_scheduled_hours` DECIMAL(5,2) NOT NULL DEFAULT 40.00,
  `weekly_holiday_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `memo` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `work_logs` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
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
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payroll_results` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
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
  FOREIGN KEY (`employee_id`) REFERENCES `employees`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 샘플 직원 (주 소정근로시간 20시간, 시급 10,320원)
INSERT INTO `employees`
  (`name`, `hourly_wage`, `employment_start_date`, `weekly_scheduled_days`, `weekly_scheduled_hours`)
VALUES
  ('홍길동 (샘플)', 10320, '2026-01-01', 5, 20.00);

-- 샘플 근무기록: 2026-06-01(월)~06-05(금) 18:00~22:00, 06-06(토) 21:00~02:00 야간 테스트
INSERT INTO `work_logs`
  (`employee_id`, `work_date`, `start_time`, `end_time`, `break_auto`)
VALUES
  (1, '2026-06-01', '18:00:00', '22:00:00', 1),
  (1, '2026-06-02', '18:00:00', '22:00:00', 1),
  (1, '2026-06-03', '18:00:00', '22:00:00', 1),
  (1, '2026-06-04', '18:00:00', '22:00:00', 1),
  (1, '2026-06-05', '18:00:00', '22:00:00', 1),
  (1, '2026-06-06', '21:00:00', '02:00:00', 0);

SET FOREIGN_KEY_CHECKS = 1;
