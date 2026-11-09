<?php
declare(strict_types=1);

// Shared eligibility rules for both the available list and the atomic registration write.
const ELIGIBLE_OFFERINGS = 'FROM students s
    JOIN users u ON u.id = s.user_id AND u.department_id = s.department_id
    JOIN offering_programs p ON p.program_id = s.program_id
    JOIN offerings o ON o.id = p.offering_id AND o.department_id = s.department_id
    JOIN courses c ON c.id = o.course_id AND c.department_id = s.department_id
        AND c.academic_level = s.academic_level
    JOIN current_semester t ON t.semester_id = o.semester_id AND t.singleton = 1
    WHERE s.user_id = ? AND u.role  = \'student\' AND u.status = \'active\'';

function available_offerings(int $studentId): array
{
    return rows('SELECT o.id, c.code, c.title, c.units ' . ELIGIBLE_OFFERINGS . '
        AND NOT EXISTS (SELECT 1 FROM registrations g
            WHERE g.student_id = s.user_id AND g.offering_id = o.id)
        ORDER BY c.code', [$studentId]);
}

function register_offering(int $studentId, int $offeringId): string
{
    $statement = db()->prepare('INSERT INTO registrations (student_id, offering_id)
        SELECT s.user_id, o.id ' . ELIGIBLE_OFFERINGS . ' AND o.id = ?');
    try {
        $statement->execute([$studentId, $offeringId]);
    } catch (PDOException $error) {
        if ((int) ($error->errorInfo[1] ?? 0) === 1062) {
            return 'duplicate';
        }
        throw $error;
    }
    return $statement->rowCount() === 1 ? 'registered' : 'ineligible';
}
