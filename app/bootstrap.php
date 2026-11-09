<?php
declare(strict_types=1);

require __DIR__ . '/db.php';

const ROLES = [
    'student' => 'Student',
    'lecturer' => 'Lecturer',
    'department_admin' => 'Department Admin',
    'super_admin' => 'Super Admin',
];

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $route, int $status = 302): void
{
    header('Location: index.php?r=' . $route, true, $status);
    exit;
}

function page(string $view, string $title, array $data = [], int $status = 200): void
{
    http_response_code($status);
    require __DIR__ . '/templates/layout.php';
    exit;
}

function csrf(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function require_csrf(): void
{
    $token = $_POST['csrf'] ?? null;
    if (!is_string($token) || !hash_equals(csrf(), $token)) {
        page('message', 'Request rejected', ['message' => 'Reload the page and try again.'], 403);
    }
}

function sign_out(): void
{
    $_SESSION = [];
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600, 'path' => $cookie['path'],
        'secure' => $cookie['secure'], 'httponly' => true, 'samesite' => 'Lax',
    ]);
    session_destroy();
}

function current_user(): ?array
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $user = rows("SELECT id, name, role, department_id FROM users WHERE id = ? AND status = 'active'",
        [$_SESSION['user_id']])[0] ?? null;
    if (!$user || $user['role'] !== $_SESSION['role'] || time() - $_SESSION['last_seen'] >= 1800) {
        sign_out();
        redirect('login');
    }
    $_SESSION['last_seen'] = time();
    return $user;
}

function require_role(string $role): array
{
    $user = current_user();
    if ($user === null) {
        redirect('login');
    }
    if ($user['role'] !== $role) {
        page('message', 'Access denied', ['message' => 'This dashboard is restricted to its assigned role.'], 403);
    }
    return $user;
}
