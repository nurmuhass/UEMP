<?php
declare(strict_types=1);

require __DIR__ . '/manage_catalog.php';

function management_error(int $status, string $message): void
{
    page('message', 'Management request rejected', ['message' => $message], $status);
}

function management_id($value): ?int
{
    $id = is_string($value)
        ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]])
        : false;
    return $id === false ? null : $id;
}

function execute_write(string $sql, array $params): void
{
    db()->prepare($sql)->execute($params);
}

function management_audit(int $actor, string $entity, int $id, string $action, ?array $before, array $after): void
{
    execute_write('INSERT INTO admin_audit (actor_id, entity, entity_id, action, before_state, after_state)
        VALUES (?, ?, ?, ?, ?, ?)', [
        $actor, $entity, $id, $action,
        $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
        json_encode($after, JSON_THROW_ON_ERROR),
    ]);
}

function management_persist(string $table, int $id, array $values, bool $create): void
{
    $columns = array_keys($values);
    if ($create) {
        $names = implode(', ', $columns);
        $placeholders = implode(', ', array_fill(0, count($columns), '?'));
        execute_write("INSERT INTO $table (id, $names) VALUES (?, $placeholders)", [$id, ...array_values($values)]);
    } else {
        $assignments = implode(', ', array_map(static fn(string $column): string => "$column = ?", $columns));
        execute_write("UPDATE $table SET $assignments WHERE id = ?", [...array_values($values), $id]);
    }
}

function management_input(string $key, array $field, ?int $department, bool $super)
{
    [$label, $type] = $field;
    $raw = $_POST[$key] ?? '';
    if ($type === 'multiple') {
        if (!is_array($raw) || !$raw) {
            throw new DomainException('Select at least one eligible program.');
        }
        $allowed = management_options($type, $department, $super);
        $values = [];
        foreach ($raw as $item) {
            $id = management_id($item);
            if ($id === null || !isset($allowed[$id])) {
                throw new DomainException('Choose eligible programs in the selected department.');
            }
            $values[$id] = $id;
        }
        return array_values($values);
    }
    if (!is_string($raw)) {
        throw new DomainException("Invalid $label.");
    }
    if ($type === 'password') {
        return $raw;
    }
    $value = trim($raw);
    if ($type === 'phone') {
        if ($value !== '' && !preg_match('/^[0-9+(). -]{5,32}$/D', $value)) {
            throw new DomainException('Enter a valid phone number.');
        }
        return $value === '' ? null : $value;
    }
    if ($type === 'email') {
        if (strlen($value) > 254 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter a valid email address.');
        }
        return $value;
    }
    if (is_int($type) || $type === 'matric') {
        $limit = $type === 'matric' ? 30 : $type;
        if ($value === '' || strlen($value) > $limit || !preg_match('//u', $value)) {
            throw new DomainException("$label is required and must fit within $limit bytes.");
        }
        return $value;
    }
    if ($type === 'number') {
        $number = management_id($value);
        if ($number === null || $number > $field[2]) {
            throw new DomainException("Enter a valid $label.");
        }
        return $number;
    }
    $options = management_options($type, $department, $super);
    if (!isset($options[$value])) {
        throw new DomainException("Choose a valid $label for the selected department.");
    }
    return in_array($type, ['roles', 'statuses'], true) ? $value : (int) $value;
}

