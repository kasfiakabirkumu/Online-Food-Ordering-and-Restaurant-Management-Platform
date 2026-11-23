<?php
declare(strict_types=1);

function config(string $key, $default = null)
{
    $value = $GLOBALS['config'];
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}


/* ---------- UTF-8 compatibility (works even when PHP mbstring is disabled) ---------- */
if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        $count = preg_match_all('/./us', $string, $matches);
        return $count === false ? strlen($string) : $count;
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        $characters = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
        if ($characters === false) {
            return $length === null ? substr($string, $start) : substr($string, $start, $length);
        }

        $total = count($characters);
        if ($start < 0) {
            $start = max(0, $total + $start);
        }
        if ($length === null) {
            $sliceLength = null;
        } elseif ($length < 0) {
            $sliceLength = max(0, $total - $start + $length);
        } else {
            $sliceLength = $length;
        }

        $result = array_slice($characters, $start, $sliceLength);
        return implode('', $result);
    }
}

/** Escape output (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route, array $params = []): string
{
    return 'index.php?' . http_build_query(['r' => $route] + $params);
}

function redirect(string $route, array $params = []): void
{
    header('Location: ' . url($route, $params));
    exit;
}

function money($amount): string
{
    return '৳' . number_format((float)$amount, 2);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function get_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/* ---------- CSRF ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        http_response_code(419);
        exit('Security check failed (invalid CSRF token). Go back, refresh the page and try again.');
    }
}

/* ---------- Auth ---------- */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

/** Clean a text input: trim, strip tags, collapse spaces. */
function clean(string $value): string
{
    return preg_replace('/\s+/u', ' ', trim(strip_tags($value)));
}
