<?php
declare(strict_types=1);

abstract class Controller
{
    protected function render(string $view, array $data = [], string $title = ''): void
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require BASE_PATH . "/app/views/$view.php";
        $content = ob_get_clean();
        $pageTitle = $title;
        require BASE_PATH . '/app/views/layout.php';
    }

    protected function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    protected function isPost(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function requirePost(): void
    {
        if (!$this->isPost()) {
            http_response_code(405);
            exit('Method not allowed.');
        }
        csrf_check();
    }

    protected function requireLogin(): array
    {
        $user = current_user();
        if (!$user) {
            flash('info', 'Please log in to continue.');
            redirect('auth/login');
        }
        return $user;
    }

    protected function requireAdmin(): array
    {
        $user = $this->requireLogin();
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            $this->render('errors/403', [], 'Forbidden');
            exit;
        }
        return $user;
    }
}
