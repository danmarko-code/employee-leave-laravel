# Security requirements check

Source: `Employee-Leave-Application-System.pdf`, section E, "Security Requirements".

Verdict: all eight listed requirements are implemented. Automated tests cover seven of them. SQL injection prevention is covered by code review plus the manual curl/sqlmap checks in `docs/SECURITY_TESTS.md`.

## Requirement map

| # | PDF requirement | Status | Where it is implemented | Proof |
|---:|---|---|---|---|
| 1 | Password Hashing - passwords must not be stored as plain text | Met | `config/hashing.php` uses `argon2id`. `app/Models/User.php` casts `password` as `hashed` and hides `password` and `remember_token` from model output. Temporary passwords are hashed before storage. | Automated: `test_argon2id_hashing_is_configured_and_active`, `test_admin_creates_user_with_one_time_password_and_forced_change`. Manual: `docs/TEMP_PASSWORD_SECURITY_TESTS.md`. |
| 2 | Authentication - only registered users can access the system | Met | `routes/web.php` puts login, forgot password, and reset password under `guest`; every other page is under `auth`. `AuthController::login()` checks the password, active flag, and locks out after 5 failed tries for 300 seconds. | Automated: `test_wrong_password_6_times_triggers_lockout`, `test_same_generic_error_for_wrong_email_and_wrong_password`, `test_logout_kills_session_and_records_audit_log`. |
| 3 | Role-Based Access Control - employees cannot access administrator pages | Met | `routes/web.php` wraps admin routes in `role:admin`. `app/Http/Middleware/EnsureUserHasRole.php` returns 403 on a role mismatch. Only `employee` and `admin` roles exist. | Automated: `test_non_admin_cannot_open_admin_urls`, `test_employee_cannot_open_approval_pages`, `test_only_two_roles_are_seeded`. |
| 4 | Data Privacy - employees can only view their own leave applications | Met | `LeaveController::index()` filters by `user_id`. `app/Policies/LeaveRequestPolicy.php` allows view, cancel, and attachment download only for the owner, with an admin exception for review. | Automated: `test_employee_sees_only_own_requests`, `test_idor_protection_prevents_viewing_another_employees_request`, `test_idor_protection_prevents_cancelling_another_employees_request`. |
| 5 | SQL Injection Prevention - use prepared statements | Met | All app queries use Eloquent or the query builder, which bind values. A search for `DB::raw`, `whereRaw`, `selectRaw`, and `orderByRaw` in `app/` found no matches. | Manual: `docs/SECURITY_TESTS.md` section 1, SQL injection payloads and sqlmap. No login, no data dump, no SQL error, no 500. |
| 6 | Input Validation - validate dates, required fields, and uploaded documents | Met | `app/Http/Requests/StoreLeaveRequest.php` requires `leave_type_id`, `start_date`, `end_date`, and `reason`; checks date order and dates not in the past; limits attachments to pdf/jpg/jpeg/png and 5 MB; requires a document when the leave type requires one; rejects weekends-only ranges, overlaps, and balance overuse. Other FormRequests validate admin input. | Automated: `test_employee_can_apply_for_leave_with_working_days_and_balance_deduction`, `test_mismatched_position_is_rejected`, `test_invalid_add_reopens_panel_with_errors`, `test_only_fixed_roles_are_accepted`, `test_page_size_is_hard_capped`. |
| 7 | Activity Logs - record important actions such as leave approval and rejection | Met | `ApprovalController::decide()` writes `leave.approved` or `leave.rejected` to `AuditLog`. Leave creation, login, logout, password change, reset requests, balance changes, settings changes, and exports are also logged. The audit viewer is admin-only and read-only. | Automated: `test_balance_update_is_audited`, `test_leave_type_changes_keep_audit_logs`, `test_audit_log_viewer_is_filtered_and_paginated`, `test_logout_kills_session_and_records_audit_log`. Manual: open `/activity` as admin and confirm approval/rejection rows. |
| 8 | Session Security - prevent unauthorized access to logged-in accounts | Met | `AuthController::login()` regenerates the session id; `logout()` invalidates the session and rotates the remember cookie. `config/session.php` sets `HttpOnly`, `SameSite=Lax`, 120 minute lifetime, and `Secure` in production. `EnforceRememberIdleTimeout` logs out idle remembered sessions. Password changes and temporary-password resets delete sessions and rotate remember tokens. | Automated: `test_logout_kills_session_and_records_audit_log`, `test_logout_rotates_remember_token_and_kills_remember_cookie`, `test_remembered_login_requires_sign_in_after_idle_timeout`, `test_admin_resets_temporary_password_and_invalidates_target_sessions`. Manual: `docs/SECURITY_TESTS.md` section 7. |

## How to run the automated checks

From the project root:

```bash
cd ./leave-system
php artisan test
```

Expected result: all tests pass.

The test config in `phpunit.xml` uses SQLite in memory. The CLI must have `pdo_sqlite` enabled. Check it with:

```bash
php -m | grep pdo_sqlite
```

If it is missing, install or enable the SQLite extension for PHP CLI. `tests/TestCase.php` aborts a test run if the active database name matches `DB_DATABASE` in `.env`, so the dev MySQL data cannot be used by tests.

Smaller groups for the PDF requirements:

```bash
php artisan test --filter='(argon2id|one_time_password|temporary_password|forced_password_change|copy_and_dismiss)'
```

Expected: all matched tests pass. Covers requirements 1 and 8.

```bash
php artisan test --filter='(non_admin_cannot_open_admin_urls|employee_cannot_open_approval_pages|employee_sees_only_own_requests|idor_protection|logout_kills_session|logout_rotates_remember_token|remembered_login_requires_sign_in_after_idle_timeout|wrong_password_6_times|same_generic_error)'
```

Expected: all matched tests pass. Covers requirements 2, 3, 4, and 8.

```bash
php artisan test --filter='(audit_log_viewer|balance_update_is_audited|leave_type_changes_keep_audit_logs|employee_can_apply_for_leave|mismatched_position|invalid_add_reopens_panel|only_fixed_roles|page_size_is_hard_capped|positions_filter_rejects)'
```

Expected: all matched tests pass. Covers requirements 6 and 7.

## How to run the manual checks

Detailed commands are already in these files:

- `docs/SECURITY_TESTS.md` section 1: SQL injection payloads and sqlmap for requirement 5.
- `docs/SECURITY_TESTS.md` section 2: stored and reflected XSS payloads for requirement 6.
- `docs/SECURITY_TESTS.md` section 3: CSRF token checks.
- `docs/SECURITY_TESTS.md` section 4: brute force and lockout for requirement 2.
- `docs/SECURITY_TESTS.md` section 6: IDOR checks for requirement 4.
- `docs/SECURITY_TESTS.md` section 7: cookie flags, session fixation replay, and logout invalidation for requirement 8.
- `docs/TEMP_PASSWORD_SECURITY_TESTS.md`: one-time password display, reset, and no-store headers for requirements 1 and 8.

Run these only on localhost or a disposable test database. Do not paste real passwords into a report.

## Notes before production

These items are not part of the eight PDF requirements, but they should be handled before a live deployment:

- `AuthController::sendResetLink()` writes the reset URL to `storage/logs/laravel.log`. Use a real mailer and remove that log call in production.
- There are no global security headers such as `Content-Security-Policy`, `X-Frame-Options`, `Referrer-Policy`, or `Strict-Transport-Security`. See `docs/SECURITY_TESTS.md` section 8.
- Set `APP_DEBUG=false`, `APP_ENV=production`, and `SESSION_SECURE_COOKIE=true`, and serve over HTTPS.
