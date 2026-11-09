# UEMP assessment checklist and permission matrix

Source: *LEAD WEB PRAXIS MEDIA LIMITED.docx*, University Education Management Portal assessment. This delivery covers the requested foundation. Open items track the rest of the assessment MVP and submission requirements.

## Implemented foundation

- [x] Secure login, role-based redirection, POST logout, password hashes, session rotation, and server-side CSRF checks.
- [x] Server-side role guards and live account-status checks. Suspended and deactivated users are blocked.
- [x] Four role dashboards. Student views own profile, registrations, units, and approved scores for the current semester. Lecturer views assigned offerings. Department admin views department offerings and review counts. Super admin views both departments.
- [x] Relational schema for faculties, departments, programs, users, student profiles, sessions, semesters, offerings, registrations, and results. Unique keys prevent duplicate registrations for the same offering and duplicate offerings for a course in a semester.
- [x] Plain PHP, PDO prepared statements, environment-based database credentials, shared PHP templates, Bootstrap, and vanilla JavaScript.
- [x] Versioned SQL, repeatable demo setup, test credentials, and linked sample academic records in two departments.
- [x] Responsive tables, escaped output, generic authentication errors, and role and status verification.

## Remaining assessment MVP

- [ ] Student self-service profile editing, eligible course browsing, and current-semester registration. Validate department, program, level, and semester on write.
- [ ] Student academic history, semester grades, GPA and CGPA, printable registration summary, and result statement. Document or configure grade boundaries and use credit-unit-weighted GPA.
- [ ] Lecturer class lists, per-student CA and exam entry, validation, calculated totals and grades, draft save, submission, feedback, and performance summaries.
- [ ] Departmental student registration, lecturer profile management, course management and allocations, academic levels and programs, registration review, and reports.
- [ ] Departmental result review, approval, rejection, and return for correction. Require submission before approval, block self-approval, lock submitted results until authorized reopening, and audit every submission, modification, approval, and rejection.
- [ ] Super-admin management of faculties, departments, programs, users, account statuses, roles and permissions, sessions, semesters, courses, and allocations. Add university-wide registration and approval monitoring, reports, and activity logs.
- [ ] Exercise the full registration and result-approval workflows once the write paths exist.
- [ ] Assessment submission package with live URL, GitHub source link, and short video or live demonstration. Setup, credentials, schema, and architecture notes are in this workspace.

Assessment exclusions include payments, accommodation, admissions, biometric attendance, messaging gateways, mobile apps, transcript certification, and external university integrations.

## Role-permission matrix

**Live** means enforced or available in this foundation. **Planned** means required by the assessment and tracked above. A dash denotes no grant.

| Permission | Student | Lecturer | Department Admin | Super Admin |
| --- | --- | --- | --- | --- |
| Open role dashboard | Live · student | Live · lecturer | Live · department | Live · university |
| Read student academic records | Live · own profile, registrations, approved current scores | Planned · assigned classes | Live · department registration and review counts | Planned · university oversight |
| Read offerings | Live · own registrations | Live · assigned offerings | Live · department offerings | Live · counts by department |
| Update personal profile, register courses, print summaries | Planned · own record and eligible courses | — | — | — |
| Manage student and lecturer profiles | — | — | Planned · own department | Planned · university-wide |
| Manage courses and lecturer allocations | — | — | Planned · own department | Planned · university-wide |
| Manage academic levels and programs | — | — | Planned · own department | Planned · programs university-wide |
| Enter scores, save drafts, submit and view feedback | — | Planned · assigned offerings | — | — |
| Approve, reject or return submitted results | — | — | Planned · own department | Planned · oversight |
| Academic reporting | Planned · own history and GPA | Planned · assigned classes | Planned · own department | Planned · university-wide |
| Manage faculties, departments, sessions and semesters | — | — | — | Planned |
| Manage account statuses, roles, permissions and system settings | — | — | — | Planned |
| Monitor activity and administrative audit trails | — | — | Planned · departmental result review | Planned · university-wide |

The dashboard routes use exact role matching. The university dashboard gives super admins their own aggregate view. Department IDs, lecturer assignments, and student IDs come from the authenticated account and database relationships.
