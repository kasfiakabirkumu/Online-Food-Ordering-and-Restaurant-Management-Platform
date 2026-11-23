<?php
declare(strict_types=1);

/** Full CRUD for categories (entity 2). */
class AdminCategoryController extends Controller
{
    public function index(): void
    {
        $this->requireAdmin();
        $this->render('admin/category_index', ['categories' => Category::allWithCounts()], 'Manage categories');
    }

    public function form(): void
    {
        $this->requireAdmin();
        $id = (int)($_GET['id'] ?? 0);
        $cat = $id ? Category::find($id) : null;
        if ($id && !$cat) {
            (new HomeController())->notFound();
            return;
        }
        $this->showForm($cat ?? ['id' => 0, 'name' => '', 'description' => ''], []);
    }

    public function save(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        $name = clean((string)($_POST['name'] ?? ''));
        $desc = clean((string)($_POST['description'] ?? ''));
        $errors = [];
        if (!preg_match("/^[\p{L}\d &'-]{2,60}$/u", $name)) {
            $errors[] = 'Name must be 2-60 characters (letters, digits, spaces, & \' -).';
        }
        if (mb_strlen($desc) > 255) {
            $errors[] = 'Description is too long.';
        }
        if (!$errors && Category::nameExists($name, $id)) {
            $errors[] = 'A category with this name already exists.';
        }
        if ($errors) {
            $this->showForm(['id' => $id, 'name' => $name, 'description' => $desc], $errors);
            return;
        }
        if ($id) {
            Category::update($id, $name, $desc);
            flash('success', 'Category updated.');
        } else {
            Category::create($name, $desc);
            flash('success', 'Category created.');
        }
        redirect('admin_category/index');
    }

    public function delete(): void
    {
        $this->requireAdmin();
        $this->requirePost();
        $id = (int)($_POST['id'] ?? 0);
        if (Category::itemCount($id) > 0) {
            flash('error', 'Move or delete the menu items in this category first.');
        } else {
            Category::delete($id);
            flash('success', 'Category deleted.');
        }
        redirect('admin_category/index');
    }

    private function showForm(array $cat, array $errors): void
    {
        $this->render('admin/category_form', ['cat' => $cat, 'errors' => $errors],
            $cat['id'] ? 'Edit category' : 'Add category');
    }
}
