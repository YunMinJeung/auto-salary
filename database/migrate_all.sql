-- ===== schema.sql =====
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


-- ===== migrate_add_owner_id.sql =====
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


-- ===== alter_employee_premiums.sql =====
-- 직원별 가산수당 개별 설정 컬럼 추가
-- night / overtime / holiday 각각 type + value 2개씩 = 6개 컬럼

ALTER TABLE `employees`
  ADD COLUMN IF NOT EXISTS `night_premium_type`     VARCHAR(12) NOT NULL DEFAULT 'global' AFTER `weekly_holiday_enabled`,
  ADD COLUMN IF NOT EXISTS `night_premium_value`    DECIMAL(10,2) DEFAULT NULL            AFTER `night_premium_type`,
  ADD COLUMN IF NOT EXISTS `overtime_premium_type`  VARCHAR(12) NOT NULL DEFAULT 'global' AFTER `night_premium_value`,
  ADD COLUMN IF NOT EXISTS `overtime_premium_value` DECIMAL(10,2) DEFAULT NULL            AFTER `overtime_premium_type`,
  ADD COLUMN IF NOT EXISTS `holiday_premium_type`   VARCHAR(12) NOT NULL DEFAULT 'global' AFTER `overtime_premium_value`,
  ADD COLUMN IF NOT EXISTS `holiday_premium_value`  DECIMAL(10,2) DEFAULT NULL            AFTER `holiday_premium_type`;

-- type 값 의미:
--   global     : 사업장 설정(settings 테이블) 따름 (기본값)
--   none       : 미적용
--   multiplier : 시급 × value배 (예: value=1.2 → 기본급의 1.2배, 가산 = 0.2배)
--   fixed      : 시간당 value원 추가 (예: value=500 → 시간당 500원 가산)


-- ===== migrate_employee_attendance.sql =====
-- ══════════════════════════════════════════════════════════════
-- 알바생 출퇴근 기능 마이그레이션
-- 실행 전: DB 백업 필수
-- ══════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- 1. users 테이블에 phone 컬럼 추가
ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(20) DEFAULT NULL AFTER name;

