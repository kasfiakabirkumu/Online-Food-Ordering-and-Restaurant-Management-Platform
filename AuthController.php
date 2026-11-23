<?php
declare(strict_types=1);

class AuthController extends Controller
{
    private const MAX_FAILS = 5;
    private const LOCK_SECONDS = 60;

    public function login(): void
    {
        if (current_user()) {
            redirect('home/index');
        }
        $errors = [];
        $email = '';
        $remembered = false;

        // Cookie: remembered e-mail address (convenience only, never the password)
        $cookieEmail = $_COOKIE['remember_email'] ?? '';
        if ($cookieEmail !== '' && filter_var($cookieEmail, FILTER_VALIDATE_EMAIL)) {
            $email = $cookieEmail;
            $remembered = true;
        }

        if ($this->isPost()) {
            csrf_check();
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $password = (string)($_POST['password'] ?? '');
            $remembered = isset($_POST['remember']);

            if (($_SESSION['lock_until'] ?? 0) > time()) {
                $errors[] = 'Too many failed attempts. Please wait a minute and try again.';
            } else {
                $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? User::findByEmail($email) : null;
                // Always run password_verify so response time does not reveal whether the e-mail exists.
                $hash = $user['password_hash'] ?? '$2y$10$usesomesillystringforsalt.invalidhashvalueXXXXXXXXXXXXXXXXXXXX';
                if ($user && password_verify($password, $hash)) {
                    Session::regenerate();               // prevents session fixation
                    $_SESSION['user'] = ['id' => (int)$user['id'], 'name' => $user['name'], 'role' => $user['role']];
                    unset($_SESSION['login_fails'], $_SESSION['lock_until']);
                    if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                        // (rehash could be stored here)
                    }
                    $this->setRememberCookie($remembered ? $email : '');
                    flash('success', 'Welcome back, ' . $user['name'] . '!');
                    redirect($user['role'] === 'admin' ? 'admin/dashboard' : 'menu/index');
                }
                $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
                if ($_SESSION['login_fails'] >= self::MAX_FAILS) {
                    $_SESSION['lock_until'] = time() + self::LOCK_SECONDS;
                    $_SESSION['login_fails'] = 0;
                }
                $errors[] = 'Invalid e-mail or password.';
            }
        }
        $this->render('auth/login', ['errors' => $errors, 'email' => $email, 'remembered' => $remembered], 'Log in');
    }

    public function register(): void
    {
        if (current_user()) {
            redirect('home/index');
        }
        $errors = [];
        $old = ['name' => '', 'email' => '', 'phone' => ''];

        if ($this->isPost()) {
            csrf_check();
            $name = clean((string)($_POST['name'] ?? ''));
            $email = strtolower(trim((string)($_POST['email'] ?? '')));
            $phone = preg_replace('/[\s-]/', '', (string)($_POST['phone'] ?? ''));
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['confirm'] ?? '');
            $old = ['name' => $name, 'email' => $email, 'phone' => $phone];

            // Regular expressions + PHP string functions
            if (!preg_match("/^[\p{L} .'-]{2,60}$/u", $name)) {
                $errors[] = 'Name must be 2-60 letters (spaces, dots, apostrophes and hyphens allowed).';
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 120) {
                $errors[] = 'Please enter a valid e-mail address.';
            }
            if (!preg_match('/^(?:\+?88)?01[3-9]\d{8}$/', $phone)) {
                $errors[] = 'Enter a valid Bangladeshi mobile number, e.g. 01712345678.';
            }
            if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,72}$/', $password)) {
                $errors[] = 'Password must be 8-72 characters with an upper case letter, a lower case letter and a digit.';
            }
            if ($password !== $confirm) {
                $errors[] = 'Passwords do not match.';
            }
            if (!$errors && User::findByEmail($email)) {
                $errors[] = 'An account with this e-mail already exists.';
            }

            if (!$errors) {
                $id = User::create(ucwords(strtolower($name)), $email, password_hash($password, PASSWORD_DEFAULT), $phone);
                Session::regenerate();
                $_SESSION['user'] = ['id' => $id, 'name' => ucwords(strtolower($name)), 'role' => 'customer'];
                flash('success', 'Account created. Welcome to FoodHub!');
                redirect('menu/index');
            }
        }
        $this->render('auth/register', ['errors' => $errors, 'old' => $old], 'Register');
    }

    public function logout(): void
    {
        $this->requirePost();
        Session::destroy();
        Session::start();
        Session::regenerate();
        flash('info', 'You have been logged out.');
        redirect('home/index');
    }

    private function setRememberCookie(string $email): void
    {
        $options = ['path' => '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])];
        if ($email !== '') {
            setcookie('remember_email', $email, ['expires' => time() + 60 * 60 * 24 * 30] + $options);
        } else {
            setcookie('remember_email', '', ['expires' => time() - 3600] + $options);
        }
    }
}
