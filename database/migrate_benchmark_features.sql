-- benchmark features: 공지, 직원 급여 공개 설정, 근무시간 절삭 단위
ALTER TABLE stores
  ADD COLUMN notice VARCHAR(200) NULL DEFAULT NULL;

ALTER TABLE settings
  ADD COLUMN show_pay_to_employee TINYINT  NOT NULL DEFAULT 1
    COMMENT '1=직원 앱에 급여 금액 표시, 0=숨김',
  ADD COLUMN work_time_unit       SMALLINT NOT NULL DEFAULT 1
    COMMENT '근무시간 절삭 단위(분): 1=없음, 10, 30, 60';
