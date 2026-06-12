<?php
/**
 * Employee — store_members 테이블의 파사드.
 * work_logs.employee_id는 이제 store_members.id를 참조한다.
 * 기존 코드 호환성을 위해 메서드 시그니처는 유지.
 */
class Employee
{
    // ─── 조회 ─────────────────────────────────────────────────

    public static function all(): array
    {
        return DB::fetchAll(
            'SELECT sm.*, s.owner_id, sm.id AS store_member_id
             FROM store_members sm
             JOIN stores s ON s.id = sm.store_id
             WHERE sm.store_id = ? AND sm.is_active = 1
               AND (sm.employment_end_date IS NULL OR sm.employment_end_date >= CURDATE())
             ORDER BY sm.name ASC',
            [Auth::storeId()]
        );
    }

    public static function allIncludeRetired(): array
    {
        return DB::fetchAll(
            'SELECT sm.*, s.owner_id, sm.id AS store_member_id
             FROM store_members sm
             JOIN stores s ON s.id = sm.store_id
             WHERE sm.store_id = ?
             ORDER BY sm.name ASC',
            [Auth::storeId()]
        );
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne(
            'SELECT sm.*, s.owner_id, sm.id AS store_member_id
             FROM store_members sm
             JOIN stores s ON s.id = sm.store_id
             WHERE sm.id = ? AND sm.store_id = ?',
            [$id, Auth::storeId()]
        );
    }

    // ─── 생성 — StoreMemberController가 직접 처리하므로 여기선 no-op ─
    // 하위 호환 유지용 (호출은 StoreMemberController에서만 함)

    public static function create(array $data): int
    {
        // Phase 3 이후 StoreMemberController가 store_members에 직접 저장.
        // 이 메서드는 호출되지 않아야 하지만 안전하게 0 반환.
        return 0;
    }

    public static function update(int $id, array $data): void
    {
        // StoreMemberController가 StoreMember::update()를 직접 호출.
    }

    public static function linkMember(int $id, int $memberId): void
    {
        // 구 employees 레코드를 새로 생성된 store_members 행에 연결 (마이그레이션 경로).
        DB::query(
            'UPDATE employees SET store_member_id = ? WHERE id = ? AND store_id = ?',
            [$memberId, $id, Auth::storeId()]
        );
    }

    public static function delete(int $id): void
    {
        // StoreMemberController가 StoreMember::delete()를 직접 호출.
    }
}
