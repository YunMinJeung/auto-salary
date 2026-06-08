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
