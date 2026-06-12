-- employees.store_member_id 연결 컬럼 추가
-- 기존 employee 레코드는 NULL (미연결 상태)
ALTER TABLE employees
    ADD COLUMN IF NOT EXISTS store_member_id INT NULL DEFAULT NULL;
