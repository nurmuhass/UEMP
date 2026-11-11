# Database design

This diagram covers the academic tables defined in [001_schema.sql](../database/001_schema.sql) and migrations [003](../database/003_offering_programs.sql), [005](../database/005_result_workflow.sql), and [006](../database/006_management.sql). Lecturer profiles are rows in `users` with `role = 'lecturer'`.

```mermaid
erDiagram
    faculties ||..o{ departments : contains
    departments ||..o{ programs : contains
    departments ||..o{ academic_levels : defines
    departments |o..o{ users : contains
    departments ||..o{ courses : owns
    users ||--o| students : has_profile
    programs ||..o{ students : enrolls
    academic_sessions ||..o{ semesters : contains
    semesters ||..o| current_semester : selected_by
    courses ||..o{ offerings : offered_as
    semesters ||..o{ offerings : schedules
    users ||..o{ offerings : teaches
    programs ||--o{ offering_programs : mapped_by
    offerings ||--o{ offering_programs : admits_via
    students ||--o{ registrations : makes
    offerings ||--o{ registrations : receives
    registrations ||--o| results : has_result

    faculties {
        INT id PK
    }
    departments {
        INT id PK
        INT faculty_id FK
    }
    programs {
        INT id PK
        INT department_id FK
    }
    academic_levels {
        INT id PK
        INT department_id FK
        SMALLINT level
    }
    users {
        INT id PK
        INT department_id FK "nullable for super_admin"
        ENUM role
    }
    students {
        INT user_id PK, FK
        INT department_id FK "part of both composite FKs"
        INT program_id FK
        SMALLINT academic_level
    }
    academic_sessions {
        INT id PK
    }
    semesters {
        INT id PK
        INT session_id FK
    }
    current_semester {
        INT singleton PK "restricted to 1"
        INT semester_id FK
    }
    courses {
        INT id PK
        INT department_id FK
        SMALLINT academic_level
    }
    offerings {
        INT id PK
        INT course_id FK
        INT department_id FK "part of both composite FKs"
        INT semester_id FK
        INT lecturer_id FK
    }
    offering_programs {
        INT offering_id PK, FK
        INT program_id PK, FK
    }
    registrations {
        INT student_id PK, FK
        INT offering_id PK, FK
    }
    results {
        INT student_id PK, FK
        INT offering_id PK, FK
    }
```

**Registration and result.** `registrations.student_id` references `students.user_id`, and `registrations.offering_id` references `offerings.id`. The exact duplicate guard is `PRIMARY KEY (student_id, offering_id)` on `registrations`. A result references that pair through `FOREIGN KEY (student_id, offering_id) REFERENCES registrations(student_id, offering_id)`. The pair is also the primary key of `results`, so each registration can have zero or one result. `UNIQUE (course_id, semester_id)` on `offerings` allows one offering per course per semester.

**Database enforcement.** Every line represents a foreign key. On `students`, the pairs `(user_id, department_id)` and `(program_id, department_id)` reference `users(id, department_id)` and `programs(id, department_id)`. On `offerings`, `(course_id, department_id)` and `(lecturer_id, department_id)` reference `courses(id, department_id)` and `users(id, department_id)`. These composite keys enforce department alignment. Dashed lines denote non-identifying relationships.

**PHP enforcement.** The database checks account existence and department membership through those keys. PHP checks that a student profile belongs to a student account, a lecturer assignment targets a lecturer account, and an offering's mapped programs belong to the same department. The `academic_level` values on `students` and `courses` have no foreign key to `academic_levels`. The [management handlers](../app/manage.php) validate levels and scoped choices, and [registration](../app/registration.php) checks that the active student's department, program, and level match an offering in the current semester.
