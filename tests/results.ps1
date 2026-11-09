param(
    [string]$Base = 'http://127.0.0.1:8087',
    [string]$Database = 'uemp_demo_regverify',
    [string]$Mysql = 'C:/xampp/mysql/bin/mysql.exe',
    [int]$DbPort = 3306,
    [switch]$FreshWorkflow
)
if ($Database -notmatch '^uemp_demo_[a-z0-9]*verify[a-z0-9]*$' -or $Base -notmatch '^http://127\.0\.0\.1:\d+$') {
    throw 'Use a disposable verification database and a local HTTP server.'
}
. "$PSScriptRoot/smoke.ps1" -Base $Base
function SignIn($email) {
    $session = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    Expect (Login $session $email (Token (Request $session login))) 303
    $session
}
function Sql($query) {
    $result = & $Mysql -h 127.0.0.1 -P $DbPort -u root -N $Database -e $query
    if ($LASTEXITCODE) { throw 'SQL assertion failed' }
    $result
}
$lecturer = SignIn lecturer
$admin = SignIn department_admin
$student = SignIn student
$otherStudent = SignIn student.econ
$otherLecturer = SignIn lecturer.econ
$otherAdmin = SignIn department_admin.econ
$super = SignIn super_admin
$offering = if ($FreshWorkflow) { 10 } else { 3 }
$code = if ($FreshWorkflow) { 'CSC205' } else { 'CSC203' }
$class = "lecturer_results&offering=$offering"
$review = "review_results&offering=$offering"
if ($FreshWorkflow) {
    $studentPage = Request $student student
    Expect $studentPage 200 'CSC205'
    $registrationToken = Token $studentPage
    Expect (Request $student register POST @{csrf='invalid';offering_id=$offering}) 403
    Expect (Request $student register POST @{csrf=$registrationToken;offering_id=$offering;student_id=5}) 400
    Expect (Request $student 'register&student_id=5' POST @{csrf=$registrationToken;offering_id=$offering}) 400
    Expect (Request $student register POST @{csrf=$registrationToken;offering_id=11}) 422
    if ((Sql "SELECT COUNT(*) FROM registrations WHERE offering_id=$offering") -ne '0') {
        throw 'A forged request wrote a registration.'
    }
    Expect (Request $lecturer $class) 200 'No students are registered'
    Expect (Request $student register POST @{csrf=$registrationToken;offering_id=$offering}) 303
    Expect (Request $student register POST @{csrf=$registrationToken;offering_id=$offering}) 409
    if ((Sql "SELECT student_id FROM registrations WHERE offering_id=$offering") -ne '1') {
        throw 'Expected exactly one registration for the signed-in student.'
    }
    Expect (Request $student registration_summary) 200 'Total units:</strong> 9'
    Expect (Request $admin department_admin) 200 'CSC205'
    Expect (Request $otherAdmin department_admin) 200 'ECO201'
    if ((Request $otherAdmin department_admin).Content -match 'CSC205|Ada Okafor') {
        throw 'Department dashboard leaked a foreign registration.'
    }
}
Expect (Request $lecturer $class) 200 'Ada Okafor'
$lt = Token (Request $lecturer $class)
$at = Token (Request $admin $review)
function Score($action, $revision, $ca=30, $exam=56, $id=1) {
    @{csrf=$lt; action=$action; revision=$revision; ca=$ca; exam=$exam; student_id=$id}
}
function Review($action, $revision, $feedback='') {
    @{csrf=$at; action=$action; revision=$revision; student_id=1; feedback=$feedback}
}
Expect (Request $student $class) 403
Expect (Request $super $review) 403
Expect (Request $otherLecturer $class) 404
Expect (Request $lecturer 'lecturer_results&offering=12') 404
Expect (Request $otherAdmin $review) 404
Expect (Request $lecturer $review POST (Review approve 0)) 403
Expect (Request $admin $class POST (Score save 0)) 403
Expect (Request $otherLecturer $class POST (@{csrf=(Token (Request $otherLecturer lecturer));action='save';revision=0;ca=30;exam=56;student_id=1})) 404
Expect (Request $otherAdmin $review POST (@{csrf=(Token (Request $otherAdmin department_admin));action='approve';revision=0;student_id=1})) 404
Expect (Request $lecturer $class POST (Score save 0 41 56)) 422
Expect (Request $lecturer $class POST (Score save 0 30 61)) 422
Expect (Request $lecturer $class POST (Score save 0 30 56 5)) 404
Expect (Request $admin $review POST (Review approve 0)) 409
Expect (Request $lecturer $class POST (Score save 0)) 303
Expect (Request $lecturer $class) 200 '(?s)Saved total</span><strong>86</strong>.*?Saved grade</span><strong>A</strong>'
Expect (Request $lecturer $class POST (Score submit 1)) 303
Expect (Request $lecturer $class POST (Score save 2)) 409
Expect (Request $admin $review POST (Review reject 2)) 422
Expect (Request $admin $review POST (Review reject 2 'Check exam total')) 303
Expect (Request $lecturer $class) 200 'Check exam total'
Expect (Request $lecturer $class POST (Score save 3 25 44)) 303
Expect (Request $lecturer $class POST (Score submit 4 25 44)) 303
Expect (Request $admin $review POST (Review approve 2)) 409
if ((Request $student student).Content -match '>69<') { throw 'Unapproved result published' }
Expect (Request $admin $review POST (Review approve 5)) 303
Expect (Request $student student) 200 '>69<'
Expect (Request $student student) 200 '<td>B</td>'
if ((Request $otherStudent student).Content -match ">69<|$code") { throw 'Result leaked to another student' }
Expect (Request $lecturer $class POST (Score save 6)) 409
$trail = Sql "SELECT GROUP_CONCAT(CONCAT(actor_id, ':', action, ':', JSON_UNQUOTE(JSON_EXTRACT(after_state, '$.status'))) ORDER BY id) FROM result_audit WHERE student_id=1 AND offering_id=$offering"
if ($trail -ne '2:save:draft,2:submit:submitted,3:reject:rejected,2:save:rejected,2:submit:submitted,3:approve:approved') {
    throw "Unexpected audit sequence: $trail"
}
$correction = Sql "SELECT CONCAT(JSON_EXTRACT(before_state, '$.ca'), '/', JSON_EXTRACT(before_state, '$.exam'), ' to ', JSON_EXTRACT(after_state, '$.ca'), '/', JSON_EXTRACT(after_state, '$.exam')) FROM result_audit WHERE student_id=1 AND offering_id=$offering AND action='save' AND JSON_EXTRACT(after_state, '$.revision')=4"
if ($correction -ne '30/56 to 25/44') { throw 'Audit did not preserve before and after scores' }

