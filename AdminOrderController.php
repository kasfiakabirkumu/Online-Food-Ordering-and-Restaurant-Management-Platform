<?php
declare(strict_types=1);

/** Read / update / delete orders. */
class AdminOrderController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $status = (string)($_GET['status'] ?? '');
        $this->render('admin/orders_index', ['orders' => Order::all($status), 'status' => $status], 'Manage orders');
    }

    public function show(): void
    {
        $this->requireAdmin();
        $order = Order::find((int)($_GET['id'] ?? 0));
        if (!$order) {
            (new HomeController())->notFound();
            return;
        }
        $this->render('admin/orders_show', ['order' => $order, 'items' => Order::items((int)$order['id'])], 'Order #' . $order['id']);
    }

    public function status(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, Order::STATUSES, true) || !Order::find($id)) {
            flash('error', 'Invalid status.');
        } else {
            Order::updateStatus($id, $status);
            flash('success', "Order #$id is now " . str_replace('_', ' ', $status) . '.');
        }
        redirect('admin_order/show', ['id' => $id]);
    }

    public function delete(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        Order::delete((int)($_POST['id'] ?? 0));
        flash('success', 'Order deleted.');
        redirect('admin_order/index');
    }
}
