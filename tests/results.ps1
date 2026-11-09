param([string]$Base = 'http://127.0.0.1:8087')
. "$PSScriptRoot/smoke.ps1" -Base $Base
function SignIn($email) {
    $session = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    Expect (Login $session $email (Token (Request $session login))) 303
    $session
}
$lecturer = SignIn lecturer
$admin = SignIn department_admin
$student = SignIn student
$otherStudent = SignIn student.econ
$otherLecturer = SignIn lecturer.econ
$otherAdmin = SignIn department_admin.econ
$super = SignIn super_admin
$class = 'lecturer_results&offering=3'
$review = 'review_results&offering=3'
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
Expect (Request $lecturer $class) 200 '86 · A'
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
if ((Request $otherStudent student).Content -match '>69<|CSC203') { throw 'Result leaked to another student' }
Expect (Request $lecturer $class POST (Score save 6)) 409
function Sql($query) {
    $result = & C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -u root -N uemp_demo_regverify -e $query
    if ($LASTEXITCODE) { throw 'SQL assertion failed' }
    $result
}
$trail = Sql "SELECT GROUP_CONCAT(CONCAT(actor_id, ':', action, ':', JSON_UNQUOTE(JSON_EXTRACT(after_state, '$.status'))) ORDER BY id) FROM result_audit WHERE student_id=1 AND offering_id=3"
if ($trail -ne '2:save:draft,2:submit:submitted,3:reject:rejected,2:save:rejected,2:submit:submitted,3:approve:approved') {
    throw "Unexpected audit sequence: $trail"
}
$correction = Sql "SELECT CONCAT(JSON_EXTRACT(before_state, '$.ca'), '/', JSON_EXTRACT(before_state, '$.exam'), ' to ', JSON_EXTRACT(after_state, '$.ca'), '/', JSON_EXTRACT(after_state, '$.exam')) FROM result_audit WHERE student_id=1 AND offering_id=3 AND action='save' AND JSON_EXTRACT(after_state, '$.revision')=4"
if ($correction -ne '30/56 to 25/44') { throw 'Audit did not preserve before and after scores' }

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
