<?php
declare(strict_types=1);

function dashboard(array $user): array
{
    $term = rows('SELECT s.id, s.name, a.label FROM current_semester t
        JOIN semesters s ON s.id = t.semester_id JOIN academic_sessions a ON a.id = s.session_id')[0] ?? null;
    $semester = $term['id'] ?? null;

    switch ($user['role']) {
        case 'student':
            $profile = rows('SELECT s.matric_number, s.academic_level, p.name AS program,
                d.name AS department, f.name AS faculty FROM students s
                JOIN programs p ON p.id = s.program_id JOIN departments d ON d.id = s.department_id
                JOIN faculties f ON f.id = d.faculty_id WHERE s.user_id = ?', [$user['id']])[0] ?? [];
            $heading = 'Registered courses';
            $columns = ['Code', 'Course', 'Units', 'Approved score'];
            $records = rows("SELECT c.code, c.title, c.units, r.ca + r.exam AS score
                FROM registrations g JOIN offerings o ON o.id = g.offering_id
                JOIN courses c ON c.id = o.course_id
                LEFT JOIN results r ON r.student_id = g.student_id AND r.offering_id = g.offering_id
                    AND r.status = 'approved'
                WHERE g.student_id = ? AND o.semester_id = ? ORDER BY c.code", [$user['id'], $semester]);
            break;
        case 'lecturer':
            $heading = 'Assigned courses';
            $columns = ['Code', 'Course', 'Registered students', 'Results awaiting review'];
            $records = rows("SELECT c.code, c.title, COUNT(g.student_id), COUNT(r.student_id)
                FROM offerings o JOIN courses c ON c.id = o.course_id
                LEFT JOIN registrations g ON g.offering_id = o.id
                LEFT JOIN results r ON r.student_id = g.student_id AND r.offering_id = o.id
                    AND r.status = 'submitted'
                WHERE o.lecturer_id = ? AND o.department_id = ? AND o.semester_id = ?
                GROUP BY c.code, c.title ORDER BY c.code", [$user['id'], $user['department_id'], $semester]);
            break;
        case 'department_admin':
            $heading = 'Department courses';
            $columns = ['Department', 'Code', 'Course', 'Registrations', 'Results awaiting review'];
            $records = rows("SELECT d.name, c.code, c.title, COUNT(g.student_id), COUNT(r.student_id)
                FROM offerings o JOIN courses c ON c.id = o.course_id
                JOIN departments d ON d.id = o.department_id
                LEFT JOIN registrations g ON g.offering_id = o.id
                LEFT JOIN results r ON r.student_id = g.student_id AND r.offering_id = o.id
                    AND r.status = 'submitted'
                WHERE o.department_id = ? AND o.semester_id = ?
                GROUP BY d.name, c.code, c.title ORDER BY c.code", [$user['department_id'], $semester]);
            break;
        case 'super_admin':
            $heading = 'University overview';
            $columns = ['Faculty', 'Department', 'Active accounts', 'Current offerings'];
            $records = rows("SELECT f.name AS faculty, d.name AS department,
                (SELECT COUNT(*) FROM users u WHERE u.department_id = d.id AND u.status = 'active'),
                (SELECT COUNT(*) FROM offerings o WHERE o.department_id = d.id AND o.semester_id = ?)
                FROM departments d JOIN faculties f ON f.id = d.faculty_id ORDER BY d.name", [$semester]);
            break;
    }

    return compact('user', 'term', 'heading', 'columns', 'records') + ['profile' => $profile ?? []];
}
