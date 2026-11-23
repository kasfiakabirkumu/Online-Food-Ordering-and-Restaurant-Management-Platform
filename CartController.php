<?php
declare(strict_types=1);

class CartController extends Controller
{
    public function index(): void
    {
        $lines = Cart::lines();
        $subtotal = Cart::subtotal($lines);
        $this->render('cart/index', [
            'lines' => $lines, 'subtotal' => $subtotal, 'fee' => Cart::deliveryFee($subtotal),
        ], 'Your cart');
    }

    public function add(): void
    {
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        if (Cart::add($id, 1)) {
            flash('success', 'Item added to your cart.');
        } else {
            flash('error', 'That item is not available.');
        }
        $back = $_SERVER['HTTP_REFERER'] ?? '';
        if ($back !== '' && parse_url($back, PHP_URL_HOST) === ($_SERVER['SERVER_NAME'] ?? '')) {
            header('Location: ' . $back);
            exit;
        }
        redirect('menu/index');
    }

    public function update(): void
    {
        $this->requirePost();
        if (isset($_POST['remove'])) {
            Cart::remove((int)$_POST['remove']);
            flash('info', 'Item removed.');
        } elseif (isset($_POST['qty']) && is_array($_POST['qty'])) {
            foreach ($_POST['qty'] as $id => $qty) {
                Cart::set((int)$id, (int)$qty);
            }
            flash('success', 'Cart updated.');
        }
        redirect('cart/index');
    }
}
