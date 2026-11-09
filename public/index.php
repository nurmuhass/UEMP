<?php
declare(strict_types=1);

require __DIR__ . '/../app/bootstrap.php';

header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header("Content-Security-Policy: default-src 'self'; style-src 'self' https://cdn.jsdelivr.net; frame-ancestors 'none'; form-action 'self'; base-uri 'none'");

try {
    $secure = getenv('SESSION_SECURE') !== '0' || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('UEMPSESSID');
    if (!session_start([
        'use_strict_mode' => 1, 'use_only_cookies' => 1, 'use_trans_sid' => 0,
        'cookie_httponly' => 1, 'cookie_samesite' => 'Lax', 'cookie_secure' => $secure,
    ])) {
        throw new RuntimeException('Session unavailable');
    }

    $route = $_GET['r'] ?? 'login';
    if (!is_string($route) || (!isset(ROLES[$route]) && !in_array($route, ['login', 'logout', 'register', 'registration_summary', 'lecturer_results', 'review_results'], true))) {
        page('message', 'Page unavailable', ['message' => 'The requested page could not be found.'], 404);
    }

    $method = $_SERVER['REQUEST_METHOD'];
    $allowed = match ($route) {
        'login', 'lecturer_results', 'review_results' => ['GET', 'POST'],
        'logout', 'register' => ['POST'],
        default => ['GET'],
    };
    if (!in_array($method, $allowed, true)) {
        header('Allow: ' . implode(', ', $allowed));
        page('message', 'Method unavailable', ['message' => 'This request method is unavailable.'], 405);
    }

    if ($route === 'logout') {
        require_csrf();
        sign_out();
        redirect('login', 303);
    }

    if ($route === 'login') {
        if ($method === 'POST') {
            require_csrf();
        }
        if ($user = current_user()) {
            redirect($user['role'], $method === 'POST' ? 303 : 302);
        }
        if ($method === 'GET') {
            page('login', 'Sign in');
        }

        $email = is_string($_POST['email'] ?? null) ? trim($_POST['email']) : '';
        $password = $_POST['password'] ?? null;
        $user = strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL)
            ? (rows('SELECT id, role, status, password_hash FROM users WHERE email = ?', [$email])[0] ?? null)
            : null;
        // Use a dummy bcrypt hash for unknown emails to keep the verification path comparable.
        $hash = $user['password_hash'] ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        $valid = is_string($password) && strlen($password) <= 72 && password_verify($password, $hash);
        if (!$valid || !$user || $user['status'] !== 'active') {
            page('login', 'Sign in', ['error' => 'Sign-in failed. Check your credentials or contact your administrator.'], 401);
        }
        if (!session_regenerate_id(true)) {
            throw new RuntimeException('Could not rotate session');
        }
        $_SESSION = ['user_id' => $user['id'], 'role' => $user['role'], 'last_seen' => time()];
        redirect($user['role'], 303);
    }

    if (in_array($route, ['lecturer_results', 'review_results'], true)) {
        require __DIR__ . '/../app/results.php';
        result_route($route, $method);
    }

    if ($route === 'register') {
        $user = require_role('student');
        require_csrf();
        if (array_diff(array_keys($_POST), ['csrf', 'offering_id']) ||
            array_diff(array_keys($_GET), ['r'])) {
            page('message', 'Invalid registration request', ['message' => 'Submit an offering from your own dashboard.'], 400);
        }
        $rawOffering = $_POST['offering_id'] ?? null;
        $offering = is_string($rawOffering)
            ? filter_var($rawOffering, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]])
            : false;
        if ($offering === false) {
            page('message', 'Invalid offering', ['message' => 'Choose an offering from your dashboard.'], 400);
        }

        require __DIR__ . '/../app/registration.php';
        $outcome = register_offering((int) $user['id'], $offering);
        if ($outcome === 'duplicate') {
            page('message', 'Already registered', ['message' => 'This course is already on your registration.'], 409);
        }
        if ($outcome === 'ineligible') {
            page('message', 'Offering unavailable', ['message' => 'This offering is unavailable for your current registration.'], 422);
        }
        $_SESSION['registration_notice'] = true;
        redirect('student', 303);
    }

    $user = require_role($route === 'registration_summary' ? 'student' : $route);
    if ($route === 'registration_summary') {
        require __DIR__ . '/../app/dashboard.php';
        page('registration_summary', 'Course registration summary', dashboard($user, true));
    }
    require __DIR__ . '/../app/dashboard.php';
    page('dashboard', ROLES[$route] . ' dashboard', dashboard($user));
} catch (Throwable $error) {
    error_log('UEMP request failed: ' . get_class($error) . ' (code ' . $error->getCode() . ')');
    page('message', 'Service unavailable', ['message' => 'Please try again later.'], 500);
}
