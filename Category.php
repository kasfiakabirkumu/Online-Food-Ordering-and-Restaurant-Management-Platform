<?php
declare(strict_types=1);

class Category
{
    public static function all(): array
    {
        return Database::pdo()->query('SELECT * FROM categories ORDER BY name')->fetchAll();
    }

    public static function allWithCounts(): array
    {
        return Database::pdo()->query(
            'SELECT c.*, COUNT(m.id) AS item_count FROM categories c
             LEFT JOIN menu_items m ON m.category_id = c.id GROUP BY c.id ORDER BY c.name'
        )->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function nameExists(string $name, int $exceptId = 0): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM categories WHERE name = :n AND id <> :id');
        $stmt->execute([':n' => $name, ':id' => $exceptId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function create(string $name, string $description): int
    {
        $stmt = Database::pdo()->prepare('INSERT INTO categories (name, description) VALUES (:n, :d)');
        $stmt->execute([':n' => $name, ':d' => $description]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, string $description): void
    {
        $stmt = Database::pdo()->prepare('UPDATE categories SET name = :n, description = :d WHERE id = :id');
        $stmt->execute([':n' => $name, ':d' => $description, ':id' => $id]);
    }

    public static function itemCount(int $id): int
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM menu_items WHERE category_id = :id');
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM categories WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
