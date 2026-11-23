<?php
declare(strict_types=1);

class AdminController extends Controller
{
    public function dashboard(): void
    {
        $this->requireAdmin();
        $this->render('admin/dashboard', [
            'stats'  => Order::stats() + ['items' => MenuItem::count()],
            'recent' => array_slice(Order::all(), 0, 8),
        ], 'Admin dashboard');
    }
}