-- 2. stores 테이블 (사업장 원장)
CREATE TABLE IF NOT EXISTS stores (
  id            INT PRIMARY KEY AUTO_INCREMENT,
  owner_id      INT NOT NULL,
  store_name    VARCHAR(200) NOT NULL DEFAULT '내 사업장',
  business_number VARCHAR(30) DEFAULT NULL,
  employee_count_type ENUM('under5','over5') NOT NULL DEFAULT 'under5',
  minimum_wage  INT NOT NULL DEFAULT 10320,
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기존 settings → stores 이전 (중복 방지)
INSERT IGNORE INTO stores (owner_id, store_name, employee_count_type, minimum_wage)
SELECT s.owner_id, s.business_name, s.employee_count_type, s.minimum_wage
FROM settings s;

-- 3. store_members 테이블 (사업장 소속 직원)
CREATE TABLE IF NOT EXISTS store_members (
  id                     INT PRIMARY KEY AUTO_INCREMENT,
  store_id               INT NOT NULL,
  user_id                INT DEFAULT NULL,       -- 알바생 로그인 계정 (없으면 NULL)
  employee_id            INT DEFAULT NULL,       -- 기존 employees 연결 (급여 연동)
  member_role            ENUM('owner','manager','employee') NOT NULL DEFAULT 'employee',
  name                   VARCHAR(100) NOT NULL,
  phone                  VARCHAR(20) DEFAULT NULL,
  hourly_wage            INT NOT NULL DEFAULT 10320,
  weekly_scheduled_hours DECIMAL(4,1) NOT NULL DEFAULT 40.0,
  weekly_scheduled_days  TINYINT NOT NULL DEFAULT 5,
  weekly_holiday_enabled TINYINT(1) NOT NULL DEFAULT 1,
  night_premium_type     VARCHAR(12) NOT NULL DEFAULT 'global',
  night_premium_value    DECIMAL(10,2) DEFAULT NULL,
  overtime_premium_type  VARCHAR(12) NOT NULL DEFAULT 'global',
  overtime_premium_value DECIMAL(10,2) DEFAULT NULL,
  holiday_premium_type   VARCHAR(12) NOT NULL DEFAULT 'global',
  holiday_premium_value  DECIMAL(10,2) DEFAULT NULL,
  employment_start_date  DATE DEFAULT NULL,
  employment_end_date    DATE DEFAULT NULL,
  is_active              TINYINT(1) NOT NULL DEFAULT 1,
  memo                   TEXT DEFAULT NULL,
  created_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id) REFERENCES stores(id) ON DELETE CASCADE,
  FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기존 employees → store_members 이전
INSERT IGNORE INTO store_members (
  store_id, employee_id, member_role, name,
  hourly_wage, weekly_scheduled_hours, weekly_scheduled_days, weekly_holiday_enabled,
  night_premium_type, night_premium_value,
  overtime_premium_type, overtime_premium_value,
  holiday_premium_type, holiday_premium_value,
  employment_start_date, employment_end_date, is_active, memo
)
SELECT
  st.id, e.id, 'employee', e.name,
  e.hourly_wage, e.weekly_scheduled_hours, e.weekly_scheduled_days, e.weekly_holiday_enabled,
  e.night_premium_type, e.night_premium_value,
  e.overtime_premium_type, e.overtime_premium_value,
  e.holiday_premium_type, e.holiday_premium_value,
  e.employment_start_date, e.employment_end_date,
  CASE WHEN e.employment_end_date IS NULL OR e.employment_end_date >= CURDATE() THEN 1 ELSE 0 END,
  e.memo
FROM employees e
JOIN stores st ON st.owner_id = e.owner_id;

-- 4. attendance_logs 테이블 (출퇴근 기록)
CREATE TABLE IF NOT EXISTS attendance_logs (
  id                INT PRIMARY KEY AUTO_INCREMENT,
  store_id          INT NOT NULL,
  store_member_id   INT NOT NULL,
  clock_in_at       DATETIME NOT NULL,
  clock_out_at      DATETIME DEFAULT NULL,
  break_minutes     INT NOT NULL DEFAULT 0,
  status            ENUM('working','completed','missing_clock_out',
                         'correction_requested','corrected','approved')
                    NOT NULL DEFAULT 'working',
  source            ENUM('mobile_web','pwa','owner_manual','admin_adjusted')
                    NOT NULL DEFAULT 'mobile_web',
  clock_in_latitude  DECIMAL(10,7) DEFAULT NULL,
  clock_in_longitude DECIMAL(10,7) DEFAULT NULL,
  clock_out_latitude  DECIMAL(10,7) DEFAULT NULL,
  clock_out_longitude DECIMAL(10,7) DEFAULT NULL,
  memo              TEXT DEFAULT NULL,
  created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id)        REFERENCES stores(id)        ON DELETE CASCADE,
  FOREIGN KEY (store_member_id) REFERENCES store_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. attendance_correction_requests 테이블 (출퇴근 수정 요청)
CREATE TABLE IF NOT EXISTS attendance_correction_requests (
  id                      INT PRIMARY KEY AUTO_INCREMENT,
  attendance_log_id       INT DEFAULT NULL,     -- NULL = 출근 누락으로 새 기록 요청
  store_id                INT NOT NULL,
  store_member_id         INT NOT NULL,
  requested_clock_in_at   DATETIME DEFAULT NULL,
  requested_clock_out_at  DATETIME DEFAULT NULL,
  reason                  TEXT NOT NULL,
  status                  ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  owner_comment           TEXT DEFAULT NULL,
  created_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at              TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (store_id)        REFERENCES stores(id)        ON DELETE CASCADE,
  FOREIGN KEY (store_member_id) REFERENCES store_members(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- 확인
SELECT 'stores' AS tbl, COUNT(*) AS cnt FROM stores
UNION ALL SELECT 'store_members', COUNT(*) FROM store_members
UNION ALL SELECT 'attendance_logs', COUNT(*) FROM attendance_logs;


-- ===== migrate_minimum_wages.sql =====
-- ══════════════════════════════════════════════════════
-- 연도별 최저시급 테이블 추가
-- 글로벌 테이블 (owner_id 없음 — 법정 기준이므로 공통)
-- ══════════════════════════════════════════════════════

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS minimum_wages (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  year           SMALLINT NOT NULL UNIQUE COMMENT '적용 연도 (예: 2026)',
  hourly_wage    INT NOT NULL COMMENT '시급 (원)',
  monthly_wage   INT NOT NULL DEFAULT 0 COMMENT '월환산액 = 시급 × 209시간',
  effective_from DATE DEFAULT NULL COMMENT '적용 시작일',
  effective_to   DATE DEFAULT NULL COMMENT '적용 종료일',
  memo           VARCHAR(500) DEFAULT NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 기존 법정 최저시급 데이터 삽입
INSERT IGNORE INTO minimum_wages
  (year, hourly_wage, monthly_wage, effective_from, effective_to, memo)
VALUES
  (2023,  9620, 2010580, '2023-01-01', '2023-12-31', '고용노동부 고시'),
  (2024,  9860, 2060740, '2024-01-01', '2024-12-31', '고용노동부 고시'),
  (2025, 10030, 2096270, '2025-01-01', '2025-12-31', '고용노동부 고시'),
  (2026, 10320, 2156880, '2026-01-01', '2026-12-31', '고용노동부 고시');

-- 확인
SELECT year, FORMAT(hourly_wage, 0) AS hourly, FORMAT(monthly_wage, 0) AS monthly
FROM minimum_wages ORDER BY year;


-- ===== migrate_attendance_audit.sql =====
-- ============================================================
-- 출퇴근 원본 보존 + 수정 이력 테이블 마이그레이션
-- ============================================================

-- 1. clock_in_at / clock_out_at 컬럼 이름 변경
ALTER TABLE attendance_logs
  CHANGE COLUMN clock_in_at  original_clock_in_at  DATETIME NOT NULL,
  CHANGE COLUMN clock_out_at original_clock_out_at DATETIME NULL DEFAULT NULL;

-- 2. 정정 필드 + 휴게·생성자 컬럼 추가
ALTER TABLE attendance_logs
  ADD COLUMN adjusted_clock_in_at   DATETIME      NULL DEFAULT NULL AFTER original_clock_in_at,
  ADD COLUMN adjusted_clock_out_at  DATETIME      NULL DEFAULT NULL AFTER original_clock_out_at,
  ADD COLUMN original_break_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER adjusted_clock_out_at,
  ADD COLUMN adjusted_break_minutes SMALLINT UNSIGNED NULL DEFAULT NULL   AFTER original_break_minutes,
  ADD COLUMN created_by_user_id     INT UNSIGNED  NULL DEFAULT NULL AFTER source;

-- 3. status ENUM 확장 (기존 approved 포함 유지 + 신규 상태 추가)
ALTER TABLE attendance_logs
  MODIFY COLUMN status ENUM(
    'working',
    'completed',
    'approved',
    'correction_requested',
    'corrected',
    'payroll_confirmed',
    'payroll_paid'
  ) NOT NULL DEFAULT 'working';

-- 4. 수정 이력 테이블 생성 (삭제 불가 설계 — 앱 레벨에서 DELETE 금지)
CREATE TABLE IF NOT EXISTS attendance_adjustment_logs (
  id                       INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  attendance_log_id        INT UNSIGNED     NOT NULL,
  store_id                 INT UNSIGNED     NOT NULL,
  store_member_id          INT UNSIGNED     NOT NULL,
  changed_by_user_id       INT UNSIGNED     NOT NULL,
  changed_by_role          ENUM('owner','admin','system') NOT NULL DEFAULT 'owner',
  before_clock_in_at       DATETIME         NULL,
  before_clock_out_at      DATETIME         NULL,
  after_clock_in_at        DATETIME         NULL,
  after_clock_out_at       DATETIME         NULL,
  before_break_minutes     SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  after_break_minutes      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  reason                   TEXT             NOT NULL,
  change_type              ENUM('owner_manual_edit','employee_request_approved','admin_adjustment','system_auto_close') NOT NULL,
  employee_visible         TINYINT(1)       NOT NULL DEFAULT 1,
  employee_acknowledged_at DATETIME         NULL DEFAULT NULL,
  created_at               DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_att_log  (attendance_log_id),
  INDEX idx_store    (store_id),
  INDEX idx_member   (store_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== migrate_benchmark_features.sql =====
-- benchmark features: 공지, 직원 급여 공개 설정, 근무시간 절삭 단위
ALTER TABLE stores
  ADD COLUMN notice VARCHAR(200) NULL DEFAULT NULL;

ALTER TABLE settings
  ADD COLUMN show_pay_to_employee TINYINT  NOT NULL DEFAULT 1
    COMMENT '1=직원 앱에 급여 금액 표시, 0=숨김',
  ADD COLUMN work_time_unit       SMALLINT NOT NULL DEFAULT 1
    COMMENT '근무시간 절삭 단위(분): 1=없음, 10, 30, 60';


-- ===== migrate_insurance.sql =====
-- 4대보험 적용 설정 추가 (직종별 가입 여부가 다를 수 있으므로 점포 단위 토글)
-- 2026년 요율: 국민연금 4.75%, 건강보험 3.595%, 장기요양 13.14%(건강보험료 기준), 고용보험 0.9%
ALTER TABLE settings
  ADD COLUMN apply_national_pension      TINYINT NOT NULL DEFAULT 1
    COMMENT '국민연금 근로자 부담분 공제 여부 (4.75%)',
  ADD COLUMN apply_health_insurance      TINYINT NOT NULL DEFAULT 1
    COMMENT '건강보험 근로자 부담분 공제 여부 (3.595% + 장기요양13.14%)',
  ADD COLUMN apply_employment_insurance  TINYINT NOT NULL DEFAULT 1
    COMMENT '고용보험 근로자 부담분 공제 여부 (0.9%)';


-- ===== migrate_insurance_eligibility.sql =====
-- 4대보험 가입 의무 체크 및 경고 이력 테이블
CREATE TABLE IF NOT EXISTS employee_insurance_settings (
  id                               INT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
  store_id                         INT UNSIGNED     NOT NULL,
  store_member_id                  INT UNSIGNED     NOT NULL,
  expected_employment_duration     ENUM('under1m','1m_to_3m','over3m','undefined') NOT NULL DEFAULT 'undefined',
  employment_type                  ENUM('regular','daily','short_hours','needs_review') NOT NULL DEFAULT 'regular',
  monthly_scheduled_hours          DECIMAL(6,2)     NOT NULL DEFAULT 0.00,
  national_pension_status          ENUM('likely_required','possibly_exempt','needs_review') NULL,
  health_insurance_status          ENUM('likely_required','possibly_exempt','needs_review') NULL,
  employment_insurance_status      ENUM('likely_required','possibly_exempt','needs_review') NULL,
  industrial_accident_status       ENUM('required')  NOT NULL DEFAULT 'required',
  user_selected_status             ENUM('enrolled','not_enrolled','needs_review') NOT NULL DEFAULT 'needs_review',
  system_judgment_json             JSON             NULL,
  warning_acknowledged             TINYINT          NOT NULL DEFAULT 0,
  warning_acknowledged_at          DATETIME         NULL,
  warning_acknowledged_by_user_id  INT UNSIGNED     NULL,
  memo                             TEXT             NULL,
  created_at                       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                       DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_member (store_id, store_member_id),
  INDEX idx_store (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== migrate_contracts_qr.sql =====
-- ================================================================
-- 1. 근로계약서 이력
-- ================================================================
CREATE TABLE IF NOT EXISTS employment_contracts (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id              INT UNSIGNED NOT NULL,
  store_member_id       INT UNSIGNED NOT NULL,
  form_data             JSON         NOT NULL,
  contract_start_date   DATE         NULL,
  contract_end_date     DATE         NULL,
  created_by_user_id    INT UNSIGNED NOT NULL,
  pdf_downloaded        TINYINT      NOT NULL DEFAULT 0,
  pdf_downloaded_at     DATETIME     NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_member (store_id, store_member_id),
  INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 2. 미성년자(연소근로자) 동의서 이력
-- ================================================================
CREATE TABLE IF NOT EXISTS minor_consent_forms (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id              INT UNSIGNED NOT NULL,
  store_member_id       INT UNSIGNED NOT NULL,
  form_data             JSON         NOT NULL,
  date_of_birth         DATE         NULL,
  age_at_signing        TINYINT      NULL,
  guardian_name         VARCHAR(100) NULL,
  guardian_relation     VARCHAR(50)  NULL,
  created_by_user_id    INT UNSIGNED NOT NULL,
  pdf_downloaded        TINYINT      NOT NULL DEFAULT 0,
  pdf_downloaded_at     DATETIME     NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_member (store_id, store_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 3. store_members 에 연소근로자 플래그 추가
-- ================================================================
ALTER TABLE store_members
  ADD COLUMN IF NOT EXISTS is_minor TINYINT NOT NULL DEFAULT 0;

-- ================================================================
-- 4. 매장 QR 토큰
-- ================================================================
CREATE TABLE IF NOT EXISTS store_qr_tokens (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id              INT UNSIGNED NOT NULL,
  token_hash            VARCHAR(64)  NOT NULL,
  is_active             TINYINT      NOT NULL DEFAULT 1,
  expires_at            DATETIME     NULL,
  revoked_at            DATETIME     NULL,
  created_by_user_id    INT UNSIGNED NOT NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_hash (token_hash),
  INDEX idx_store (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ================================================================
-- 5. QR 스캔 이력
-- ================================================================
CREATE TABLE IF NOT EXISTS qr_scan_logs (
  id                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id              INT UNSIGNED NOT NULL,
  employee_user_id      INT UNSIGNED NOT NULL,
  token_id              INT UNSIGNED NOT NULL,
  action                ENUM('view','clock_in','clock_out') NOT NULL DEFAULT 'view',
  ip_address            VARCHAR(45)  NULL,
  user_agent            VARCHAR(500) NULL,
  success               TINYINT      NOT NULL DEFAULT 0,
  failure_reason        VARCHAR(200) NULL,
  created_at            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_store (store_id),
  INDEX idx_employee (employee_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== migrate_employee_member_link.sql =====
-- employees.store_member_id 연결 컬럼 추가
-- 기존 employee 레코드는 NULL (미연결 상태)
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS store_member_id INT NULL DEFAULT NULL;


-- ===== migrate_date_of_birth.sql =====
ALTER TABLE store_members ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL DEFAULT NULL;


-- ===== migrate_work_schedule.sql =====
ALTER TABLE store_members
  ADD COLUMN IF NOT EXISTS work_start_time     TIME         NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS work_end_time       TIME         NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS daily_break_minutes INT UNSIGNED NULL DEFAULT NULL;


-- ===== migrate_schema_drift.sql =====
-- ══════════════════════════════════════════════════════════════
-- 스키마 드리프트 해소 마이그레이션
-- 코드(모델)가 사용하지만 database/ DDL에 누락되어 있던
-- 4개 테이블과 여러 컬럼/ENUM 값을 보강한다.
-- 실행 전: DB 백업 필수.
-- ══════════════════════════════════════════════════════════════

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ════════════════════════════════════════════════════════════
-- 1. store_members 누락 컬럼
--    (Invitation.php / StoreMember.php 가 사용)
-- ════════════════════════════════════════════════════════════
ALTER TABLE store_members
  ADD COLUMN IF NOT EXISTS account_status
      ENUM('no_account','invited','linked') NOT NULL DEFAULT 'no_account',
  ADD COLUMN IF NOT EXISTS employment_status
      ENUM('active','on_leave','terminated') NOT NULL DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS trial_end_date     DATE         NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS trial_hourly_wage  INT          NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS invitation_id      INT UNSIGNED NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS joined_at          DATETIME     NULL DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS created_by_user_id INT UNSIGNED NULL DEFAULT NULL;

-- ════════════════════════════════════════════════════════════
-- 2. attendance_logs 누락 컬럼 / ENUM 보강
--    (AttendanceLog.php / AttendanceChangeRequest.php 가 사용)
-- ════════════════════════════════════════════════════════════
ALTER TABLE attendance_logs
  ADD COLUMN IF NOT EXISTS employee_user_id INT UNSIGNED NULL DEFAULT NULL AFTER store_member_id,
  ADD COLUMN IF NOT EXISTS record_status
      ENUM('original','pending_employee_review','accepted','objected',
           'owner_forced_confirmed','corrected_confirmed') NOT NULL DEFAULT 'original',
  ADD COLUMN IF NOT EXISTS active_change_request_id INT UNSIGNED NULL DEFAULT NULL;

-- source ENUM 에 qr / pwa 값 추가 (clockIn 이 'qr','pwa' 저장)
ALTER TABLE attendance_logs
  MODIFY COLUMN source
    ENUM('mobile_web','pwa','qr','owner_manual','admin_adjusted')
    NOT NULL DEFAULT 'mobile_web';

-- ════════════════════════════════════════════════════════════
-- 3. work_logs 누락 컬럼
--    (WorkLog.php 가 store_id / is_employer_early_leave 를 사용)
-- ════════════════════════════════════════════════════════════
ALTER TABLE work_logs
  ADD COLUMN IF NOT EXISTS store_id INT NULL DEFAULT NULL AFTER owner_id,
  ADD COLUMN IF NOT EXISTS is_employer_early_leave TINYINT(1) NOT NULL DEFAULT 0 AFTER is_early_leave;

-- ════════════════════════════════════════════════════════════
-- 4. attendance_change_requests (점주→직원 수정 요청 큐)
--    AttendanceChangeRequest.php 전용 테이블
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS attendance_change_requests (
  id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id                    INT UNSIGNED NOT NULL,
  attendance_log_id           INT UNSIGNED NOT NULL,
  store_member_id             INT UNSIGNED NOT NULL,
  requested_by_user_id        INT UNSIGNED NOT NULL,
  status                      ENUM('pending_employee_review','accepted','objected',
                                    'counter_proposed','owner_forced_confirmed',
                                    'corrected_confirmed') NOT NULL DEFAULT 'pending_employee_review',
  -- 원본 / 점주 제안 값
  original_clock_in           DATETIME     NULL,
  original_clock_out          DATETIME     NULL,
  original_break_min          SMALLINT UNSIGNED NULL,
  proposed_clock_in           DATETIME     NULL,
  proposed_clock_out          DATETIME     NULL,
  proposed_break_min          SMALLINT UNSIGNED NULL,
  change_reason               TEXT         NULL,
  -- 직원 응답
  employee_response_at        DATETIME     NULL,
  employee_objection          TEXT         NULL,
  employee_requested_clock_in   DATETIME   NULL,
  employee_requested_clock_out  DATETIME   NULL,
  employee_requested_break_min  SMALLINT UNSIGNED NULL,
  objection_status            ENUM('submitted','accepted','rejected','counter_proposed') NULL DEFAULT NULL,
  -- 점주 처리
  owner_response              TEXT         NULL,
  owner_response_at           DATETIME     NULL,
  owner_processed_by          INT UNSIGNED NULL,
  -- 재수정 제안
  counter_clock_in            DATETIME     NULL,
  counter_clock_out           DATETIME     NULL,
  counter_break_min           SMALLINT UNSIGNED NULL,
  counter_reason              TEXT         NULL,
  -- 강제 확정
  is_force_confirmed          TINYINT(1)   NOT NULL DEFAULT 0,
  force_reason                TEXT         NULL,
  force_confirmed_by          INT UNSIGNED NULL,
  force_confirmed_at          DATETIME     NULL,
  -- 협의 확정
  resolution_note             TEXT         NULL,
  resolved_at                 DATETIME     NULL,
  created_at                  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store   (store_id),
  INDEX idx_log     (attendance_log_id),
  INDEX idx_member  (store_member_id),
  INDEX idx_status  (store_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 5. employee_invitations (직원 초대)
--    Invitation.php 전용 테이블
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS employee_invitations (
  id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id                    INT UNSIGNED NOT NULL,
  store_member_id             INT UNSIGNED NULL DEFAULT NULL,
  invited_name                VARCHAR(100) NOT NULL,
  hourly_wage                 INT          NULL DEFAULT NULL,
  weekly_contract_hours       DECIMAL(4,1) NULL DEFAULT NULL,
  weekly_contract_days        TINYINT      NULL DEFAULT NULL,
  weekly_holiday_pay_enabled  TINYINT(1)   NOT NULL DEFAULT 1,
  hire_date                   DATE         NULL DEFAULT NULL,
  invited_phone               VARCHAR(20)  NULL DEFAULT NULL,
  invited_email               VARCHAR(255) NULL DEFAULT NULL,
  token                       VARCHAR(64)  NOT NULL,
  status                      ENUM('pending','accepted','cancelled') NOT NULL DEFAULT 'pending',
  expires_at                  DATETIME     NULL DEFAULT NULL,
  accepted_by_user_id         INT UNSIGNED NULL DEFAULT NULL,
  accepted_at                 DATETIME     NULL DEFAULT NULL,
  created_by_user_id          INT UNSIGNED NOT NULL,
  created_at                  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_token (token),
  INDEX idx_store  (store_id),
  INDEX idx_member (store_member_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 6. labor_risk_alerts (노무 리스크 알림)
--    LaborRiskAlert.php 전용 테이블
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS labor_risk_alerts (
  id                       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id                 INT UNSIGNED NOT NULL,
  store_member_id          INT UNSIGNED NULL DEFAULT NULL,
  related_type             VARCHAR(50)  NOT NULL,
  related_id               INT UNSIGNED NULL DEFAULT NULL,
  alert_code               VARCHAR(60)  NOT NULL,
  severity                 ENUM('danger','warning','info') NOT NULL DEFAULT 'info',
  visibility_scope         VARCHAR(40)  NOT NULL DEFAULT 'owner_only',
  title                    VARCHAR(200) NOT NULL,
  message                  TEXT         NOT NULL,
  employee_message         TEXT         NULL DEFAULT NULL,
  legal_basis              TEXT         NULL DEFAULT NULL,
  status                   ENUM('open','acknowledged','resolved','ignored') NOT NULL DEFAULT 'open',
  acknowledged_by_user_id  INT UNSIGNED NULL DEFAULT NULL,
  acknowledged_at          DATETIME     NULL DEFAULT NULL,
  resolved_at              DATETIME     NULL DEFAULT NULL,
  ignored_at               DATETIME     NULL DEFAULT NULL,
  created_at               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at               DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_owner  (owner_id),
  INDEX idx_member (store_member_id),
  INDEX idx_status (owner_id, status),
  INDEX idx_code   (owner_id, alert_code, related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ════════════════════════════════════════════════════════════
-- 7. employee_record_responses (직원 기록 확인/이의제기 응답)
--    EmployeeRecordResponse.php 전용 테이블
-- ════════════════════════════════════════════════════════════
CREATE TABLE IF NOT EXISTS employee_record_responses (
  id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id          INT UNSIGNED NOT NULL,
  store_member_id   INT UNSIGNED NOT NULL,
  related_type      VARCHAR(50)  NOT NULL,
  related_id        INT UNSIGNED NULL DEFAULT NULL,
  response_type     ENUM('acknowledge','objection') NOT NULL,
  message           TEXT         NULL DEFAULT NULL,
  status            ENUM('submitted','resolved') NOT NULL DEFAULT 'submitted',
  responded_at      DATETIME     NULL DEFAULT NULL,
  created_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_owner   (owner_id),
  INDEX idx_member  (store_member_id),
  INDEX idx_related (related_type, related_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- 확인
SELECT 'attendance_change_requests' AS tbl, COUNT(*) AS cnt FROM attendance_change_requests
UNION ALL SELECT 'employee_invitations',      COUNT(*) FROM employee_invitations
UNION ALL SELECT 'labor_risk_alerts',         COUNT(*) FROM labor_risk_alerts
UNION ALL SELECT 'employee_record_responses', COUNT(*) FROM employee_record_responses;


-- ===== migrate_schedules.sql =====
-- schedules 테이블 생성 — 근무표 일정 (보스몬 벤치마킹)
CREATE TABLE IF NOT EXISTS schedules (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id      INT UNSIGNED NOT NULL,
  store_id      INT UNSIGNED NOT NULL,
  employee_id   INT UNSIGNED NOT NULL   COMMENT 'store_members.id 참조',
  schedule_date DATE         NOT NULL,
  start_time    TIME         NOT NULL,
  end_time      TIME         NOT NULL,
  break_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  memo          TEXT         NULL DEFAULT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_owner_store_week (owner_id, store_id, schedule_date),
  INDEX idx_employee         (employee_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ===== migrate_leave_records.sql =====
-- leave_records 테이블 생성 — 연차/휴가 기록
CREATE TABLE IF NOT EXISTS leave_records (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  owner_id      INT UNSIGNED NOT NULL,
  store_id      INT UNSIGNED NOT NULL,
  employee_id   INT UNSIGNED NOT NULL   COMMENT 'store_members.id 참조',
  leave_type    ENUM('annual','sick','other') NOT NULL DEFAULT 'annual',
  start_date    DATE         NOT NULL,
  end_date      DATE         NOT NULL,
  days          DECIMAL(4,1) NOT NULL DEFAULT 1.0,
  status        ENUM('pending','approved','rejected') NOT NULL DEFAULT 'approved',
  memo          TEXT         NULL DEFAULT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_owner_emp  (owner_id, employee_id),
  INDEX idx_store      (store_id),
  INDEX idx_start_date (start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 급여명세서
CREATE TABLE IF NOT EXISTS payslips (
  id                        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  store_id                  INT UNSIGNED NOT NULL,
  owner_id                  INT UNSIGNED NOT NULL,
  employee_id               INT UNSIGNED NOT NULL,
  payroll_result_id         INT UNSIGNED NULL DEFAULT NULL,
  period_start              DATE         NOT NULL,
  period_end                DATE         NOT NULL,
  payment_date              DATE         NULL DEFAULT NULL,
  version                   SMALLINT UNSIGNED NOT NULL DEFAULT 1,
  status                    ENUM('DRAFT','CONFIRMED','ISSUED','CORRECTED','CANCELLED') NOT NULL DEFAULT 'DRAFT',
  pay_period_type           ENUM('MONTHLY','WEEKLY','CUSTOM') NOT NULL DEFAULT 'MONTHLY',
  period_label              VARCHAR(100) NOT NULL DEFAULT '',
  gross_pay                 DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_deductions          DECIMAL(12,2) NOT NULL DEFAULT 0,
  net_pay                   DECIMAL(12,2) NOT NULL DEFAULT 0,
  snapshot_json             LONGTEXT     NOT NULL,
  issued_at                 DATETIME     NULL DEFAULT NULL,
  issued_by                 INT UNSIGNED NULL DEFAULT NULL,
  corrected_from_payslip_id INT UNSIGNED NULL DEFAULT NULL,
  correction_reason         TEXT         NULL DEFAULT NULL,
  cancelled_at              DATETIME     NULL DEFAULT NULL,
  cancelled_by              INT UNSIGNED NULL DEFAULT NULL,
  cancellation_reason       TEXT         NULL DEFAULT NULL,
  created_at                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_store_employee  (store_id, employee_id),
  INDEX idx_owner           (owner_id),
  INDEX idx_period          (period_start, period_end),
  INDEX idx_status          (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
