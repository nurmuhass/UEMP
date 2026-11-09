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
if ((Request $otherStudent student).Content -match '>69<|CSC203') { throw 'Result leaked to another student' }
Expect (Request $lecturer $class POST (Score save 6)) 409
Write-Output 'Result workflow, stale forms, validation, role scope and publication passed'
