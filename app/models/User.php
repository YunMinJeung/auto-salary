<?php
class User
{
    public static function find(int $id): ?array
    {
        return DB::fetchOne('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByEmail(string $email): ?array
    {
        return DB::fetchOne('SELECT * FROM users WHERE email = ?', [trim($email)]);
    }

    public static function emailExists(string $email): bool
    {
        return DB::fetchOne('SELECT id FROM users WHERE email = ?', [trim($email)]) !== null;
    }

    public static function create(array $data): int
    {
        DB::query('
            INSERT INTO users (email, password_hash, name, role)
            VALUES (?, ?, ?, ?)
        ', [
            trim($data['email']),
            password_hash($data['password'], PASSWORD_BCRYPT),
            trim($data['name']),
            $data['role'] ?? 'owner',
        ]);
        return DB::lastInsertId();
    }

    public static function verifyPassword(string $plain, string $hash): bool
    {
        return password_verify($plain, $hash);
    }

    public static function updatePassword(int $id, string $newPassword): void
    {
        DB::query(
            'UPDATE users SET password_hash = ? WHERE id = ?',
            [password_hash($newPassword, PASSWORD_BCRYPT), $id]
        );
    }
}
