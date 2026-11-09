# UEMP demo walkthrough

Use a freshly seeded assessment database. Run this once with the four [demo logins](README.md#demo-logins). Allow about four minutes.

1. **Student** · Open the dashboard as `student@example.test`. Register **CSC205 Computer Networks**. The registered total changes from 6 to 9 units. Open the printable registration summary. CSC205 has no approved score.
2. **Lecturer** · Sign out and sign in as `lecturer@example.test`. Open **Class lists and results**, choose **CSC205**, and locate **Ada Okafor**. Save CA **30** and exam **56** as a draft. Show the calculated **86 / A**, then submit for review.
3. **Department Admin** · Sign in as `department_admin@example.test`. Open **Review results**, choose **CSC205**, and reject the result with the feedback **Check exam total**. The department report shows the registration and review status.
4. **Lecturer** · Return to CSC205, show the feedback, correct the scores to CA **25** and exam **44**, and resubmit. The calculated result is **69 / B**.
5. **Department Admin** · Approve and publish the corrected CSC205 result.
6. **Student** · Reopen the dashboard. CSC205 now shows **69 / B**. Open **Academic history** and **Printable result statement**. The seeded 3-credit A and newly approved 3-credit B yield a **4.50 GPA and CGPA**. The unrelated draft for CSC203 stays hidden in the result statement.
7. **Super Admin** · Sign in as `super_admin@example.test`. Open **Manage university** and show faculties, accounts, and the current semester. Open **Monitoring and audit** to show both departments and the CSC205 score changes, rejection, resubmission, and approval.
8. **Access checks** · Try opening `index.php?r=super_admin` while signed in as a student and confirm HTTP 403. Try signing in as `suspended@example.test` using the demo password and confirm sign-in is denied.

Steps 1–7 modify demo data. Start with a fresh assessment database for a repeat walkthrough.