function management_save_user(int $id, array $values, ?array $before, array $actor): void
{
    $role = $values['role'];
    $department = $values['department_id'];
    $profile = [];
    if ($role === 'student') {
        foreach (['program_id', 'matric_number', 'academic_level'] as $key) {
            $profile[$key] = $values[$key];
            unset($values[$key]);
        }
    }
    $password = $values['password'];
    unset($values['password']);
    if ($before === null || $password !== '') {
        if (strlen($password) < 8 || strlen($password) > 72 || str_contains($password, "\0")) {
            throw new DomainException('Enter a password of 8 to 72 bytes.');
        }
        $values['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    }

    if ($id === (int) $actor['id'] &&
        ($role !== 'super_admin' || $values['status'] !== 'active')) {
        throw new DomainException('You cannot remove your own Super Admin access.');
    }
    if ($before !== null &&
        ($role !== $before['role'] || $department !== ($before['department_id'] === null ? null : (int) $before['department_id']))) {
        if (rows('SELECT student_id FROM registrations WHERE student_id = ? LIMIT 1 FOR UPDATE', [$id]) ||
            rows('SELECT id FROM offerings WHERE lecturer_id = ? LIMIT 1 FOR UPDATE', [$id])) {
            throw new DomainException('Reassignment is blocked while the account has registrations or allocated courses.');
        }
        execute_write('DELETE FROM students WHERE user_id = ?', [$id]);
    }
    management_persist('users', $id, $values, $before === null);
    if ($role === 'student') {
        if (rows('SELECT user_id FROM students WHERE user_id = ? FOR UPDATE', [$id])) {
            execute_write('UPDATE students SET program_id = ?, matric_number = ?, academic_level = ? WHERE user_id = ?',
                [$profile['program_id'], $profile['matric_number'], $profile['academic_level'], $id]);
        } else {
            execute_write('INSERT INTO students (user_id, department_id, program_id, matric_number, academic_level)
                VALUES (?, ?, ?, ?, ?)', [$id, $department, $profile['program_id'], $profile['matric_number'], $profile['academic_level']]);
        }
    }
}

function management_values(string $entity, bool $super, ?int $scope, ?array $before): array
{
    $fields = management_fields($entity, $super);
    $department = $scope;
    if (isset($fields['department_id'])) {
        $department = $entity === 'users' && ($_POST['role'] ?? null) === 'super_admin'
            ? null : management_input('department_id', $fields['department_id'], null, $super);
        unset($fields['department_id']);
    }

    $values = !empty(MANAGEMENT[$entity][2]) ? ['department_id' => $department] : [];
    if ($entity === 'users') {
        if (($_POST['role'] ?? null) !== 'student') {
            unset($fields['program_id'], $fields['matric_number'], $fields['academic_level']);
        }
        if (!$super) {
            $values['status'] = $before['status'] ?? 'active';
        }
    }
    foreach ($fields as $key => $field) {
        $values[$key] = management_input($key, $field, $department, $super);
    }
    return $values;
}

function management_guard_history(string $entity, array $before, array $values): void
{
    if ($entity !== 'users' && isset($before['department_id']) &&
        (int) $before['department_id'] !== $values['department_id']) {
        throw new DomainException('The department of an existing record cannot be changed.');
    }
    if ($entity === 'offerings' && ((int) $before['course_id'] !== $values['course_id'] ||
        (int) $before['semester_id'] !== $values['semester_id'])) {
        throw new DomainException('Create a separate allocation for a different course or semester.');
    }
    if ($entity === 'academic_levels' && (int) $before['level'] !== $values['level']) {
        $scope = [$before['department_id'], $before['level']];
        if (rows('SELECT id FROM courses WHERE department_id = ? AND academic_level = ? LIMIT 1', $scope) ||
            rows('SELECT user_id FROM students WHERE department_id = ? AND academic_level = ? LIMIT 1', $scope)) {
            throw new DomainException('An academic level in use cannot be renumbered.');
        }
    }
    if ($entity === 'courses' && (int) $before['units'] !== $values['units'] &&
        rows('SELECT g.student_id FROM registrations g JOIN offerings o ON o.id = g.offering_id
            WHERE o.course_id = ? LIMIT 1 FOR UPDATE', [$before['id']])) {
        throw new DomainException('Credit units are locked once students have registered for the course.');
    }
}

function management_save(array $actor, bool $super, string $entity, ?int $id): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Serialize management writes, including ID assignment and cross-record validation.
        rows('SELECT id FROM management_lock WHERE id = 1 FOR UPDATE');
        $live = rows("SELECT id, department_id FROM users WHERE id = ? AND role = ? AND status = 'active' FOR UPDATE",
            [$actor['id'], $actor['role']])[0] ?? null;
        if ($live === null) {
            $pdo->rollBack();
            management_error(403, 'Your management access has changed.');
        }
        $scope = $super ? null : (int) $live['department_id'];
        if ($id !== null) {
            $condition = $scope === null ? '' : ' AND department_id = ?';
            $params = $scope === null ? [$id] : [$id, $scope];
            rows("SELECT id FROM $entity WHERE id = ?$condition FOR UPDATE", $params);
        }
        $before = $id === null ? null : (management_rows($entity, $scope, $id)[0] ?? null);
        if ($id !== null && $before === null) {
            $pdo->rollBack();
            management_error(404, 'Record unavailable.');
        }
        if ($super && $entity === 'semesters' && $id !== null && ($_POST['action'] ?? null) === 'current') {
            $previous = rows('SELECT semester_id FROM current_semester WHERE singleton = 1')[0] ?? null;
            execute_write('INSERT INTO current_semester (singleton, semester_id) VALUES (1, ?)
                ON DUPLICATE KEY UPDATE semester_id = ?', [$id, $id]);
            management_audit((int) $actor['id'], 'current_semester', 1, 'set_current', $previous, ['semester_id' => $id]);
        } else {
            $values = management_values($entity, $super, $scope, $before);
            if ($before !== null) {
                management_guard_history($entity, $before, $values);
            }
            $id ??= (int) rows("SELECT COALESCE(MAX(id), 0) + 1 AS id FROM $entity")[0]['id'];
            if ($entity === 'users') {
                management_save_user($id, $values, $before, $actor);
            } else {
                $programs = $values['program_ids'] ?? [];
                unset($values['program_ids']);
                if ($entity === 'offerings' && $before !== null) {
                    $before['program_ids'] = array_column(rows('SELECT program_id FROM offering_programs WHERE offering_id = ? ORDER BY program_id', [$id]), 'program_id');
                }
                management_persist($entity, $id, $values, $before === null);
                if ($entity === 'offerings') {
                    execute_write('DELETE FROM offering_programs WHERE offering_id = ?', [$id]);
                    foreach ($programs as $program) {
                        execute_write('INSERT INTO offering_programs (offering_id, program_id) VALUES (?, ?)', [$id, $program]);
                    }
                }
            }
            $after = management_rows($entity, $scope, $id)[0];
            if ($entity === 'offerings') {
                $after['program_ids'] = $programs;
            }
            $action = $before === null ? 'create' : 'update';
            if ($entity === 'users' && $values['password'] !== '') {
                $after['password_changed'] = true;
            }
            management_audit((int) $actor['id'], $entity, $id, $action, $before, $after);
        }
        $pdo->commit();
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($error instanceof DomainException) {
            management_error(422, $error->getMessage());
        }
        if ($error instanceof PDOException && ($error->errorInfo[0] ?? null) === '23000') {
            management_error(409, 'The record conflicts with an existing or linked record.');
        }
        throw $error;
    }
}

function management_route(string $route, string $method): void
{
    $super = $route === 'manage_university';
    $user = require_role($super ? 'super_admin' : 'department_admin');
    $entities = management_entities($super);
    $entity = $_GET['entity'] ?? array_key_first($entities);
    if (!is_string($entity) || !isset($entities[$entity])) {
        management_error(404, 'Management section unavailable.');
    }
    $rawId = $_GET['id'] ?? null;
    $id = management_id($rawId);
    if ($rawId !== null && $id === null) {
        management_error(400, 'Invalid record identifier.');
    }
    if ($method === 'POST') {
        require_csrf();
        management_save($user, $super, $entity, $id);
        redirect($route . '&entity=' . $entity . '&saved=1', 303);
    }
    $scope = $super ? null : (int) $user['department_id'];
    $record = $id === null ? null : (management_rows($entity, $scope, $id)[0] ?? null);
    if ($id !== null && $record === null) {
        management_error(404, 'Record unavailable.');
    }
    if ($entity === 'offerings' && $id !== null) {
        $record['program_ids'] = array_column(rows('SELECT program_id FROM offering_programs WHERE offering_id = ?', [$id]), 'program_id');
    }
    $fields = management_fields($entity, $super);
    $records = management_rows($entity, $scope);
    $current = $entity === 'semesters' ? (rows('SELECT semester_id FROM current_semester WHERE singleton = 1')[0]['semester_id'] ?? null) : null;
    page('manage', $entities[$entity][0], compact('user', 'route', 'entity', 'entities', 'id', 'record',
        'fields', 'records', 'scope', 'super', 'current'));
}
