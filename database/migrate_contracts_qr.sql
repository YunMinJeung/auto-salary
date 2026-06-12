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
