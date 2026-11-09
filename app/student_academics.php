<?php
declare(strict_types=1);

require_once __DIR__ . '/results.php';

function student_profile_route(string $method): void
{
    $user = require_role('student');
    $record = rows('SELECT u.name, u.email, u.phone, s.matric_number, p.name AS program,
        d.name AS department, s.academic_level
        FROM users u JOIN students s ON s.user_id = u.id
        JOIN programs p ON p.id = s.program_id JOIN departments d ON d.id = s.department_id
        WHERE u.id = ?', [$user['id']])[0] ?? null;
    if ($record === null) {
        page('message', 'Profile unavailable', ['message' => 'Contact your department administrator.'], 404);
    }
    if ($method === 'POST') {
        require_csrf();
        $name = is_string($_POST['name'] ?? null) ? trim($_POST['name']) : '';
        $phone = is_string($_POST['phone'] ?? null) ? trim($_POST['phone']) : null;
        if (array_diff(array_keys($_POST), ['csrf','name','phone']) || $name === '' ||
            strlen($name) > 100 || !preg_match('//u', $name) || $phone === null ||
            ($phone !== '' && !preg_match('/^[0-9+(). -]{5,32}$/D', $phone))) {
            page('profile', 'My profile', compact('user', 'record') + ['error' => 'Enter a valid name and phone number.'], 422);
        }
        $statement = db()->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ? AND role = 'student' AND status = 'active'");
        $statement->execute([$name, $phone === '' ? null : $phone, $user['id']]);
        redirect('student_profile&saved=1', 303);
    }
    page('profile', 'My profile', compact('user', 'record'));
}

function student_history_route(bool $print): void
{
    $user = require_role('student');
    $records = rows("SELECT t.id AS semester_id, a.label, t.name AS semester, c.code, c.title, c.units,
        r.ca, r.exam FROM results r
        JOIN offerings o ON o.id = r.offering_id JOIN courses c ON c.id = o.course_id
        JOIN semesters t ON t.id = o.semester_id JOIN academic_sessions a ON a.id = t.session_id
        WHERE r.student_id = ? AND r.status = 'approved'
        ORDER BY a.label DESC, t.id DESC, c.code", [$user['id']]);
    $terms = [];
    $credits = $weighted = 0;
    $points = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1, 'F' => 0];
    foreach ($records as $row) {
        $semester = $row['semester_id'];
        $terms[$semester] ??= ['title' => $row['label'] . ' · ' . $row['semester'], 'credits' => 0, 'weighted' => 0, 'records' => []];
        $units = (int) $row['units'];
        $total = (int) $row['ca'] + (int) $row['exam'];
        $grade = result_grade($total);
        $value = $units * $points[$grade];
        $terms[$semester]['records'][] = [$row['code'], $row['title'], $units, $total, $grade, $points[$grade]];
        $terms[$semester]['credits'] += $units;
        $terms[$semester]['weighted'] += $value;
        $credits += $units;
        $weighted += $value;
    }
    $student = rows('SELECT s.matric_number, p.name AS program FROM students s
        JOIN programs p ON p.id = s.program_id WHERE s.user_id = ?', [$user['id']])[0] ?? [];
    $cgpa = $credits ? number_format($weighted / $credits, 2, '.', '') : '—';
    page('academic_history', $print ? 'Result statement' : 'Academic history',
        compact('user', 'student', 'terms', 'cgpa', 'credits', 'print'));
}
