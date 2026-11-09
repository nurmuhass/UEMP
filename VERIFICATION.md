# Portal verification

## Fresh Oracle MySQL run

Verified with Oracle MySQL Community Server **8.4.13** and XAMPP PHP **8.0.30** on Windows. MySQL ran on loopback port **33318** with an isolated temporary data directory. The live assessment host was outside this run.

I ran the [README fresh setup](README.md#fresh-local-setup) with the Oracle MySQL client, database `uemp_demo_releaseverify`, database port `33318`, and PHP port `8089`. All schema and migration steps succeeded. I ran the same setup again with a separate `uemp_demo_grantverify` database and then ran PHP on port `8088` as a restricted database user. Administrative test assertions continued to use the isolated database's setup account.

```powershell
$mysql = Join-Path $env:TEMP 'uemp-oracle-mysql8413/mysql-8.4.13-winx64/bin/mysql.exe'
& $mysql -h 127.0.0.1 -P 33318 -u root -e "SELECT VERSION()"
pwsh -NoProfile -File tests/results.ps1 -Base http://127.0.0.1:8089 -Database uemp_demo_releaseverify -Mysql $mysql -DbPort 33318 -FreshWorkflow
pwsh -NoProfile -File tests/results.ps1 -Base http://127.0.0.1:8088 -Database uemp_demo_grantverify -Mysql $mysql -DbPort 33318 -FreshWorkflow
Get-ChildItem app,public,scripts -Recurse -Filter *.php | ForEach-Object { & C:/xampp/php/php.exe -l $_.FullName; if ($LASTEXITCODE) { throw 'PHP lint failed' } }
git diff --check
git ls-files -ci --exclude-standard
```

The workflow test changes the disposable demo database. Use a freshly seeded database for a repeat run.

| Check | Observed result |
| --- | --- |
| Fresh README setup | Database, seed, and migrations completed on Oracle MySQL 8.4.13. |
| Four roles and session security | All four signed in, received the correct dashboard redirect, rotated their session IDs, and signed out. All 12 cross-role dashboard requests returned 403. |
| Registration and duplicate prevention | The student registered CSC205. A second request returned 409. SQL confirmed exactly one registration for the signed-in student and the summary showed 9 units. |
| Forged submissions | Invalid CSRF returned 403. Adding another student's ID to the registration body or URL returned 400. An Economics offering returned 422. SQL confirmed zero registrations before the valid request. A lecturer score request for an unregistered student returned 404. |
| Department boundaries | The Computer Science department saw CSC205 and the Economics department did not. Direct requests from other-department lecturers and admins to the CSC205 result routes returned 404. Cross-role result writes returned 403. |
| Complete result workflow | The new CSC205 registration appeared in the class list. A draft of 86/A was saved, submitted, and locked. The department admin rejected it with feedback. The lecturer corrected it to 69/B and resubmitted. A stale approval returned 409. The current approval published 69/B only to the owning student. |
| History and statement | The student history showed 4.50 CGPA and the statement included CSC205 after approval. The Economics student could see neither CSC205 nor the newly approved score. |
| Blocked accounts | Seeded suspended and deactivated accounts returned 401. Suspending an already signed-in student invalidated the session and blocked a fresh login. Reactivating the account did not restore the invalidated session. |
| Audit integrity | Six expected CSC205 audit events were recorded with actor and status. The correction preserved before/after scores. A forced audit error rolled back the result approval and left no audit event. |
| Restricted database user | The full workflow passed after correcting the deployment grants. A Super Admin created a test faculty with that same runtime account, and SQL found one matching administrative audit event. |
| Private paths and lint | The SQL schema, app bootstrap, and README were inaccessible by direct URL on the local public root. PHP lint and `git diff --check` passed. |

## Failures corrected and rerun

- The first restricted PHP launch used port 8090, which another process already occupied. The retry used 8088. An intermediate launch supplied a short Windows temp path containing a tilde to PHP's INI parser. Using the resolved long path restored session startup. The README setup already uses the resolved directory object's full path.
- The initial restricted grants allowed registration inserts, but a result write returned 500 at the locking read. I granted UPDATE on `registrations` and `management_lock` for MySQL locking reads, updated the deployment instructions, and reran the full workflow plus an audited administration write. Both passed.
- The README contained a stray UTF-16 fragment. I rewrote it as UTF-8 and ran setup from the updated command block.

## Live URL checklist

These checks still need to run on the actual hosted URL.

- [ ] Open the URL from a separate device or network. Verify the certificate, HTTP-to-HTTPS redirect, and session cookie flags.
- [ ] Confirm that the production web server serves `public/` and denies direct requests for PHP internals, SQL, documentation, and any environment files. Confirm server errors use a private log.
- [ ] Verify the hosting database version, migration state, secret injection, runtime grants, and writable PHP session path.
- [ ] Sign in and out with all four demo accounts and verify each lands on the intended dashboard. Try a cross-role route and a cross-department result URL.
- [ ] Run the [walkthrough](DEMO.md) on a fresh synthetic assessment database. Check duplicate and suspended-account behavior and confirm both print previews and Bootstrap styling in a browser.
- [ ] Confirm the assessment link and the source repository link are accessible to reviewers. Supply the demo credentials and walkthrough video or live demonstration.
