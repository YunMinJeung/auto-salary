-- ─────────────────────────────────────────────────────────
-- 기존 설치 → 멀티테넌트 마이그레이션
-- 실행 전: 반드시 DB 백업 후 진행하세요.
-- ─────────────────────────────────────────────────────────

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. 사용자 테이블 생성
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

-- 2. 기본 관리자 계정 생성
--    이메일: admin@example.com
--    비밀번호: changeme1  (로그인 후 반드시 변경하세요!)
INSERT IGNORE INTO `users` (`id`, `email`, `password_hash`, `name`, `role`)
VALUES (
  1,
  'admin@example.com',
  '$2y$10$37rN1uGyp9pVfCUfvowhROca7ug7BywT7NZfXK/8qpYlYUYBxtJIG',
  '관리자',
  'owner'
);

-- 3. settings에 owner_id 추가
ALTER TABLE `settings`
  ADD COLUMN IF NOT EXISTS `owner_id` INT NOT NULL DEFAULT 1 AFTER `id`;

UPDATE `settings` SET `owner_id` = 1 WHERE `owner_id` = 0;

ALTER TABLE `settings`
  ADD UNIQUE KEY IF NOT EXISTS `uq_owner_settings` (`owner_id`),
  ADD CONSTRAINT IF NOT EXISTS `fk_settings_owner`
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- 4. employees에 owner_id 추가
ALTER TABLE `employees`
  ADD COLUMN IF NOT EXISTS `owner_id` INT NOT NULL DEFAULT 1 AFTER `id`;

UPDATE `employees` SET `owner_id` = 1 WHERE `owner_id` = 0;

ALTER TABLE `employees`
  ADD CONSTRAINT IF NOT EXISTS `fk_employees_owner`
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- 5. work_logs에 owner_id 추가
ALTER TABLE `work_logs`
  ADD COLUMN IF NOT EXISTS `owner_id` INT NOT NULL DEFAULT 1 AFTER `id`;

UPDATE `work_logs` SET `owner_id` = 1 WHERE `owner_id` = 0;

ALTER TABLE `work_logs`
  ADD CONSTRAINT IF NOT EXISTS `fk_work_logs_owner`
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

-- 6. payroll_results에 owner_id 추가
ALTER TABLE `payroll_results`
  ADD COLUMN IF NOT EXISTS `owner_id` INT NOT NULL DEFAULT 1 AFTER `id`;

UPDATE `payroll_results` SET `owner_id` = 1 WHERE `owner_id` = 0;

ALTER TABLE `payroll_results`
  ADD CONSTRAINT IF NOT EXISTS `fk_payroll_owner`
    FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

-- 완료 확인
SELECT 'users' AS tbl, COUNT(*) AS cnt FROM users
UNION ALL SELECT 'employees', COUNT(*) FROM employees
UNION ALL SELECT 'work_logs', COUNT(*) FROM work_logs;
