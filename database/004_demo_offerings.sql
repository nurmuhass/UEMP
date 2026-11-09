-- Apply once to the original seeded demo after 003. Existing registrations are preserved.
START TRANSACTION;
INSERT INTO courses (id, department_id, code, title, units, academic_level) VALUES
 (10, 1, 'CSC205', 'Computer Networks', 3, 200),
 (11, 2, 'ECO203', 'Economic Statistics', 2, 200);
INSERT INTO offerings (id, course_id, department_id, semester_id, lecturer_id) VALUES
 (10, 10, 1, 1, 2), (11, 11, 2, 1, 6);
INSERT INTO offering_programs (offering_id, program_id) VALUES (10, 1), (11, 2);
COMMIT;
