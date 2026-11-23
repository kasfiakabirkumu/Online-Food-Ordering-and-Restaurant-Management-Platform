<?php
declare(strict_types=1);

class Order
{
    public const STATUSES = ['pending', 'preparing', 'out_for_delivery', 'delivered', 'cancelled'];

    /**
     * Create order + items + payment in one transaction.
     * $lines come from Cart::lines() (prices read from the DB).
     */
    public static function place(int $userId, array $lines, float $subtotal, float $fee, array $details, array $payment): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $total = round($subtotal + $fee, 2);
            $stmt = $pdo->prepare(
                'INSERT INTO orders (user_id, subtotal, delivery_fee, total, delivery_address, phone, notes)
                 VALUES (:u, :s, :f, :t, :a, :p, :n)'
            );
            $stmt->execute([':u' => $userId, ':s' => $subtotal, ':f' => $fee, ':t' => $total,
                            ':a' => $details['address'], ':p' => $details['phone'], ':n' => $details['notes']]);
            $orderId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price) VALUES (:o, :m, :q, :p)'
            );
            foreach ($lines as $line) {
                $itemStmt->execute([':o' => $orderId, ':m' => $line['id'], ':q' => $line['qty'], ':p' => $line['price']]);
            }

            $payStmt = $pdo->prepare(
                'INSERT INTO payments (order_id, method, status, amount, transaction_ref, card_last4)
                 VALUES (:o, :m, :s, :a, :r, :l)'
            );
            $payStmt->execute([':o' => $orderId, ':m' => $payment['method'], ':s' => $payment['status'],
                               ':a' => $total, ':r' => $payment['ref'], ':l' => $payment['last4']]);
            $pdo->commit();
            return $orderId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function forUser(int $userId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT o.*, p.method AS pay_method, p.status AS pay_status FROM orders o
             JOIN payments p ON p.order_id = o.id WHERE o.user_id = :u ORDER BY o.id DESC'
        );
        $stmt->execute([':u' => $userId]);
        return $stmt->fetchAll();
    }

    public static function all(string $status = ''): array
    {
        $sql = 'SELECT o.*, u.name AS customer, p.method AS pay_method, p.status AS pay_status FROM orders o
                JOIN users u ON u.id = o.user_id JOIN payments p ON p.order_id = o.id';
        $params = [];
        if (in_array($status, self::STATUSES, true)) {
            $sql .= ' WHERE o.status = :s';
            $params[':s'] = $status;
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY o.id DESC');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT o.*, u.name AS customer, u.email, p.method AS pay_method, p.status AS pay_status,
                    p.transaction_ref, p.card_last4
             FROM orders o JOIN users u ON u.id = o.user_id JOIN payments p ON p.order_id = o.id
             WHERE o.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function items(int $orderId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT oi.*, m.name, m.emoji FROM order_items oi
             JOIN menu_items m ON m.id = oi.menu_item_id WHERE oi.order_id = :id'
        );
        $stmt->execute([':id' => $orderId]);
        return $stmt->fetchAll();
    }

    public static function updateStatus(int $id, string $status): void
    {
        $pdo = Database::pdo();
        $pdo->prepare('UPDATE orders SET status = :s WHERE id = :id')->execute([':s' => $status, ':id' => $id]);
        // Cash on delivery is paid when the order is delivered.
        if ($status === 'delivered') {
            $pdo->prepare('UPDATE payments SET status = "paid" WHERE order_id = :id AND method = "cod"')
                ->execute([':id' => $id]);
        }
    }

    public static function delete(int $id): void
    {
        Database::pdo()->prepare('DELETE FROM orders WHERE id = :id')->execute([':id' => $id]);
    }

    public static function stats(): array
    {
        $pdo = Database::pdo();
        return [
            'orders'    => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
            'pending'   => (int)$pdo->query('SELECT COUNT(*) FROM orders WHERE status = "pending"')->fetchColumn(),
            'revenue'   => (float)$pdo->query('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = "paid"')->fetchColumn(),
            'customers' => (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "customer"')->fetchColumn(),
        ];
    }
}
