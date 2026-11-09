<?php
declare(strict_types=1);

function result_grade(int $total): string
{
    return match (true) {
        $total >= 70 => 'A',
        $total >= 60 => 'B',
        $total >= 50 => 'C',
        $total >= 45 => 'D',
        $total >= 40 => 'E',
        default => 'F',
    };
}

function result_number($value, int $min, int $max = 2147483647): ?int
{
    if (!is_string($value)) {
        return null;
    }
    $number = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $min, 'max_range' => $max]]);
    return $number === false ? null : $number;
}

function result_offerings(array $user, ?int $id = null, bool $lock = false): array
{
    $sql = "SELECT o.id, c.code, c.title, a.label, t.name AS semester
        FROM offerings o JOIN courses c ON c.id = o.course_id
        JOIN semesters t ON t.id = o.semester_id JOIN academic_sessions a ON a.id = t.session_id
        JOIN users u ON u.department_id = o.department_id
        WHERE u.id = ? AND u.role = ? AND u.status = 'active'
        AND (u.role = 'department_admin' OR o.lecturer_id = u.id)";
    $params = [$user['id'], $user['role']];
    if ($id !== null) {
        $sql .= ' AND o.id = ?';
        $params[] = $id;
    }
    return rows($sql . ' ORDER BY a.label DESC, t.id DESC, c.code' . ($lock ? ' FOR UPDATE' : ''), $params);
}

function result_class_list(int $offering, bool $review): array
{
    $sql = 'SELECT s.user_id, s.matric_number, u.name, r.ca, r.exam, r.status, r.feedback, r.revision
        FROM registrations g JOIN students s ON s.user_id = g.student_id
        JOIN users u ON u.id = s.user_id
        LEFT JOIN results r ON r.student_id = g.student_id AND r.offering_id = g.offering_id
        WHERE g.offering_id = ?';
    if ($review) {
        $sql .= " AND r.status IN ('submitted', 'approved', 'rejected')";
    }
    return rows($sql . ' ORDER BY s.matric_number', [$offering]);
}

function result_error(int $status, string $message): void
{
    page('message', 'Result request rejected', ['message' => $message], $status);
}

function change_result(array $user, int $offering, bool $review): void
{
    $action = $_POST['action'] ?? null;
    $student = result_number($_POST['student_id'] ?? null, 1);
    $revision = result_number($_POST['revision'] ?? null, 0, 4294967294);
    if ($student === null || $revision === null ||
        !in_array($action, $review ? ['approve', 'reject'] : ['save', 'submit'], true)) {
        result_error(400, 'Invalid result action.');
    }

    $feedback = null;
    if ($action === 'reject') {
        $input = $_POST['feedback'] ?? null;
        $feedback = is_string($input) ? trim($input) : '';
        if ($feedback === '' || strlen($feedback) > 1000 || !preg_match('//u', $feedback)) {
            result_error(422, 'Enter rejection feedback of up to 1,000 UTF-8 bytes.');
        }
    }
    $ca = $exam = null;
    if (!$review) {
        $ca = result_number($_POST['ca'] ?? null, 0, 40);
        $exam = result_number($_POST['exam'] ?? null, 0, 60);
        if ($ca === null || $exam === null) {
            result_error(422, 'Enter whole numbers from 0 to 40 for CA and 0 to 60 for the exam.');
        }
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $failure = write_result($pdo, $user, $offering, $student, $revision, $action, $ca, $exam, $feedback, $review);
        if ($failure !== null) {
            $pdo->rollBack();
        } else {
            $pdo->commit();
        }
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
    if ($failure !== null) {
        result_error($failure, $failure === 404
            ? 'Offering or class registration unavailable.'
            : 'This result is locked or has changed. Reload the result before continuing.');
    }
}

function write_result(PDO $pdo, array $user, int $offering, int $student, int $revision,
    string $action, ?int $ca, ?int $exam, ?string $feedback, bool $review): ?int
{
    // Lock the offering and recheck the live actor before locking the target registration.
    if (!result_offerings($user, $offering, true) ||
        !rows('SELECT student_id FROM registrations WHERE student_id = ? AND offering_id = ? FOR UPDATE',
            [$student, $offering])) {
        return 404;
    }
    $before = rows('SELECT ca, exam, status, feedback, revision FROM results
        WHERE student_id = ? AND offering_id = ? FOR UPDATE', [$student, $offering])[0] ?? null;
    $status = $before['status'] ?? null;
    if ($revision !== (int) ($before['revision'] ?? 0) ||
        ($review ? $status !== 'submitted' : !in_array($status, [null, 'draft', 'rejected'], true))) {
        return 409;
    }
    $next = match ($action) {
        'save' => $status === 'rejected' ? 'rejected' : 'draft',
        'submit' => 'submitted',
        'approve' => 'approved',
        'reject' => 'rejected',
    };
    if ($action === 'save' && $status === 'rejected') {
        $feedback = $before['feedback'];
    }
    $after = [
        'ca' => $review ? (int) $before['ca'] : $ca,
        'exam' => $review ? (int) $before['exam'] : $exam,
        'status' => $next, 'feedback' => $feedback, 'revision' => $revision + 1,
    ];
    $statement = $pdo->prepare($before === null
        ? 'INSERT INTO results (ca, exam, status, feedback, revision, student_id, offering_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)'
        : 'UPDATE results SET ca = ?, exam = ?, status = ?, feedback = ?, revision = ?
            WHERE student_id = ? AND offering_id = ?');
    $statement->execute([...array_values($after), $student, $offering]);

    $audit = $pdo->prepare('INSERT INTO result_audit
        (student_id, offering_id, actor_id, action, before_state, after_state) VALUES (?, ?, ?, ?, ?, ?)');
    $audit->execute([$student, $offering, $user['id'], $action,
        $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
        json_encode($after, JSON_THROW_ON_ERROR)]);
    return null;
}

function result_route(string $route, string $method): void
{
    $review = $route === 'review_results';
    $user = require_role($review ? 'department_admin' : 'lecturer');
    $rawOffering = $_GET['offering'] ?? null;
    $id = result_number($rawOffering, 1);
    if ($id === null && ($rawOffering !== null || $method === 'POST')) {
        result_error(400, 'Choose a valid offering.');
    }
    if ($method === 'POST') {
        require_csrf();
        change_result($user, $id, $review);
        redirect($route . '&offering=' . $id, 303);
    }
    $offerings = result_offerings($user, $id);
    if ($id !== null && !$offerings) {
        result_error(404, 'Offering unavailable.');
    }
    $class = $id === null ? [] : result_class_list($id, $review);
    page('results', $review ? 'Department result review' : 'Lecturer class lists',
        compact('user', 'review', 'route', 'id', 'offerings', 'class'));
}
