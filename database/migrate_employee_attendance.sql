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
