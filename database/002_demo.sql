START TRANSACTION;
INSERT INTO faculties VALUES (1,'Faculty of Science'),(2,'Faculty of Social Sciences');
INSERT INTO departments VALUES (1,1,'Computer Science'),(2,2,'Economics');
INSERT INTO programs VALUES (1,1,'BSc Computer Science'),(2,2,'BSc Economics');
INSERT INTO academic_sessions VALUES (1,'2026/2027');
INSERT INTO semesters VALUES (1,1,'First semester');
INSERT INTO current_semester VALUES (1,1);
INSERT INTO users VALUES
 (1,'student@example.test','Ada Okafor',@demo_hash,'student','active',1),
 (2,'lecturer@example.test','Dr. Chidi Obi',@demo_hash,'lecturer','active',1),
 (3,'department_admin@example.test','Tomi Bello',@demo_hash,'department_admin','active',1),
 (4,'super_admin@example.test','Amina Yusuf',@demo_hash,'super_admin','active',NULL),
 (5,'student.econ@example.test','Bisi Adeyemi',@demo_hash,'student','active',2),
 (6,'lecturer.econ@example.test','Dr. Musa Lawal',@demo_hash,'lecturer','active',2),
 (7,'department_admin.econ@example.test','Ife Eze',@demo_hash,'department_admin','active',2),
 (8,'suspended@example.test','Suspended Demo',@demo_hash,'student','suspended',1),
 (9,'deactivated@example.test','Deactivated Demo',@demo_hash,'student','deactivated',2);
INSERT INTO students VALUES (1,1,1,'CSC/2025/001',200),(5,2,2,'ECO/2025/001',200),
 (8,1,1,'CSC/2025/002',200),(9,2,2,'ECO/2025/002',200);
INSERT INTO courses VALUES (1,1,'CSC201','Data Structures',3,200),
 (2,2,'ECO201','Microeconomics',3,200),(3,1,'CSC203','Database Systems',3,200);
INSERT INTO offerings VALUES (1,1,1,1,2),(2,2,2,1,6),(3,3,1,1,2);
INSERT INTO registrations VALUES (1,1),(5,2),(1,3);
INSERT INTO results VALUES (1,1,34,49,'approved'),(5,2,31,44,'submitted'),(1,3,39,59,'draft');
COMMIT;
