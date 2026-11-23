<?php
declare(strict_types=1);

class MenuController extends Controller
{
    public function index(): void
    {
        $q = clean((string)($_GET['q'] ?? ''));
        $categoryId = (int)($_GET['category'] ?? 0);
        $this->render('menu/index', [
            'items'      => MenuItem::search(mb_substr($q, 0, 60), $categoryId),
            'categories' => Category::all(),
            'q'          => $q,
            'categoryId' => $categoryId,
        ], 'Menu');
    }
}
