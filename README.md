# University Education Management Portal

## System Architecture

The web server serves `public/`. Browser requests reach [public/index.php](public/index.php), which selects a handler using the `r` query parameter and checks the HTTP method. [app/bootstrap.php](app/bootstrap.php) provides session checks, role guards, CSRF validation, and shared rendering. Handlers in `app/` query MySQL through the PDO connection in [app/db.php](app/db.php). The handler passes data to [app/templates/layout.php](app/templates/layout.php), which renders the portal shell and page template. CSS and JavaScript are served from `public/assets/`.

For example, `POST index.php?r=register` checks the student session and CSRF token, then passes the session user ID and submitted offering ID to [app/registration.php](app/registration.php). A single `INSERT … SELECT` checks the active semester, department, program, and level as it inserts the registration. Success redirects to the Student Dashboard, which reloads the registered courses and credit total.

The versioned schema and demo SQL live in `database/`. `scripts/seed-demo.php` loads the base fixtures, and `tests/` contains PowerShell checks.

## Database Structure and Relationships

The schema is defined in [001_schema.sql](database/001_schema.sql) and migrations [003](database/003_offering_programs.sql), [005](database/005_result_workflow.sql), and [006](database/006_management.sql).

| Tables                                                    | Records and relationships                                                                                                                                                                                                                                                                                                                                                                                                                                                          |
| --------------------------------------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `faculties`, `departments`, `programs`, `academic_levels` | `departments.faculty_id` references `faculties.id`. Programs and academic levels reference `departments.id` through `department_id`. Each `(department_id, level)` is unique in `academic_levels`.                                                                                                                                                                                                                                                                                 |
| `users`, `students`                                       | `users` stores unique email, password hash, contact details, role, and account status. `users.department_id` references `departments.id` and is null for Super Admins. A student profile uses `students.user_id` as its primary key and has a unique `matric_number`. Composite foreign keys `(user_id, department_id)` and `(program_id, department_id)` tie the profile to a user and program in the same department. Lecturer profiles are `users` rows with `role='lecturer'`. |
| `academic_sessions`, `semesters`, `current_semester`      | `semesters.session_id` references `academic_sessions.id`. `(session_id, name)` is unique. The singleton row in `current_semester` references the active term through `semester_id`.                                                                                                                                                                                                                                                                                                |
| `courses`, `offerings`, `offering_programs`               | Courses belong to a department and have a unique `code`, `units`, and `academic_level`. An offering uses `semester_id` to reference the term and composite keys `(course_id, department_id)` and `(lecturer_id, department_id)` to reference the course and lecturer account in that department. `UNIQUE (course_id, semester_id)` allows one offering per course per term. `offering_programs` references offerings and programs with primary key `(offering_id, program_id)`.    |
| `registrations`, `results`                                | `registrations.student_id` references `students.user_id`, and `offering_id` references `offerings.id`. The primary key `(student_id, offering_id)` prevents duplicate registration. `results` uses the same pair as both its primary key and foreign key to `registrations`. It stores `ca`, `exam`, `status`, `feedback`, and `revision`.                                                                                                                                         |
| `result_audit`, `admin_audit`, `management_lock`          | The audit tables reference `users.id` through `actor_id` and store action, timestamp, and before/after JSON. `result_audit` references the result through `(student_id, offering_id)`. `admin_audit` identifies the managed record by `entity` and `entity_id`. The singleton `management_lock` row serializes management writes.                                                                                                                                                  |

## Technology Stack

- **Backend** — Plain PHP 8 syntax, built-in sessions and password APIs, and PDO with `pdo_mysql`. No Composer install or application framework is needed.
- **Database** — MySQL-compatible SQL with transactional tables, foreign keys, check constraints, JSON audit fields, and a window function in migration `006`. Local checks used PHP 8.0.30 and XAMPP MariaDB 10.4.32. The [verification record](VERIFICATION.md) documents a separate Oracle MySQL 8.4.13 run.
- **Frontend** — Server-rendered HTML, Bootstrap **5.3.8 CSS** from a CDN with an integrity hash, [portal-theme.css](public/assets/portal-theme.css), and [portal.js](public/assets/portal.js). No frontend build step is needed.

## Security Implementation

- **Passwords and sessions.** [app/manage.php](app/manage.php) and [scripts/seed-demo.php](scripts/seed-demo.php) use `password_hash()`. [public/index.php](public/index.php) checks credentials with `password_verify()` and rotates the session ID on successful login. Strict-mode, cookie-only sessions use HttpOnly, SameSite=Lax, and Secure by default. [app/bootstrap.php](app/bootstrap.php) reloads the active account and verifies its role on protected requests, expires idle sessions after 30 minutes, and destroys the session on logout.
- **Permissions and ownership.** Students use their session identity for registration, profile changes, and approved results. Lecturers are scoped to assigned offerings and registered students. Department Admins are scoped to their department in management, review, and reports. Super Admins manage university-wide records and assign fixed roles. See [app/registration.php](app/registration.php), [app/student_academics.php](app/student_academics.php), [app/results.php](app/results.php), [app/manage_catalog.php](app/manage_catalog.php), and [app/reports.php](app/reports.php).
- **Forms and queries.** Writes use POST and session-bound CSRF tokens generated with `random_bytes()` and compared using `hash_equals()`. Handlers validate identifiers, score ranges, permitted fields, and scoped choices on the server. PDO binds input values using native prepared statements. Dynamic management table and column names come from a fixed catalog. Templates escape text through `e()`.
- **Approval and audit.** [app/results.php](app/results.php) checks the active actor and locks records in a transaction. Lecturers save or submit scores. Department Admins approve or reject submitted scores, with feedback required for rejection. Lecturers can correct and resubmit rejected scores. Submitted and approved scores are locked, stale revisions return HTTP 409, and only approved results appear to their owners. Result and management changes commit together with their audit entries. Management audits omit passwords and hashes.

