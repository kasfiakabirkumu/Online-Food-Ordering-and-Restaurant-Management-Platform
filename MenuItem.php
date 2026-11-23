<?php
declare(strict_types=1);

class MenuItem
{
    public static function search(string $q = '', int $categoryId = 0, bool $onlyAvailable = true): array
    {
        $sql = 'SELECT m.*, c.name AS category_name FROM menu_items m
                JOIN categories c ON c.id = m.category_id WHERE 1 = 1';
        $params = [];
        if ($onlyAvailable) {
            $sql .= ' AND m.is_available = 1';
        }
        if ($q !== '') {
            $sql .= ' AND (m.name LIKE :q1 OR m.description LIKE :q2)';
            $like = '%' . addcslashes($q, '%_\\') . '%';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
        }
        if ($categoryId > 0) {
            $sql .= ' AND m.category_id = :cat';
            $params[':cat'] = $categoryId;
        }
        $sql .= ' ORDER BY c.name, m.name';
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT m.*, c.name AS category_name FROM menu_items m
             JOIN categories c ON c.id = m.category_id WHERE m.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function findMany(array $ids): array
    {
        $ids = array_values(array_map('intval', $ids));
        if (!$ids) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = Database::pdo()->prepare(
            "SELECT m.*, c.name AS category_name FROM menu_items m
             JOIN categories c ON c.id = m.category_id WHERE m.id IN ($marks) ORDER BY m.name"
        );
        $stmt->execute($ids);
        return $stmt->fetchAll();
    }

    public static function count(): int
    {
        return (int)Database::pdo()->query('SELECT COUNT(*) FROM menu_items')->fetchColumn();
    }

    public static function create(array $d): int
    {
        $stmt = Database::pdo()->prepare(
            'INSERT INTO menu_items (category_id, name, description, price, emoji, is_available)
             VALUES (:c, :n, :d, :p, :e, :a)'
        );
        $stmt->execute([':c' => $d['category_id'], ':n' => $d['name'], ':d' => $d['description'],
                        ':p' => $d['price'], ':e' => $d['emoji'], ':a' => $d['is_available']]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        $stmt = Database::pdo()->prepare(
            'UPDATE menu_items SET category_id = :c, name = :n, description = :d, price = :p,
             emoji = :e, is_available = :a WHERE id = :id'
        );
        $stmt->execute([':c' => $d['category_id'], ':n' => $d['name'], ':d' => $d['description'],
                        ':p' => $d['price'], ':e' => $d['emoji'], ':a' => $d['is_available'], ':id' => $id]);
    }

    public static function isOrdered(int $id): bool
    {
        $stmt = Database::pdo()->prepare('SELECT COUNT(*) FROM order_items WHERE menu_item_id = :id');
        $stmt->execute([':id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare('DELETE FROM menu_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
