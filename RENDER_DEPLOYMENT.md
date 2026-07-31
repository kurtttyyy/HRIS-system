# Render demo deployment

This configuration creates a free, disposable HRIS demonstration. Use fictional
records only. The SQLite database and uploaded files can be reset whenever
Render restarts, redeploys, or spins down the free service.

## Deploy

1. Commit and push the project to its GitHub repository.
2. Sign in to the Render Dashboard with GitHub.
3. Select **New > Blueprint**.
4. Connect the `HRIS-system` repository.
5. Render detects the repository-root `render.yaml` file.
6. Enter private values when prompted:
   - `DEFAULT_ADMIN_EMAIL`: the email used to sign in to the demo.
   - `DEFAULT_ADMIN_PASSWORD`: a unique demo password with at least 12
     characters. Do not reuse a personal password.
7. Select **Apply** and wait for the deployment status to become **Live**.
8. Open the generated `https://...onrender.com` URL and sign in with the
   credentials entered above.

## Before presenting

- Open the website a few minutes before the meeting. A free service sleeps
  after inactivity and needs time to wake.
- Confirm that the login and the pages needed for the demonstration work.
- Do not upload or enter real employee, applicant, payroll, medical, or patient
  information.
- Treat all changes made during the demo as temporary.
