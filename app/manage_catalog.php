<?php
declare(strict_types=1);

// Table and column identifiers used by management SQL come only from this catalog.
const MANAGEMENT = [
    'faculties' => ['Faculties', ['name' => ['Name', 100]]],
    'departments' => ['Departments', ['faculty_id' => ['Faculty', 'faculties'], 'name' => ['Name', 100]]],
    'programs' => ['Programs', ['name' => ['Name', 100]], true],
    'academic_levels' => ['Academic levels', ['level' => ['Level', 'number', 32767]], true],
    'users' => ['Accounts', [
        'name' => ['Name', 100], 'email' => ['Email', 'email'], 'phone' => ['Phone', 'phone'],
        'role' => ['Role', 'roles'], 'status' => ['Status', 'statuses'], 'password' => ['New password', 'password'],
        'program_id' => ['Student program', 'programs'], 'matric_number' => ['Student matriculation number', 'matric'],
        'academic_level' => ['Student academic level', 'levels'],
    ], true],
    'academic_sessions' => ['Academic sessions', ['label' => ['Session label', 20]]],
    'semesters' => ['Semesters', ['session_id' => ['Academic session', 'academic_sessions'], 'name' => ['Name', 30]]],
    'courses' => ['Courses', [
        'code' => ['Course code', 20], 'title' => ['Title', 120],
        'units' => ['Credit units', 'number', 255], 'academic_level' => ['Academic level', 'levels'],
    ], true],
    'offerings' => ['Course allocations', [
        'course_id' => ['Course', 'courses'], 'semester_id' => ['Semester', 'semesters'],
        'lecturer_id' => ['Lecturer', 'lecturers'], 'program_ids' => ['Eligible programs', 'multiple'],
    ], true],
];

function management_entities(bool $super): array
{
    return $super ? MANAGEMENT : array_filter(MANAGEMENT, static fn(array $entity): bool => !empty($entity[2]));
}

function management_fields(string $entity, bool $super): array
{
    $fields = MANAGEMENT[$entity][1];
    if ($super && !empty(MANAGEMENT[$entity][2])) {
        $fields = ['department_id' => ['Department', 'departments']] + $fields;
    }
    if (!$super && $entity === 'users') {
        unset($fields['status']);
    }
    return $fields;
}

function management_options(string $type, ?int $department, bool $super): array
{
    if ($type === 'roles') {
        return $super ? ROLES : array_intersect_key(ROLES, array_flip(['student', 'lecturer']));
    }
    if ($type === 'statuses') {
        return ['active' => 'Active', 'suspended' => 'Suspended', 'deactivated' => 'Deactivated'];
    }
    $where = $department === null ? '' : ' WHERE department_id = ?';
    $scope = $department === null ? [] : [$department];
    $records = match ($type) {
        'faculties', 'departments' => rows("SELECT id, name AS label FROM $type ORDER BY name"),
        'programs', 'multiple' => rows('SELECT id, name AS label FROM programs' . $where . ' ORDER BY name', $scope),
        'levels' => rows('SELECT DISTINCT level AS id, level AS label FROM academic_levels' . $where . ' ORDER BY level', $scope),
        'courses' => rows("SELECT id, CONCAT(code, ' · ', title) AS label FROM courses$where ORDER BY code", $scope),
        'academic_sessions' => rows('SELECT id, label FROM academic_sessions ORDER BY label DESC'),
        'semesters' => rows("SELECT s.id, CONCAT(a.label, ' · ', s.name) AS label FROM semesters s
            JOIN academic_sessions a ON a.id=s.session_id ORDER BY a.label DESC, s.name"),
        'lecturers' => rows("SELECT id, CONCAT(name, ' · ', email) AS label FROM users
            WHERE role='lecturer'" . ($where === '' ? '' : ' AND department_id = ?') . ' ORDER BY name', $scope),
        default => [],
    };
    return array_column($records, 'label', 'id');
}

function management_rows(string $entity, ?int $department, ?int $id = null): array
{
    $select = $entity === 'users'
        ? 'SELECT u.id, u.name, u.email, u.phone, u.role, u.status, u.department_id, s.program_id, s.matric_number, s.academic_level
            FROM users u LEFT JOIN students s ON s.user_id = u.id'
        : "SELECT u.* FROM $entity u";
    $conditions = [];
    $params = [];
    if ($department !== null) {
        $conditions[] = 'u.department_id = ?';
        $params[] = $department;
        if ($entity === 'users') {
            $conditions[] = "u.role IN ('student','lecturer')";
        }
    }
    if ($id !== null) {
        $conditions[] = 'u.id = ?';
        $params[] = $id;
    }
    return rows($select . ($conditions ? ' WHERE ' . implode(' AND ', $conditions) : '') . ' ORDER BY u.id DESC', $params);
}
