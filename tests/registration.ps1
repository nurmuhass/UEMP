# Uses the disposable uemp_demo_regverify database and its registration fixtures.
param([string]$Base = 'http://127.0.0.1:8087')
. "$PSScriptRoot/smoke.ps1" -Base $Base
function SignIn($role) {
    $session = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    Expect (Login $session $role (Token (Request $session login))) 303
    $session
}
function Sql($query) {
    $result = & C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -u root -N uemp_demo_regverify -e $query
    if ($LASTEXITCODE) { throw 'SQL assertion failed' }
    $result
}

$student = SignIn student
$admin = SignIn department_admin
$econAdmin = SignIn department_admin.econ
try {
    $dashboard = Request $student student
    Expect $dashboard 200 'CSC205'
    if ($dashboard.Content -match 'ECO203|CSC207|CSC307|offering_id" value="14"') {
        throw 'An ineligible offering was listed'
    }
    $token = Token $dashboard
    Expect (Request $admin register POST @{csrf=(Token (Request $admin department_admin));offering_id=10}) 403
    Expect (Request $admin registration_summary) 403
    Expect (Request $student register GET) 405
    Expect (Request $student register POST @{csrf='invalid';offering_id=10}) 403
    Expect (Request $student register POST @{csrf=$token;offering_id=10;student_id=5}) 400
    Expect (Request $student 'register&student_id=5' POST @{csrf=$token;offering_id=10}) 400
    foreach ($id in 11,12,13,14,99999) {
        Expect (Request $student register POST @{csrf=$token;offering_id=$id}) 422
    }
    if ((Sql 'SELECT COUNT(*) FROM registrations') -ne 3) { throw 'A rejected request wrote a registration' }
    Expect (Request $student register POST @{csrf=$token;offering_id=10}) 303
    Expect (Request $student register POST @{csrf=$token;offering_id=10}) 409
    if ((Sql 'SELECT student_id FROM registrations WHERE offering_id=10') -ne 1) {
        throw 'Expected exactly one new registration for the signed-in student'
    }
    $unique = Sql "SELECT GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='registrations' AND INDEX_NAME='PRIMARY' AND NON_UNIQUE=0"
    if ($unique -ne 'student_id,offering_id') { throw 'Missing database uniqueness' }
    $dashboard = Request $student student
    Expect $dashboard 200 'Course registered'
    Expect $dashboard 200 'Total units:</strong> 9'
    $summary = Request $student registration_summary
    Expect $summary 200 'Total units:</strong> 9'
    Expect $summary 200 'CSC205'
    if ($summary.Content -match 'Approved score') { throw 'Grades appeared on the registration summary' }
    $department = Request $admin department_admin
    Expect $department 200 'CSC/2025/001'
    Expect $department 200 'Ada Okafor'
    if ($department.Content -match 'ECO/2025/001|Bisi Adeyemi') { throw 'Economics registration leaked' }
    $economics = Request $econAdmin department_admin
    Expect $economics 200 'ECO/2025/001'
    if ($economics.Content -match 'CSC/2025/001|Ada Okafor') { throw 'Computer Science registration leaked' }
    Write-Output 'Registration, duplicate key, all eligibility dimensions, identity tampering, CSRF, print summary and department scope passed'
} finally {
    Sql 'DELETE FROM registrations WHERE student_id=1 AND offering_id=10'
}