## Setup Instructions

From a fresh clone, open PowerShell 7 in the repository root. Start MySQL or XAMPP MariaDB and enable PHP's `pdo_mysql` extension. The following commands use XAMPP paths and an empty-password **local** root account. Adjust the client path, port, and user if needed. Set `$askDbPassword` to `$true` for a password-protected account. Use a fresh database name beginning with `uemp_demo`.

```powershell
$ErrorActionPreference = 'Stop'
$php = 'C:/xampp/php/php.exe'
$mysql = 'C:/xampp/mysql/bin/mysql.exe'
$dbName = 'uemp_demo'
$dbPort = '3306'
$dbUser = 'root'
$httpPort = '8085'
$askDbPassword = $false

if ($dbName -cnotmatch '^uemp_demo(?:_[a-z0-9]+)?$') { throw 'Choose a dedicated demo database name' }
$mysqlArgs = @('-h', '127.0.0.1', '-P', $dbPort, '-u', $dbUser)
if ($askDbPassword) { $mysqlArgs += '-p' }

& $mysql @mysqlArgs -e "CREATE DATABASE $dbName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
if ($LASTEXITCODE) { throw 'Database creation failed. Choose a fresh name if it already exists.' }
Get-Content database/001_schema.sql -Raw | & $mysql @mysqlArgs $dbName
if ($LASTEXITCODE) { throw 'Base schema import failed' }

$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = $dbPort
$env:DB_NAME = $dbName
$env:DB_USER = $dbUser
$dbPassword = if ($askDbPassword) { Read-Host 'Database password' -MaskInput } else { '' }
[Environment]::SetEnvironmentVariable('DB_PASSWORD', $dbPassword, [EnvironmentVariableTarget]::Process)
Remove-Variable dbPassword
$env:SESSION_SECURE = '0'

& $php scripts/seed-demo.php
if ($LASTEXITCODE) { throw 'Demo seed failed' }
foreach ($file in '003_offering_programs.sql', '004_demo_offerings.sql', '005_result_workflow.sql', '006_management.sql') {
    Get-Content "database/$file" -Raw | & $mysql @mysqlArgs $dbName
    if ($LASTEXITCODE) { throw "Failed to apply $file" }
}

$sessionDir = New-Item -ItemType Directory -Force (Join-Path $env:TEMP "$dbName-sessions")
& $php -d "session.save_path=$($sessionDir.FullName)" -d display_errors=0 -S "127.0.0.1:${httpPort}" -t public
```

Open [the local sign-in page](http://127.0.0.1:8085/index.php?r=login). Use your selected HTTP port if you changed it. The four synthetic accounts below share the demo password **`PortalDemo!2026`**.

| Role             | Email                           |
| ---------------- | ------------------------------- |
| Student          | `student@example.test`          |
| Lecturer         | `lecturer@example.test`         |
| Department Admin | `department_admin@example.test` |
| Super Admin      | `super_admin@example.test`      |

The seeder supplies the demo hash when it loads `002_demo.sql` and skips a database that already has users. Run each migration once. Use a new database name for a clean repeat. From another terminal, run `pwsh -NoProfile -File tests/smoke.ps1 -Base http://127.0.0.1:8085`.

For hosting, serve only `public/` over HTTPS and use a writable PHP session directory outside it. Apache needs `AllowOverride AuthConfig` where the included `.htaccess` files apply. Supply `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASSWORD` through the process environment or host secret manager. The app reads environment variables directly. Leave `SESSION_SECURE` unset or set it to `1`, and keep PHP errors in a private log.

Use a separate setup identity for schema changes. The runtime identity needs SELECT across the schema, INSERT and UPDATE on managed records and results, INSERT on registrations, program mappings, and audit tables, DELETE on student profiles and offering-program mappings, and UPDATE on `registrations` and `management_lock` for locking reads. Keep demo fixtures on synthetic assessment instances. A real-data installation needs private initial administrator provisioning. The repository provides the demo seeder. Hosted HTTPS, document-root restrictions, and real-data provisioning were outside the current local check. See the [live URL checklist](VERIFICATION.md#live-url-checklist).

## Major Technical Decisions

1. **Server-rendered PHP.** A front controller and shared templates keep the request path short and make local setup possible with one PHP process. Navigation and form submissions generally reload the page.
2. **Separate courses from offerings.** A course holds catalog data, while an offering holds the term, lecturer, and eligible program mappings. This supports teaching the same course in later terms, at the cost of extra joins and allocation setup.
3. **Enforce scope at the record level.** Fixed roles select the handler, and the session identity, department, or lecturer assignment limits the query. Composite keys add database enforcement for related records. A new permission policy requires code changes.
4. **Use explicit result states and transactional audits.** Draft, submitted, rejected, and approved states separate marking from publication. Row locks and revisions protect review decisions from stale forms. This adds transaction and audit storage overhead.
5. **Calculate grades and GPA on the server.** [app/results.php](app/results.php) adds CA (0–40) and exam (0–60), then applies A/B/C/D/E/F thresholds of 70/60/50/45/40/0. [app/student_academics.php](app/student_academics.php) assigns 5/4/3/2/1/0 points and divides credit-weighted points by approved credit units per semester or across all terms. Approved failures count. A grading policy change requires a code change and can affect historical calculations.
