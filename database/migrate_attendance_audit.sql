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
