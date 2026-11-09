ALTER TABLE users ADD COLUMN phone VARCHAR(32) NULL;

CREATE TABLE academic_levels (
    id INT PRIMARY KEY,
    department_id INT NOT NULL,
    level SMALLINT NOT NULL,
    UNIQUE (department_id, level),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    CHECK (level > 0)
);

INSERT INTO academic_levels (id, department_id, level)
SELECT ROW_NUMBER() OVER (ORDER BY department_id, level), department_id, level
FROM (
    SELECT department_id, academic_level AS level FROM students
    UNION SELECT department_id, academic_level FROM courses
) existing_levels;

CREATE TABLE management_lock (
    id INT PRIMARY KEY CHECK (id = 1)
);
INSERT INTO management_lock VALUES (1);

CREATE TABLE admin_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_id INT NOT NULL,
    entity VARCHAR(32) NOT NULL,
    entity_id INT NOT NULL,
    action VARCHAR(16) NOT NULL,
    before_state JSON NULL,
    after_state JSON NOT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (actor_id) REFERENCES users(id)
);
