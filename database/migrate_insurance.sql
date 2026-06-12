-- 4대보험 적용 설정 추가 (직종별 가입 여부가 다를 수 있으므로 점포 단위 토글)
-- 2026년 요율: 국민연금 4.75%, 건강보험 3.595%, 장기요양 13.14%(건강보험료 기준), 고용보험 0.9%
ALTER TABLE settings
  ADD COLUMN apply_national_pension      TINYINT NOT NULL DEFAULT 1
    COMMENT '국민연금 근로자 부담분 공제 여부 (4.75%)',
  ADD COLUMN apply_health_insurance      TINYINT NOT NULL DEFAULT 1
    COMMENT '건강보험 근로자 부담분 공제 여부 (3.595% + 장기요양13.14%)',
  ADD COLUMN apply_employment_insurance  TINYINT NOT NULL DEFAULT 1
    COMMENT '고용보험 근로자 부담분 공제 여부 (0.9%)';
