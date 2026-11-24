# Security report

This maps the controls in this app to OWASP Top 10 (2021). Paths are relative to the project root.

| # | Implemented protection | OWASP category | File(s) |
|---:|---|---|---|
| 1 | `role:admin` and `role:employee,admin` on route groups | A01 Broken Access Control | `bootstrap/app.php`, `routes/web.php` |
| 2 | Role middleware aborts with 403 on a role mismatch | A01 Broken Access Control | `app/Http/Middleware/EnsureUserHasRole.php` |
| 3 | Leave requests: owner or admin only; no self-approval | A01 Broken Access Control | `app/Policies/LeaveRequestPolicy.php` |
| 4 | Admin policy bound to User, Department, Position, LeaveType, LeaveBalance, AuditLog, Report | A01 Broken Access Control | `app/Providers/AppServiceProvider.php`, `app/Policies/AdminPolicy.php` |
| 5 | Admin and review actions use FormRequest `authorize()` plus validation | A01 Broken Access Control | `app/Http/Requests/*.php` |
| 6 | CSRF tokens in all web forms, checked by the web middleware group | A01 Broken Access Control | `resources/views/**/*.blade.php`, `bootstrap/app.php` |
| 7 | Attachments are downloadable only through an authorized controller route | A01 Broken Access Control | `app/Http/Controllers/LeaveController.php`, `app/Policies/LeaveRequestPolicy.php` |
| 8 | Passwords are hashed on write and hidden from model output | A02 Cryptographic Failures | `app/Models/User.php`, `app/Console/Commands/CreateAdmin.php` |
| 9 | Argon2id is the default hash driver and is explicit for temporary passwords | A02 Cryptographic Failures | `config/hashing.php`, `app/Http/Controllers/Admin/UserController.php` |
| 10 | `Password::uncompromised()` is used on password change, reset, and admin creation | A02 Cryptographic Failures | `app/Http/Requests/ChangePasswordRequest.php`, `app/Http/Requests/ResetPasswordRequest.php`, `app/Console/Commands/CreateAdmin.php` |
| 11 | Session cookie: `HttpOnly`, `SameSite=Lax`, `Secure` in production | A05 Security Misconfiguration | `config/session.php` |
| 12 | Debug off, directory indexes off, and requests route through `index.php` | A05 Security Misconfiguration | `.env`, `README.md`, `public/.htaccess` |
| 13 | Eloquent/query builder with bound parameters; no raw SQL in app code | A03 Injection | `app/Http/Controllers/**/*.php`, `app/Support/ReportQuery.php` |
| 14 | Blade auto-escaping of user-supplied values | A03 Injection | `resources/views/**/*.blade.php` |
| 15 | CSV cells neutralise `= + - @`; export sends `nosniff` | A03 Injection | `app/Http/Controllers/Admin/ReportController.php` |
| 16 | Uploads limited to pdf/jpg/jpeg/png and 5 MB | A04 Insecure Design | `app/Http/Requests/StoreLeaveRequest.php` |
| 17 | Uploads stored with random names on the local disk | A04 Insecure Design | `app/Http/Controllers/LeaveController.php` |
| 18 | Last admin cannot be demoted, deactivated, or deleted | A04 Insecure Design | `app/Support/AdminGuard.php`, `app/Http/Requests/UpdateUserRequest.php`, `app/Http/Controllers/Admin/UserController.php` |
| 19 | Leave types with requests or balances cannot be deleted | A04 Insecure Design | `app/Http/Controllers/Admin/LeaveTypeController.php` |
| 20 | Pagination capped at 1 to 25 per page | A04 Insecure Design | `ApprovalController.php`, `LeaveController.php`, `Admin/{Dashboard,User,LeaveBalance,Report,AuditLog}Controller.php` |
| 21 | One-time temporary password plus forced change at first login | A07 Identification and Authentication Failures | `app/Http/Controllers/Admin/UserController.php`, `app/Http/Middleware/EnsurePasswordIsChanged.php`, `resources/views/admin/users/show.blade.php` |
| 22 | Strong password rules on change and reset | A07 Identification and Authentication Failures | `app/Http/Requests/ChangePasswordRequest.php`, `app/Http/Requests/ResetPasswordRequest.php` |
| 23 | Login locked after 5 tries for 300s; forgot password limited to 3 per 600s | A07 Identification and Authentication Failures | `app/Http/Controllers/AuthController.php` |
| 24 | Generic login error prevents account enumeration | A07 Identification and Authentication Failures | `app/Http/Controllers/AuthController.php` |
| 25 | Session ID regenerated on login and invalidated on logout | A07 Identification and Authentication Failures | `app/Http/Controllers/AuthController.php` |
| 26 | Idle sessions expire after 120 minutes by default | A07 Identification and Authentication Failures | `config/session.php`, `.env.example` |
| 27 | Password reset tokens are hashed, expire after 60 minutes, and are single-use | A07 Identification and Authentication Failures | `app/Http/Controllers/AuthController.php`, `config/auth.php`, `database/migrations/0001_01_01_000000_create_users_table.php` |
| 28 | Temporary-password reset is POST-only with CSRF, policy, throttle, `no-store` headers, session deletion, and remember-token rotation | A07 Identification and Authentication Failures | `app/Http/Controllers/Admin/UserController.php`, `app/Http/Requests/ResetTemporaryPasswordRequest.php`, `routes/web.php` |
| 29 | Audit entries for auth, leave, approvals, admin changes, and exports | A09 Security Logging and Monitoring Failures | `app/Models/AuditLog.php`, `app/Http/Controllers/**/*.php` |
| 30 | Audit viewer is read-only with validated filters and pagination | A09 Security Logging and Monitoring Failures | `app/Http/Controllers/Admin/AuditLogController.php`, `resources/views/admin/activity/index.blade.php` |

Notes:

- Only `employee` and `admin` remain. Approver and other legacy roles were removed by migration.
- Login abuse control is in `AuthController` using `RateLimiter`. The only route-level `throttle` is on temporary-password reset.
- Temporary-password responses set `no-store`, `no-cache`, and `must-revalidate`. Only CSV exports set `X-Content-Type-Options`. There are no global security headers.
- The password reset link is written to `storage/logs/laravel.log`, including in local mode. Production must use a real mailer and remove that log call.
- No dedicated controls were implemented for A06, A08, or A10. The app does not accept user-controlled outbound URLs.
