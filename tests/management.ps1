# Run against a freshly seeded disposable uemp_demo_manageverify database.
param([string]$Base = 'http://127.0.0.1:8088')
. "$PSScriptRoot/smoke.ps1" -Base $Base
function SignIn($email, $password='PortalDemo!2026') {
    $session = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    $csrf = Token (Request $session login)
    Expect (Request $session login POST @{email="$email@example.test";password=$password;csrf=$csrf}) 303
    $session
}
function Save($session, $route, $body, $status=303) {
    $body.csrf = Token (Request $session $route)
    Expect (Request $session $route POST $body) $status
}
function Sql($query) {
    $result = & C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -u root -N uemp_demo_manageverify -e $query
    if ($LASTEXITCODE) { throw 'Verification SQL failed' }
    $result
}
$super = SignIn super_admin
$dept = SignIn department_admin
$student = SignIn student
$lecturer = SignIn lecturer
$university = 'manage_university&entity='
$department = 'manage_department&entity='
Expect (Request $dept "${university}faculties") 403
Expect (Request $student 'manage_department') 403
Expect (Request $dept "${department}faculties") 404
Expect (Request $dept "${department}users&id=5") 404
Expect (Request $dept "${department}users&id=3") 404
Expect (Request $dept "${department}courses&id=2" POST @{csrf=(Token (Request $dept manage_department));code='X';title='X';units=1;academic_level=200}) 404
Expect (Request $student 'university_reports') 403
Expect (Request $lecturer 'department_reports') 403

Save $super "${university}faculties" @{name='Test Faculty'}
$faculty = Sql "SELECT id FROM faculties WHERE name='Test Faculty'"
Save $super "${university}faculties&id=$faculty" @{name='Renamed Faculty'}
Save $super "${university}departments" @{faculty_id=$faculty;name='Test Department'}
$newDept = Sql "SELECT id FROM departments WHERE name='Test Department'"
Save $super "${university}programs" @{department_id=$newDept;name='Test Program'}
Save $super "${university}academic_sessions" @{label='2027/2028'}
$sessionId = Sql "SELECT id FROM academic_sessions WHERE label='2027/2028'"
Save $super "${university}semesters" @{session_id=$sessionId;name='First semester'}
$semester = Sql "SELECT id FROM semesters WHERE session_id=$sessionId"
Save $super "${university}semesters&id=$semester" @{action='current'}
if ((Sql 'SELECT semester_id FROM current_semester') -ne $semester) { throw 'Current semester was not updated' }
Save $super "${university}semesters&id=1" @{action='current'}

Save $dept "${department}academic_levels" @{level=300}
Save $dept "${department}programs" @{name='Department Program'}
$program = Sql "SELECT id FROM programs WHERE name='Department Program' AND department_id=1"
Save $dept "${department}users" @{name='Scoped Lecturer';email='scoped.lecturer@example.test';role='lecturer';password='ScopedDemo!2026'}
$lecturerId = Sql "SELECT id FROM users WHERE email='scoped.lecturer@example.test'"
Save $dept "${department}users" @{name='Scoped Student';email='scoped.student@example.test';role='student';password='ScopedDemo!2026';program_id=$program;academic_level=300;matric_number='CSC/TEST/001'}
Save $dept "${department}users" @{name='Forged Admin';email='forged@example.test';role='super_admin';password='ScopedDemo!2026'} 422
Save $dept "${department}users" @{name='Wrong Program';email='wrong@example.test';role='student';password='ScopedDemo!2026';program_id=2;academic_level=200;matric_number='CSC/TEST/002'} 422
Save $dept "${department}courses" @{code='CSC399';title='Verification Course';units=4;academic_level=300}
$course = Sql "SELECT id FROM courses WHERE code='CSC399'"
# Invoke-WebRequest expects the literal HTML multi-select field name.
Save $dept "${department}offerings" @{course_id=$course;semester_id=1;lecturer_id=6;'program_ids[]'=$program} 422
Save $dept "${department}offerings" @{course_id=$course;semester_id=1;lecturer_id=$lecturerId;'program_ids[]'=$program}
$offering = Sql "SELECT id FROM offerings WHERE course_id=$course AND lecturer_id=$lecturerId"
if (!$offering) { throw 'Allocation was not created' }
$scoped = SignIn scoped.lecturer 'ScopedDemo!2026'
$account = @{department_id=1;name='Scoped Lecturer';email='scoped.lecturer@example.test';role='lecturer';status='suspended'}
Save $super "${university}users&id=$lecturerId" $account
Expect (Request $scoped lecturer) 302
$account.status = 'active'
$account.role = 'department_admin'
Save $super "${university}users&id=$lecturerId" $account 422
$account.role = 'lecturer'
Save $super "${university}users&id=$lecturerId" $account
$scoped = SignIn scoped.lecturer 'ScopedDemo!2026'
Expect (Request $scoped lecturer_reports) 200 'CSC399'
if ((Request $scoped lecturer_reports).Content -match 'CSC201|ECO201') { throw 'Lecturer saw unassigned offerings' }
$scopedId = Sql "SELECT id FROM users WHERE email='scoped.student@example.test'"
Save $super "${university}users&id=$scopedId" @{department_id=1;name='Scoped Student';email='scoped.student@example.test';role='lecturer';status='deactivated'}
if ((Sql "SELECT CONCAT(role, ':',status) FROM users WHERE id=$scopedId") -ne 'lecturer:deactivated') { throw 'Role/status update failed' }

Save $student student_profile @{name='Ada Updated';phone='+234 800 000 0000'}
Expect (Request $student student_profile) 200 'Ada Updated'
Save $student student_profile @{name='Forbidden';phone='';student_id=5;program_id=2} 422
if ((Sql 'SELECT name FROM users WHERE id=5') -ne 'Bisi Adeyemi') { throw 'Another profile was modified' }
# Approved A (3 credits) plus approved E (2 credits) yields CGPA 3.40.
# A second semester fixture also distinguishes semester GPA from CGPA.
Sql "INSERT INTO offerings VALUES (90,1,1,$semester,2); INSERT INTO courses VALUES (90,1,'CSC290','Weighted History',2,200); INSERT INTO offerings VALUES (91,90,1,$semester,2); INSERT INTO registrations VALUES (1,91); INSERT INTO results (student_id,offering_id,ca,exam,status) VALUES (1,91,20,22,'approved')"
$history = Request $student academic_history
Expect $history 200 'CGPA:</strong> 3.40'
Expect $history 200 'Semester GPA:</strong> 5.00'
Expect $history 200 'Semester GPA:</strong> 1.00'
if ($history.Content -match 'CSC203|ECO201') { throw 'Unapproved or foreign results appeared' }
Expect (Request $student result_statement) 200 'Print statement'
$departmentReport = Request $dept department_reports
Expect $departmentReport 200 'CSC399'
if ($departmentReport.Content -match 'Economics|ECO201|Bisi Adeyemi') { throw 'Department report leaked records' }
Expect (Request $super university_reports) 200 'Recent administrative changes'
if ((Sql "SELECT COUNT(*) FROM admin_audit WHERE JSON_EXTRACT(after_state, '$.password_hash') IS NOT NULL OR JSON_EXTRACT(after_state, '$.password') IS NOT NULL") -ne '0') { throw 'Credential leaked to audit' }
Write-Output 'Management, department scope, profile, weighted history, reports and audit passed'
