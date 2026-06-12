-- 어드민 패널 필수 테이블
-- wage_history, audit_logs, support_tickets, calc_standards, subscriptions

CREATE TABLE IF NOT EXISTS wage_history (
  id               INT PRIMARY KEY AUTO_INCREMENT,
  store_member_id  INT NOT NULL,
  store_id         INT NOT NULL,
  owner_id         INT NOT NULL,
  hourly_wage      INT NOT NULL,
  effective_from   DATE NOT NULL,
  memo             VARCHAR(255) NOT NULL DEFAULT '',
  created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_member_date (store_member_id, effective_from)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
  id             INT PRIMARY KEY AUTO_INCREMENT,
  actor_user_id  INT NULL,
  actor_role     VARCHAR(30) NOT NULL DEFAULT '',
  action         VARCHAR(100) NOT NULL,
  target_type    VARCHAR(50) NOT NULL,
  target_id      INT NULL,
  before_value   TEXT NULL,
  after_value    TEXT NULL,
  reason         VARCHAR(500) NOT NULL DEFAULT '',
  ip_address     VARCHAR(45) NULL,
  user_agent     VARCHAR(500) NULL,
  created_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_action (action),
  INDEX idx_target (target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS support_tickets (
  id           INT PRIMARY KEY AUTO_INCREMENT,
  user_id      INT NULL,
  store_id     INT NULL,
  ticket_type  ENUM('BUG','FEATURE','BILLING','ETC') NOT NULL DEFAULT 'ETC',
  title        VARCHAR(255) NOT NULL,
  content      TEXT NOT NULL,
  status       ENUM('OPEN','IN_PROGRESS','HOLD','ANSWERED','CLOSED') NOT NULL DEFAULT 'OPEN',
  admin_reply  TEXT NULL,
  admin_memo   TEXT NULL,
  replied_at   DATETIME NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status),
  INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS calc_standards (
  id                               INT PRIMARY KEY AUTO_INCREMENT,
  year                             SMALLINT NOT NULL,
  min_hourly_wage                  INT NOT NULL,
  night_start_time                 TIME NOT NULL DEFAULT '22:00:00',
  night_end_time                   TIME NOT NULL DEFAULT '06:00:00',
  insurance_national_pension_rate  DECIMAL(6,4) NOT NULL DEFAULT 0.0450,
  insurance_health_rate            DECIMAL(6,4) NOT NULL DEFAULT 0.0354,
  insurance_long_term_care_rate    DECIMAL(6,4) NOT NULL DEFAULT 0.1281,
  insurance_employment_rate        DECIMAL(6,4) NOT NULL DEFAULT 0.0090,
  description                      VARCHAR(500) NULL,
  applies_from                     DATE NOT NULL,
  applies_to                       DATE NULL,
  is_active                        TINYINT(1) NOT NULL DEFAULT 0,
  created_at                       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at                       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_year (year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
  id          INT PRIMARY KEY AUTO_INCREMENT,
  store_id    INT NOT NULL,
  plan        ENUM('free','basic','pro') NOT NULL DEFAULT 'free',
  status      ENUM('active','suspended','cancelled') NOT NULL DEFAULT 'active',
  created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_store (store_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- users.role ENUM에 super_admin, employee 추가
ALTER TABLE users MODIFY COLUMN role ENUM('owner','admin','super_admin','employee') NOT NULL DEFAULT 'owner';

-- stores 테이블 status 컬럼 추가
ALTER TABLE stores ADD COLUMN IF NOT EXISTS status ENUM('ACTIVE','SUSPENDED','DELETED') NOT NULL DEFAULT 'ACTIVE' AFTER owner_id;
