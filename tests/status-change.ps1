# Run only against the isolated, seeded verification database.
param([string]$Base = 'http://127.0.0.1:8085')
. "$PSScriptRoot/smoke.ps1" -Base $Base
function Set-DemoStudentStatus($status) {
    & C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -P 33316 -u root uemp_demo_verify -e "UPDATE users SET status = '$status' WHERE email = 'student@example.test'"
    if ($LASTEXITCODE) { throw "Database status update failed" }
}
foreach ($status in 'suspended','deactivated') {
    $jar = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    Expect (Login $jar student (Token (Request $jar login))) 303
    Expect (Request $jar student) 200 'CSC201'
    try {
        Set-DemoStudentStatus $status
        Expect (Request $jar student) 302
    } finally {
        Set-DemoStudentStatus active
    }
    Expect (Request $jar student) 302
    Write-Output "$status invalidated an existing session"
}
