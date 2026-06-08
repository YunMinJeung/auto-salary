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
