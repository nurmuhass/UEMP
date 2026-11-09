-- The registrations composite primary key in 001 enforces student/offering uniqueness.
CREATE TABLE offering_programs (
    offering_id INT NOT NULL,
    program_id INT NOT NULL,
    PRIMARY KEY (offering_id, program_id),
    FOREIGN KEY (offering_id) REFERENCES offerings(id),
    FOREIGN KEY (program_id) REFERENCES programs(id)
);

-- Map existing offerings only where the department has one unambiguous program.
INSERT INTO offering_programs (offering_id, program_id)
SELECT o.id, MIN(p.id)
FROM offerings o JOIN programs p ON p.department_id = o.department_id
GROUP BY o.id
HAVING COUNT(*) = 1;
