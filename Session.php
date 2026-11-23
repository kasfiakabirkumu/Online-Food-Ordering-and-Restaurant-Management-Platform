<?php
declare(strict_types=1);

/** Hardened session handling (Q7): HttpOnly + SameSite cookie, strict mode, timeout, fingerprint, ID regeneration. */
class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        session_name('FOODHUBSESSID');
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();

        $timedOut = isset($_SESSION['last_activity'])
            && (time() - $_SESSION['last_activity']) > (int)config('app.session_timeout');
        $fingerprint = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
        $mismatch = isset($_SESSION['fp']) && !hash_equals($_SESSION['fp'], $fingerprint);

        if ($timedOut || $mismatch) {
            self::destroy();
            session_start();
            session_regenerate_id(true);
            if ($timedOut) {
                flash('info', 'You were logged out after a period of inactivity.');
            }
        }
        $_SESSION['last_activity'] = time();
        $_SESSION['fp'] = $fingerprint;
    }

    /** Call after login / privilege change to prevent session fixation. */
    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
