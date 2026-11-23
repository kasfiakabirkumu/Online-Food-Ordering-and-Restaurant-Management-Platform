<?php
declare(strict_types=1);

/** JSON Web API used by public/assets/js/app.js via fetch() (Q6). */
class ApiController extends Controller
{
    // GET index.php?r=api/menu&q=burger&category=1
    public function menu(): void
    {
        $q = mb_substr(clean((string)($_GET['q'] ?? '')), 0, 60);
        $items = MenuItem::search($q, (int)($_GET['category'] ?? 0));
        $this->json([
            'ok'    => true,
            'count' => count($items),
            'items' => array_map(fn($i) => [
                'id' => (int)$i['id'], 'name' => $i['name'], 'description' => $i['description'],
                'price' => (float)$i['price'], 'price_text' => money($i['price']),
                'emoji' => $i['emoji'], 'category' => $i['category_name'],
            ], $items),
        ]);
    }

    // POST index.php?r=api/cart_add   body: {"id": 3}   header: X-CSRF-Token
    public function cart_add(): void
    {
        if (!$this->isPost()) {
            $this->json(['ok' => false, 'error' => 'POST required'], 405);
        }
        csrf_check();
        $body = json_decode((string)file_get_contents('php://input'), true);
        $id = is_array($body) ? (int)($body['id'] ?? 0) : 0;
        if ($id <= 0 || !Cart::add($id, 1)) {
            $this->json(['ok' => false, 'error' => 'Item not available'], 422);
        }
        $this->json(['ok' => true, 'cart_count' => Cart::count()]);
    }
}