if ($FreshWorkflow) {
    if ((Sql "SELECT COUNT(*) FROM result_audit WHERE offering_id=$offering AND action='save' AND before_state IS NULL") -ne '1') {
        throw 'New result was missing its creation audit.'
    }
    Expect (Request $student academic_history) 200 'CGPA:</strong> 4.50'
    Expect (Request $student result_statement) 200 'CSC205'
    Expect (Request $super university_reports) 200 'Recent result audit events'
    try {
        Sql "UPDATE users SET status='suspended' WHERE id=1"
        Expect (Request $student student) 302
        $blocked = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
        Expect (Login $blocked student (Token (Request $blocked login))) 401
    } finally {
        Sql "UPDATE users SET status='active' WHERE id=1"
    }
    Expect (Request $student student) 302
    Write-Output 'Fresh registration-to-publication, duplicate rejection, forged registration, GPA and live suspension passed'
}

# Fault injection is confined to the verification database.
Sql "CREATE TRIGGER fail_result_audit BEFORE INSERT ON result_audit FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Test audit failure'"
try {
    $econReview = 'review_results&offering=2'
    $token = Token (Request $otherAdmin $econReview)
    Expect (Request $otherAdmin $econReview POST @{csrf=$token;action='approve';student_id=5;revision=0}) 500
    if ((Sql "SELECT CONCAT(status,':',revision) FROM results WHERE student_id=5 AND offering_id=2") -ne 'submitted:0') {
        throw 'A result change survived an audit failure'
    }
    if ((Sql 'SELECT COUNT(*) FROM result_audit WHERE student_id=5 AND offering_id=2') -ne '0') {
        throw 'Failed audit write left an event'
    }
} finally {
    Sql 'DROP TRIGGER fail_result_audit'
}
Write-Output 'Result workflow, stale forms, validation, role scope, publication, audit snapshots and rollback passed'
