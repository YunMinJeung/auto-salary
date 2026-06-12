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
