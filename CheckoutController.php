<?php
declare(strict_types=1);

class CheckoutController extends Controller
{
    public function index(): void
    {
        $user = $this->requireLogin();
        $lines = Cart::lines();
        if (!$lines) {
            flash('info', 'Your cart is empty.');
            redirect('menu/index');
        }
        $subtotal = Cart::subtotal($lines);
        $fee = Cart::deliveryFee($subtotal);
        $errors = [];
        $old = ['address' => '', 'phone' => User::find($user['id'])['phone'] ?? '', 'notes' => '', 'method' => 'cod',
                'card_name' => '', 'bkash_number' => '', 'bkash_trx' => ''];

        if ($this->isPost()) {
            csrf_check();
            $old['address'] = clean((string)($_POST['address'] ?? ''));
            $old['phone'] = preg_replace('/[\s-]/', '', (string)($_POST['phone'] ?? ''));
            $old['notes'] = clean((string)($_POST['notes'] ?? ''));
            $old['method'] = (string)($_POST['method'] ?? '');
            $old['card_name'] = clean((string)($_POST['card_name'] ?? ''));
            $old['bkash_number'] = preg_replace('/[\s-]/', '', (string)($_POST['bkash_number'] ?? ''));
            $old['bkash_trx'] = strtoupper(trim((string)($_POST['bkash_trx'] ?? '')));

            if (mb_strlen($old['address']) < 10 || mb_strlen($old['address']) > 255) {
                $errors[] = 'Delivery address must be 10-255 characters.';
            }
            if (!preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $old['phone'])) {
                $errors[] = 'Enter a valid mobile number.';
            }
            if (mb_strlen($old['notes']) > 255) {
                $errors[] = 'Notes are too long (max 255 characters).';
            }

            $payment = ['method' => $old['method'], 'status' => 'pending', 'ref' => null, 'last4' => null];
            switch ($old['method']) {
                case 'cod':
                    break; // pay the rider
                case 'card':
                    $number = preg_replace('/\D/', '', (string)($_POST['card_number'] ?? ''));
                    $expiry = trim((string)($_POST['card_expiry'] ?? ''));
                    $cvv = (string)($_POST['card_cvv'] ?? '');
                    if (!preg_match('/^\d{13,19}$/', $number) || !$this->luhn($number)) {
                        $errors[] = 'Card number is not valid.';
                    }
                    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2})$/', $expiry, $m) || !$this->notExpired((int)$m[1], (int)$m[2])) {
                        $errors[] = 'Card expiry must be MM/YY and in the future.';
                    }
                    if (!preg_match('/^\d{3,4}$/', $cvv)) {
                        $errors[] = 'CVV must be 3 or 4 digits.';
                    }
                    if (!preg_match("/^[\p{L} .'-]{2,60}$/u", $old['card_name'])) {
                        $errors[] = 'Enter the name on the card.';
                    }
                    if (!$errors) {
                        // SIMULATED gateway: nothing is charged. Only the last 4 digits are kept; CVV is never stored.
                        $payment['status'] = 'paid';
                        $payment['ref'] = 'CARD-' . strtoupper(bin2hex(random_bytes(4)));
                        $payment['last4'] = substr($number, -4);
                    }
                    break;
                case 'bkash':
                    if (!preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $old['bkash_number'])) {
                        $errors[] = 'Enter a valid bKash number.';
                    }
                    if (!preg_match('/^[A-Z0-9]{8,12}$/', $old['bkash_trx'])) {
                        $errors[] = 'bKash transaction ID must be 8-12 letters/digits.';
                    }
                    if (!$errors) {
                        $payment['status'] = 'paid'; // SIMULATED confirmation
                        $payment['ref'] = $old['bkash_trx'];
                    }
                    break;
                default:
                    $errors[] = 'Please choose a payment method.';
            }

            if (!$errors) {
                $orderId = Order::place($user['id'], $lines, $subtotal, $fee,
                    ['address' => $old['address'], 'phone' => $old['phone'], 'notes' => $old['notes']], $payment);
                Cart::clear();
                flash('success', "Order #$orderId placed successfully!");
                redirect('order/show', ['id' => $orderId]);
            }
        }
        $this->render('checkout/index', [
            'lines' => $lines, 'subtotal' => $subtotal, 'fee' => $fee, 'errors' => $errors, 'old' => $old,
        ], 'Checkout');
    }

    /** Luhn checksum for card numbers. */
    private function luhn(string $number): bool
    {
        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = (int)$number[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10 === 0;
    }

    private function notExpired(int $month, int $yy): bool
    {
        $year = 2000 + $yy;
        $now = (int)date('Y') * 12 + (int)date('n');
        return ($year * 12 + $month) >= $now;
    }
}
