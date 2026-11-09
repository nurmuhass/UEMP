# Foundation verification

I ran these commands from the project directory. XAMPP MySQL was stopped, so I used its MariaDB 10.4.32 binary on port 33316 with a temporary copy of the stock backup data directory. PHP CLI was 8.0.30 with pdo_mysql enabled.

```powershell
C:/xampp/php/php.exe -v
C:/xampp/php/php.exe -m
$d = Join-Path $env:TEMP 'uemp-foundation-db'
Copy-Item C:/xampp/mysql/backup $d -Recurse
Start-Process C:/xampp/mysql/bin/mysqld.exe -ArgumentList '--no-defaults',"--datadir=`"$d`"",'--basedir=C:/xampp/mysql','--port=33316','--bind-address=127.0.0.1' -WindowStyle Hidden -PassThru
& C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -P 33316 -u root -e "SELECT VERSION()"
$mysql = 'C:/xampp/mysql/bin/mysql.exe'
& $mysql -h 127.0.0.1 -P 33316 -u root -e "CREATE DATABASE uemp_demo_verify CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
Get-Content database/001_schema.sql -Raw | & $mysql -h 127.0.0.1 -P 33316 -u root uemp_demo_verify
$env:DB_HOST = '127.0.0.1'
$env:DB_PORT = '33316'
$env:DB_NAME = 'uemp_demo_verify'
$env:DB_USER = 'root'
[Environment]::SetEnvironmentVariable('DB_PASSWORD','',[EnvironmentVariableTarget]::Process)
$env:SESSION_SECURE = '0'
C:/xampp/php/php.exe scripts/seed-demo.php
C:/xampp/php/php.exe scripts/seed-demo.php
$d = New-Item -ItemType Directory -Force (Join-Path $env:TEMP 'uemp-foundation-sessions')
Start-Process C:/xampp/php/php.exe -ArgumentList '-d',"session.save_path=`"$($d.FullName)`"",'-d','display_errors=0','-S','127.0.0.1:8085','-t','public' -RedirectStandardError (Join-Path $d 'server.log') -WindowStyle Hidden -PassThru
pwsh -NoProfile -File tests/smoke.ps1
pwsh -NoProfile -File tests/status-change.ps1
Get-ChildItem app,public,scripts -Recurse -Filter *.php | ForEach-Object { C:/xampp/php/php.exe -l $_.FullName; if($LASTEXITCODE){throw 'Lint failed'} }
& C:/xampp/mysql/bin/mysql.exe -h 127.0.0.1 -P 33316 -u root uemp_demo_verify -e "SELECT role,status,COUNT(*) AS accounts,MIN(CHAR_LENGTH(password_hash)) AS hash_length FROM users GROUP BY role,status; SELECT COUNT(*) AS departments FROM departments; SELECT COUNT(*) AS current_terms FROM current_semester; SELECT status,COUNT(*) AS results FROM results GROUP BY status;"
```

- Schema application and first seed succeeded. Second seed skipped. The SQL check found seven active accounts across four roles, two blocked accounts, two departments, one current term, and one result in each of draft, submitted, and approved status.
- Each of the four primary demo accounts passed anonymous redirect, valid login, correct role redirect, session ID rotation, dashboard content and scope, and logout checks. All 12 cross-role route requests returned HTTP 403.
- Invalid login and logout CSRF tokens returned HTTP 403. GET logout returned HTTP 405. Access after logout redirected to login.
- Suspended, deactivated, and unknown accounts returned HTTP 401. Switching an already signed-in student to either blocked status redirected the next request to login and invalidated the session. The script restored the demo account to active after each check.
- Unknown routes and direct requests for the schema, app bootstrap, and README returned HTTP 404. All nine PHP files passed lint.
- During setup, the initial test run needed an explicit import of the PowerShell Utility module. The first HTTP launch returned 500. I relaunched PHP with an explicit writable session directory and reran the full suite successfully.

The checks used PHP's built-in HTTP server and XAMPP MariaDB. Apache hosting, Oracle MySQL, HTTPS behavior, and browser rendering were outside this verification run.
