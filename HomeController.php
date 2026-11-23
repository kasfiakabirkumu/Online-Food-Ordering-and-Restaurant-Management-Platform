<?php
declare(strict_types=1);

class HomeController extends Controller
{
    public function index(): void
    {
        $featured = array_slice(MenuItem::search(), 0, 6);
        $this->render('home/index', ['featured' => $featured, 'categories' => Category::all()], 'Home');
    }

    public function notFound(): void
    {
        http_response_code(404);
        $this->render('errors/404', [], 'Page not found');
    }
}
