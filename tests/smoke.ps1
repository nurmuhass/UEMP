param([string]$Base = 'http://127.0.0.1:8085')
$ErrorActionPreference = 'Stop'
Import-Module Microsoft.PowerShell.Utility
function Request($jar, $route, $method = 'GET', $body = $null) {
    $args = @{ Uri = "$Base/index.php?r=$route"; WebSession = $jar; Method = $method
        SkipHttpErrorCheck = $true; MaximumRedirection = 0; ErrorAction = 'SilentlyContinue' }
    if ($null -ne $body) { $args.Body = $body }
    Invoke-WebRequest @args
}
function Expect($response, $code, $pattern = '') {
    if ($response.StatusCode -ne $code -or ($pattern -and $response.Content -notmatch $pattern)) {
        throw "Expected HTTP $code and pattern '$pattern', got HTTP $($response.StatusCode)"
    }
}
function Token($response) {
    if ($response.Content -notmatch 'name="csrf" value="([a-f0-9]{64})"') { throw 'Missing CSRF token' }
    $Matches[1]
}
function Login($jar, $email, $csrf) {
    Request $jar login POST @{ email = "$email@example.test"; password = 'PortalDemo!2026'; csrf = $csrf }
}
$roles = [ordered]@{student='CSC201'; lecturer='CSC201'; department_admin='Computer Science'; super_admin='Economics'}
foreach ($role in $roles.Keys) {
    $jar = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    Expect (Request $jar $role) 302
    $csrf = Token (Request $jar login)
    $before = $jar.Cookies.GetCookies($Base)['UEMPSESSID'].Value
    Expect (Login $jar $role 'invalid') 403
    $response = Login $jar $role $csrf
    Expect $response 303
    if ([string]$response.Headers.Location -notmatch "r=$role$") { throw "Wrong redirect for $role" }
    if ($before -eq $jar.Cookies.GetCookies($Base)['UEMPSESSID'].Value) { throw 'Session ID did not rotate' }
    $dashboard = Request $jar $role
    Expect $dashboard 200 $roles[$role]
    if ($role -in 'student','lecturer','department_admin' -and $dashboard.Content -match 'ECO201|Economics') {
        throw "Cross-department data visible to $role"
    }
    if ($role -eq 'student' -and $dashboard.Content -match 'Draft|>98<') { throw 'Unapproved result leaked' }
    foreach ($other in $roles.Keys) {
        if ($other -ne $role) { Expect (Request $jar $other) 403 }
    }
    Expect (Request $jar logout) 405
    Expect (Request $jar logout POST @{csrf=$csrf}) 403
    Expect (Request $jar logout POST @{csrf=(Token $dashboard)}) 303
    Expect (Request $jar $role) 302
    Write-Output "$role passed login, rotation, dashboard scope, cross-role denials and logout"
}
foreach ($email in 'suspended','deactivated','unknown') {
    $jar = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
    Expect (Login $jar $email (Token (Request $jar login))) 401
    Expect (Request $jar student) 302
    Write-Output "$email denied"
}
$jar = [Microsoft.PowerShell.Commands.WebRequestSession]::new()
Expect (Request $jar unknown) 404
foreach ($path in '/database/001_schema.sql','/app/bootstrap.php','/README.md') {
    Expect (Invoke-WebRequest "$Base$path" -SkipHttpErrorCheck) 404
}
Write-Output 'Unknown route and private file checks passed'
