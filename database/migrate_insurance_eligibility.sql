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
