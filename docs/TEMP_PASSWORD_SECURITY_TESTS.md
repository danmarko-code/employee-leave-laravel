# Temporary password security tests

This file covers the one-time password flow added in the latest revision.

Run it only on localhost or a disposable test database. Do not paste real passwords into this file, tickets, or chat.

## What is protected

| Area | Expected behavior |
|---|---|
| Storage | Only an Argon2id hash is stored. The plain password is never written to the database, logs, audit log, URL, or flash messages shown elsewhere. |
| Display | The password appears once in an `Account created` card on the employee profile. The field is read-only and monospace. |
| Lifetime | The password is passed with `session()->flash()` and pulled once. Refresh or back does not show it again. |
| Cache | The redirect and profile response include `Cache-Control` with `no-store`, `no-cache`, and `must-revalidate`, plus `Pragma: no-cache`. |
| Copy | The Copy button is wired in `public/js/app.js` with no inline handler. It shows `Copied`. |
| Leave warning | Leaving the page while the unacknowledged card is visible shows a browser warning. `I've saved it` removes the card and the warning. |
| Reset | Only an admin can reset another user. Self-reset is blocked. The route is POST only, CSRF protected, policy checked, request checked, and rate limited. |
| Session kill | Reset deletes the target user's stored sessions and rotates the remember token. |
| Forced change | The target user has `must_change_password = true` and is sent to `/password/change` on login. |
| Audit | Reset writes an audit row with actor and target only. Never the password. |

## Run the automated tests

From the project root:

```bash
cd ./leave-system

php artisan test --filter='(one_time_password|temporary_password|copy_and_dismiss|forced_password_change|argon2id)'
```

Expected result: all matched tests pass.

Run the full suite:

```bash
php artisan test
```

Expected result: 83 tests pass.

The tests use an in-memory SQLite database, so they do not touch the MySQL data in `.env`.

For first-time setup:

```bash
php artisan migrate --seed
php artisan app:create-admin
```

For a normal restart:

```bash
php artisan serve
```

`php artisan migrate:fresh` is destructive and only for a local reset.

## Manual browser test

Start the app if it is not already running:

```bash
php artisan serve
```

Open `http://localhost:8000/login` and sign in with a local admin account.

### A. Create a user

1. Open `/users/create`.
2. Fill in a throwaway name and email.
3. Choose Employee, an active department, and a position in that department.
4. Submit the form.

Expected result:

- You land on the new employee's profile.
- A card titled `Account created` is visible.
- The temporary password is in a read-only monospace field.
- There is a Copy button and an `I've saved it` button.
- The card does not auto-dismiss.

### B. Copy and leave warning

1. Click Copy.
2. Try to leave or refresh the page before clicking `I've saved it`.
3. Click `I've saved it`.
4. Leave the page again.

Expected result:

- Copy shows `Copied`.
- The browser warns before leaving while the card is still visible.
- After `I've saved it`, the card is gone and there is no warning.

### C. Refresh and back

1. Refresh the profile page.
2. Press Back in the browser.

Expected result:

- The `Account created` card is not shown again.
- The password is not present in the page source.

### D. Response headers

Open browser DevTools, then Network, then select the redirect and the profile page request.

Expected result:

- `Cache-Control` contains `no-store`, `no-cache`, and `must-revalidate`. Laravel may add `private` as well.
- `Pragma` is `no-cache`.

### E. Reset the temporary password

1. Open the employee profile as an admin.
2. Click `Reset temporary password`.
3. Accept the confirm step.
4. Copy the new password from the same `Account created` card.
5. Sign out.
6. Try to sign in with the old password.
7. Sign in with the new password.

Expected result:

- A new card appears with a new password.
- The old password fails with the generic credential error.
- The new password signs in and redirects to `/password/change`.
- The user cannot open the dashboard until the password is changed.

### F. Blocked reset attempts

As an employee, try to POST to `/users/{id}/temporary-password`.

Expected result: `403`.

As an admin, try the same route with your own user id.

Expected result: `403`. The button is also hidden on your own profile.

Send a GET to `/users/{id}/temporary-password`.

Expected result: `405`.

Post to that route without a CSRF token.

Expected result: `419`.

Send more than three reset POSTs within 10 minutes.

Expected result: the fourth request returns `429`.

## Check stored data and audit log

After a reset, inspect the latest audit row:

```bash
php artisan tinker --execute='$log = App\Models\AuditLog::where("action", "user.temporary_password.reset")->latest("id")->first(); echo $log ? json_encode($log->only(["user_id", "action", "description", "properties"])) . PHP_EOL : "no reset log" . PHP_EOL;'
```

Expected result:

- `user_id` is the admin who did the reset.
- `action` is `user.temporary_password.reset`.
- `properties` contains only `target_user_id` and `target_email`.
- No password value appears anywhere.

Check stored sessions for the target user:

```bash
php artisan tinker --execute='$id = App\Models\User::where("email", "employee@company.test")->value("id"); echo "sessions=" . \Illuminate\Support\Facades\DB::table("sessions")->where("user_id", $id)->count() . PHP_EOL;'
```

Expected result: `sessions=0` for that user after the reset.

Check that the password was not logged:

```bash
read -rsp 'Temporary password to search for: ' TEMP_PASSWORD
echo
grep -F -- "$TEMP_PASSWORD" storage/logs/laravel.log
unset TEMP_PASSWORD
```

Expected result: no output. The password is read without echo, so it does not appear in the terminal or shell history.

## Failure means stop

Any of these is a security failure:

- The password appears in a URL, log, audit row, or normal flash message.
- Refresh or Back shows the card again.
- The old password still works after a reset.
- The target user can reach the dashboard before changing the password.
- An employee or the admin themselves can reset the password.
- The response headers do not include the no-store directives.
- Copy uses an inline `onclick` or similar handler.
