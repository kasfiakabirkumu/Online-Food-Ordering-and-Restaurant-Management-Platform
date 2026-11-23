<?php
declare(strict_types=1);

/** Full CRUD for menu items (entity 1). */
class AdminMenuController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/menu_index', ['items' => MenuItem::search('', 0, false)], 'Manage menu');
    }

    // GET: show create/edit form
    public function form(): void
    {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $item = $id ? MenuItem::find($id) : null;
        if ($id && !$item) {
            (new HomeController())->notFound();
            return;
        }
        $this->showForm($item ?? ['id' => 0, 'name' => '', 'description' => '', 'price' => '', 'emoji' => '🍽️',
                                  'category_id' => 0, 'is_available' => 1], []);
    }

    // POST: create (id = 0) or update
    public function save(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'name'         => clean((string)($_POST['name'] ?? '')),
            'description'  => clean((string)($_POST['description'] ?? '')),
            'price'        => trim((string)($_POST['price'] ?? '')),
            'emoji'        => trim((string)($_POST['emoji'] ?? '')),
            'category_id'  => (int)($_POST['category_id'] ?? 0),
            'is_available' => isset($_POST['is_available']) ? 1 : 0,
        ];
        $errors = [];
        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 80) {
            $errors[] = 'Name must be 2-80 characters.';
        }
        if (mb_strlen($data['description']) > 255) {
            $errors[] = 'Description is too long (max 255).';
        }
        if (!preg_match('/^\d{1,6}(\.\d{1,2})?$/', $data['price']) || (float)$data['price'] <= 0) {
            $errors[] = 'Price must be a positive number with at most 2 decimals.';
        }
        if ($data['emoji'] === '' || mb_strlen($data['emoji']) > 4) {
            $data['emoji'] = '🍽️';
        }
        if (!Category::find($data['category_id'])) {
            $errors[] = 'Please choose a category.';
        }
        if ($errors) {
            $this->showForm($data + ['id' => $id], $errors);
            return;
        }
        if ($id) {
            MenuItem::update($id, $data);
            flash('success', 'Menu item updated.');
        } else {
            MenuItem::create($data);
            flash('success', 'Menu item created.');
        }
        redirect('admin_menu/index');
    }

    public function delete(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        if (MenuItem::isOrdered($id)) {
            flash('error', 'This item appears in past orders, so it cannot be deleted. Mark it as unavailable instead.');
        } else {
            MenuItem::delete($id);
            flash('success', 'Menu item deleted.');
        }
        redirect('admin_menu/index');
    }

    private function showForm(array $item, array $errors): void
    {
        $this->render('admin/menu_form', ['item' => $item, 'errors' => $errors, 'categories' => Category::all()],
            $item['id'] ? 'Edit menu item' : 'Add menu item');
    }
}
