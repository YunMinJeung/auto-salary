<?php
class Store
{
    public static function findByOwner(int $ownerId): ?array
    {
        return DB::fetchOne('SELECT * FROM stores WHERE owner_id = ?', [$ownerId]);
    }

    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM stores WHERE id = ?', [$id]);
    }

    public static function create(array $data): int
    {
        DB::query(
            'INSERT INTO stores (owner_id, store_name, employee_count_type, minimum_wage) VALUES (?, ?, ?, ?)',
            [
                $data['owner_id'],
                $data['store_name'],
                $data['employee_count_type'] ?? 'under5',
                $data['minimum_wage']        ?? DEFAULT_MIN_WAGE,
            ]
        );
        return DB::lastInsertId();
    }

    public static function update(int $id, int $ownerId, array $data): void
    {
        DB::query(
            'UPDATE stores SET store_name=?, employee_count_type=?, minimum_wage=? WHERE id=? AND owner_id=?',
            [
                $data['store_name'],
                $data['employee_count_type'],
                (int)$data['minimum_wage'],
                $id,
                $ownerId,
            ]
        );
    }
}
