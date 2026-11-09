ALTER TABLE results
    ADD COLUMN feedback TEXT NULL,
    ADD COLUMN revision INT UNSIGNED NOT NULL DEFAULT 0;

CREATE TABLE result_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    offering_id INT NOT NULL,
    actor_id INT NOT NULL,
    action ENUM('save','submit','reject','approve') NOT NULL,
    before_state JSON NULL,
    after_state JSON NOT NULL,
    recorded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id, offering_id) REFERENCES results(student_id, offering_id),
    FOREIGN KEY (actor_id) REFERENCES users(id)
);
