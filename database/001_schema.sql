CREATE TABLE faculties (
    id INT PRIMARY KEY, name VARCHAR(100) NOT NULL UNIQUE
);
CREATE TABLE departments (
    id INT PRIMARY KEY, faculty_id INT NOT NULL,
    name VARCHAR(100) NOT NULL UNIQUE
);
CREATE TABLE programs (
    id INT PRIMARY KEY, department_id INT NOT NULL,
    name VARCHAR(100) NOT NULL, UNIQUE (id, department_id)
);
CREATE TABLE academic_sessions (
    id INT PRIMARY KEY, label VARCHAR(20) NOT NULL UNIQUE
);
CREATE TABLE semesters (
    id INT PRIMARY KEY, session_id INT NOT NULL,
    name VARCHAR(30) NOT NULL, UNIQUE (session_id, name)
);
CREATE TABLE current_semester (
    singleton INT PRIMARY KEY CHECK (singleton = 1),
    semester_id INT NOT NULL
);
CREATE TABLE users (
    id INT PRIMARY KEY, email VARCHAR(254) NOT NULL UNIQUE, name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student','lecturer','department_admin','super_admin') NOT NULL,
    status ENUM('active','suspended','deactivated') NOT NULL DEFAULT 'active',
    department_id INT NULL,
    UNIQUE (id, department_id),
    CHECK ((role = 'super_admin' AND department_id IS NULL) OR
           (role <> 'super_admin' AND department_id IS NOT NULL))
);
CREATE TABLE students (
    user_id INT PRIMARY KEY, department_id INT NOT NULL, program_id INT NOT NULL,
    matric_number VARCHAR(30) NOT NULL UNIQUE, academic_level SMALLINT NOT NULL,
    FOREIGN KEY (user_id, department_id) REFERENCES users(id, department_id),
    FOREIGN KEY (program_id, department_id) REFERENCES programs(id, department_id)
);
CREATE TABLE courses (
    id INT PRIMARY KEY, department_id INT NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE, title VARCHAR(120) NOT NULL,
    units TINYINT UNSIGNED NOT NULL CHECK (units > 0), academic_level SMALLINT NOT NULL,
    UNIQUE (id, department_id)
);
CREATE TABLE offerings (
    id INT PRIMARY KEY, course_id INT NOT NULL, department_id INT NOT NULL,
    semester_id INT NOT NULL, lecturer_id INT NOT NULL,
    FOREIGN KEY (course_id, department_id) REFERENCES courses(id, department_id),
    FOREIGN KEY (lecturer_id, department_id) REFERENCES users(id, department_id),
    UNIQUE (course_id, semester_id)
);
CREATE TABLE registrations (
    student_id INT NOT NULL,
    offering_id INT NOT NULL,
    PRIMARY KEY (student_id, offering_id)
);
CREATE TABLE results (
    student_id INT NOT NULL, offering_id INT NOT NULL,
    ca TINYINT UNSIGNED NOT NULL CHECK (ca <= 40),
    exam TINYINT UNSIGNED NOT NULL CHECK (exam <= 60),
    status ENUM('draft','submitted','approved','rejected') NOT NULL,
    PRIMARY KEY (student_id, offering_id),
    FOREIGN KEY (student_id, offering_id) REFERENCES registrations(student_id, offering_id)
);
ALTER TABLE departments ADD FOREIGN KEY (faculty_id) REFERENCES faculties(id);
ALTER TABLE programs ADD FOREIGN KEY (department_id) REFERENCES departments(id);
ALTER TABLE semesters ADD FOREIGN KEY (session_id) REFERENCES academic_sessions(id);
ALTER TABLE current_semester ADD FOREIGN KEY (semester_id) REFERENCES semesters(id);
ALTER TABLE users ADD FOREIGN KEY (department_id) REFERENCES departments(id);
ALTER TABLE courses ADD FOREIGN KEY (department_id) REFERENCES departments(id);
ALTER TABLE offerings ADD FOREIGN KEY (semester_id) REFERENCES semesters(id);
ALTER TABLE registrations ADD FOREIGN KEY (student_id) REFERENCES students(user_id),
                          ADD FOREIGN KEY (offering_id) REFERENCES offerings(id);
