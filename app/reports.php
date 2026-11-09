<?php
declare(strict_types=1);

function report_section(string $heading, array $columns, array $records): array
{
    return compact('heading', 'columns', 'records');
}

function offering_report(array $user): array
{
    $scope = match ($user['role']) {
        'lecturer' => [' AND o.department_id = ? AND o.lecturer_id = ?', [$user['department_id'], $user['id']]],
        'department_admin' => [' AND o.department_id = ?', [$user['department_id']]],
        default => ['', []],
    };
    return rows("SELECT CONCAT(a.label, ' · ', t.name), d.name, c.code, c.title, COUNT(g.student_id),
        SUM(CASE WHEN r.status = 'draft' THEN 1 ELSE 0 END),
        SUM(CASE WHEN r.status = 'submitted' THEN 1 ELSE 0 END),
        SUM(CASE WHEN r.status = 'rejected' THEN 1 ELSE 0 END),
        SUM(CASE WHEN r.status = 'approved' THEN 1 ELSE 0 END),
        ROUND(AVG(CASE WHEN r.status = 'approved' THEN r.ca + r.exam END), 2),
        ROUND(100 * AVG(CASE WHEN r.status = 'approved' THEN r.ca + r.exam >= 40 END), 1)
        FROM offerings o JOIN courses c ON c.id = o.course_id JOIN departments d ON d.id = o.department_id
        JOIN semesters t ON t.id = o.semester_id JOIN academic_sessions a ON a.id = t.session_id
        LEFT JOIN registrations g ON g.offering_id = o.id
        LEFT JOIN results r ON r.offering_id = g.offering_id AND r.student_id = g.student_id
        WHERE 1=1" . $scope[0] . '
        GROUP BY o.id, a.label, t.id, t.name, d.name, c.code, c.title
        ORDER BY a.label DESC, t.id DESC, d.name, c.code', $scope[1]);
}

function reports_route(string $route): void
{
    $role = match ($route) {
        'lecturer_reports' => 'lecturer', 'department_reports' => 'department_admin', default => 'super_admin',
    };
    $user = require_role($role);
    $sections = [report_section('Registrations and results by offering',
        ['Term', 'Department', 'Code', 'Course', 'Registered', 'Draft', 'Submitted', 'Rejected', 'Approved', 'Approved average', 'Approved pass %'],
        offering_report($user))];

    if ($role === 'department_admin') {
        $sections[] = report_section('Department student roster',
            ['Matriculation number', 'Name', 'Program', 'Level', 'Status'],
            rows('SELECT s.matric_number, u.name, p.name, s.academic_level, u.status
                FROM students s JOIN users u ON u.id = s.user_id
                JOIN programs p ON p.id = s.program_id
                WHERE s.department_id = ? ORDER BY s.matric_number', [$user['department_id']]));
    }

    if ($role === 'super_admin') {
        $sections[] = report_section('Recent result audit events',
            ['Time', 'Actor', 'Department', 'Course', 'Student', 'Action', 'Before', 'After'],
            rows('SELECT x.recorded_at, u.name, d.name, c.code, s.matric_number, x.action, x.before_state, x.after_state
                FROM result_audit x JOIN users u ON u.id = x.actor_id JOIN offerings o ON o.id = x.offering_id
                JOIN departments d ON d.id = o.department_id JOIN courses c ON c.id = o.course_id
                JOIN students s ON s.user_id = x.student_id ORDER BY x.id DESC LIMIT 100'));
        $sections[] = report_section('Recent administrative changes',
            ['Time', 'Actor', 'Record type', 'Record ID', 'Action', 'Before', 'After'],
            rows('SELECT x.recorded_at, u.name, x.entity, x.entity_id, x.action, x.before_state, x.after_state
                FROM admin_audit x JOIN users u ON u.id = x.actor_id ORDER BY x.id DESC LIMIT 100'));
    }

    $title = match ($role) {
        'lecturer' => 'Course performance', 'department_admin' => 'Department reports', default => 'University monitoring and audit',
    };
    page('reports', $title, compact('user', 'sections'));
}
