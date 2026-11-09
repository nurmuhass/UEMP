# UEMP assessment checklist and permission matrix

Source: *LEAD WEB PRAXIS MEDIA LIMITED.docx*, University Education Management Portal assessment.

## Implemented

- [x] Login, logout, role-based redirection, password hashes, session rotation, CSRF, and live account-status checks.
- [x] Four role dashboards with server-side role and record-scope enforcement.
- [x] Student profile editing for name and phone. Email, matriculation number, program, department, and level are managed by authorized administrators.
- [x] Current-semester course selection and registration. The server checks department, program, level, and semester, and the database prevents duplicate registrations.
- [x] Registered courses and unit totals, printable registration summary, approved academic history, semester GPA, CGPA, and printable result statement.
- [x] Lecturer class lists, validated CA and exam entry, calculated totals and grades, draft saving, submission, feedback, and course performance summaries.
- [x] Department result review, approval, rejection, correction, and resubmission. Submitted and approved scores are locked. Score changes and transitions are audited in the same transaction.
- [x] Department management of student accounts and profiles, lecturer accounts, programs, academic levels, courses, and allocations. Registration lists, rosters, and performance reports are scoped to the assigned department.
- [x] University management of faculties, departments, programs, academic levels, accounts, role assignments, account statuses, sessions, semesters, current-term selection, courses, and allocations.
- [x] University-wide registration and result monitoring, course performance reporting, and result and administrative audit views.
- [x] PHP and PDO with prepared statements, relational MySQL schema, public web root, environment-based configuration, shared responsive templates, versioned migrations, and demo credentials.
- [x] End-to-end verification for authentication, registration, result review and publication, and management and reporting.

## Remaining or limited

- [ ] Assessment submission package. A deployed public URL, submission-ready source repository link, and a short walkthrough or live demonstration still need to be supplied.
- [ ] Custom permission policies, if the assessor expects editable permissions beyond role assignment. The portal assigns one of the four assessment roles and enforces a fixed permission matrix.
- [ ] If required, add record retirement and paginated or searchable audit history. Management supports create and edit, with account suspension and deactivation. Referenced academic records are preserved, and monitoring displays the latest 100 events from each audit trail.

Assessment exclusions include payments, accommodation, admissions, biometric attendance, messaging gateways, mobile apps, transcript certification, and external university integrations.

## Role-permission matrix

| Permission | Student | Lecturer | Department Admin | Super Admin |
| --- | --- | --- | --- | --- |
| Open role dashboard | Student | Lecturer | Department | University |
| Read academic records | Own registrations and approved results | Registered students in assigned offerings | Department rosters, registrations, and review queue | University monitoring and audit |
| Read offerings | Own eligible and registered offerings | Assigned offerings | Own department | University-wide |
| Edit profile, register courses, and print summaries | Own permitted fields and academic records | — | — | — |
| Manage student and lecturer accounts | — | — | Own department | University-wide |
| Manage courses, allocations, programs, and academic levels | — | — | Own department | University-wide |
| Enter scores, save drafts, submit, and view feedback | — | Assigned offerings | — | — |
| Approve or reject submitted results | — | — | Own department | Oversight |
| View academic reports | Own history and GPA | Assigned offerings | Own department | University-wide |
| Manage faculties, departments, sessions, semesters, and current term | — | — | — | University-wide |
| Assign roles and activate, suspend, or deactivate accounts | — | — | — | University-wide |
| Read administrative and result audit history | — | — | Review status and feedback for departmental results | University-wide |

Dashboard routes use exact role matching. Department IDs, lecturer assignments, and student IDs come from the authenticated account and database relationships.
