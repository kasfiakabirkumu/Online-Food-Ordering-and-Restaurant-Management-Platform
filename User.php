<?php
declare(strict_types=1);

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT id, name, email, phone, role FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(string $name, string $email, string $passwordHash, string $phone): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO users (name, email, password_hash, phone, role) VALUES (:n, :e, :p, :ph, "customer")'
        );
        $stmt->execute([':n' => $name, ':e' => $email, ':p' => $passwordHash, ':ph' => $phone]);
        return (int)Database::pdo()->lastInsertId();
    }
}
