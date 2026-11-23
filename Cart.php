<?php
declare(strict_types=1);

/** Session based shopping cart. Prices are always read from the database, never from the browser. */
class Cart
{
    private const MAX_QTY = 20;

    public static function add(int $itemId, int $qty = 1): bool
    {
        $item = MenuItem::find($itemId);
        if (!$item || !$item['is_available']) {
            return false;
        }
        $current = $_SESSION['cart'][$itemId] ?? 0;
        $_SESSION['cart'][$itemId] = min(self::MAX_QTY, $current + max(1, $qty));
        return true;
    }

    public static function set(int $itemId, int $qty): void
    {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$itemId]);
        } else {
            $_SESSION['cart'][$itemId] = min(self::MAX_QTY, $qty);
        }
    }

    public static function remove(int $itemId): void
    {
        unset($_SESSION['cart'][$itemId]);
    }

    public static function clear(): void
    {
        unset($_SESSION['cart']);
    }

    public static function count(): int
    {
        return array_sum($_SESSION['cart'] ?? []);
    }

    /** @return array<int,array> cart lines with qty and subtotal */
    public static function lines(): array
    {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            return [];
        }
        $lines = [];
        $foundIds = [];
        foreach (MenuItem::findMany(array_keys($cart)) as $item) {
            $id = (int)$item['id'];
            $foundIds[$id] = true;
            if (!$item['is_available']) {
                unset($_SESSION['cart'][$id]);
                continue;
            }
            $qty = max(1, min(self::MAX_QTY, (int)$cart[$id]));
            $_SESSION['cart'][$id] = $qty;
            $lines[] = $item + ['qty' => $qty, 'subtotal' => round($qty * (float)$item['price'], 2)];
        }

        // Remove IDs that no longer exist in the database, otherwise the cart badge
        // can show items that cannot be checked out.
        foreach (array_keys($cart) as $id) {
            if (!isset($foundIds[(int)$id])) {
                unset($_SESSION['cart'][$id]);
            }
        }
        return $lines;
    }

    public static function subtotal(array $lines): float
    {
        $sum = 0.0;
        foreach ($lines as $line) {
            $sum += $line['subtotal'];
        }
        return round($sum, 2);
    }

    public static function deliveryFee(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }
        return $subtotal >= (float)config('app.free_delivery_over') ? 0.0 : (float)config('app.delivery_fee');
    }
}
