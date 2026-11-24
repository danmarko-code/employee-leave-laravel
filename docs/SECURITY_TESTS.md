# Security tests for localhost

Run these yourself. Do not run them against a live site.

Set up one terminal first:

```bash
cd ./leave-system

BASE=http://localhost:8000
VICTIM=victim@example.com
REQ_ID=1

USER_JAR=/tmp/leave-user.cookies
ADMIN_JAR=/tmp/leave-admin.cookies
EMP_A_JAR=/tmp/leave-emp-a.cookies
EMP_B_JAR=/tmp/leave-emp-b.cookies

login_token() {
  curl -s -c "$1" "$BASE/login" \
    | grep -o 'name="_token" value="[^"]*"' \
    | head -1 | sed 's/.*value="//;s/"//'
}

csrf() {
  curl -s -b "$1" -c "$1" "$BASE$2" \
    | grep -o 'name="csrf-token" content="[^"]*"' \
    | head -1 | sed 's/.*content="//;s/"//'
}

login() {
  local jar="$1" email="$2" pass="$3"
  local token
  token=$(login_token "$jar")
  curl -s -b "$jar" -c "$jar" -o /dev/null -X POST "$BASE/login" \
    --data-urlencode "_token=$token" \
    --data-urlencode "email=$email" \
    --data-urlencode "password=$pass"
}
```

Log in the jars you need before tests that need a session:

```bash
login "$USER_JAR"  "$VICTIM" 'your-password'
login "$ADMIN_JAR" 'admin@example.com' 'your-admin-password'
login "$EMP_A_JAR" 'employee-a@example.com' 'your-password'
login "$EMP_B_JAR" 'employee-b@example.com' 'your-password'
```

If your session cookie is not named `laravel-session`, change the name in the commands below. The name comes from `SESSION_COOKIE` or `APP_NAME`.

---

## 1. SQL injection

### 1.1 Manual payloads on login

```bash
for PAYLOAD in "' OR 1=1 -- " "' UNION SELECT 1,2,3 -- " "admin'--"; do
  TOKEN=$(login_token /tmp/sqli-login.cookies)
  printf 'payload: %s -> ' "$PAYLOAD"
  curl -s -o /tmp/sqli-login.out -w '%{http_code}\n' \
    -b /tmp/sqli-login.cookies -c /tmp/sqli-login.cookies \
    -H 'Accept: application/json' -X POST "$BASE/login" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "email=$PAYLOAD" \
    --data-urlencode "password=wrong"
  cat /tmp/sqli-login.out; echo
done
```

Expected safe result: no login, no dashboard redirect, no SQL error, no table or column names. The JSON should say the credentials do not match. A 500 with a query fragment is a failure.

### 1.2 Manual payloads on search and filter fields

```bash
PAYLOAD="' OR 1=1 -- "

check_filter() {
  printf '%s?%s -> ' "$1" "$2"
  curl -s -G -o /tmp/sqli-filter.out -w '%{http_code}\n' \
    -b "$ADMIN_JAR" --data-urlencode "$2=$PAYLOAD" "$BASE$1"
  grep -iE 'sql|syntax|pdo|exception|column|table' /tmp/sqli-filter.out | head -3
}

check_filter /users q
check_filter /activity action
check_filter /approvals status
check_filter /requests status

printf '/reports?from -> '
curl -s -G -o /tmp/sqli-filter.out -w '%{http_code}\n' \
  -b "$ADMIN_JAR" --data-urlencode "from=2026-01-01' OR '1'='1" "$BASE/reports"
grep -iE 'sql|syntax|pdo|exception|column|table' /tmp/sqli-filter.out | head -3
```

Expected safe result: 200, 302, or a validation error. No 500. No SQL text. No rows from other users. The `q` value is shown as plain text in `/users`.

### 1.3 sqlmap on the login form

Note: run `php artisan cache:clear` before the next sqlmap run. Lockout from this run stays in the cache and will interfere with section 1.4.

```bash
sqlmap -u "$BASE/login" \
  --data="email=$VICTIM&password=wrong" \
  --csrf-url="$BASE/login" --csrf-token="_token" \
  --batch --level=2 --risk=1 --ignore-redirects \
  --output-dir=/tmp/sqlmap-login
```

Expected safe result: no injectable parameter. If it reports 419, add `--cookie="laravel-session=PASTE_ADMIN_SESSION"` and rerun. Any dump of users, hashes, or schema is a failure.

### 1.4 sqlmap on one filter endpoint

Note: run `php artisan cache:clear` first if section 1.3 triggered lockout. The lockout cache will interfere with this run.

```bash
sqlmap -u "$BASE/users?q=test" \
  --cookie="laravel-session=PASTE_ADMIN_SESSION" \
  --batch --level=2 --risk=1 --ignore-redirects \
  --output-dir=/tmp/sqlmap-users-filter
```

Expected safe result: `q` is not injectable. No 500 responses used as oracle. No data dumped.

---

## 2. XSS

### 2.1 Stored XSS in the remarks field

The remarks field is named `note` in this app.

```bash
TOKEN=$(csrf "$ADMIN_JAR" "/approvals/$REQ_ID")

curl -s -o /dev/null -w 'reject: %{http_code}\n' \
  -b "$ADMIN_JAR" -c "$ADMIN_JAR" \
  -X POST "$BASE/approvals/$REQ_ID/reject" \
  --data-urlencode "_token=$TOKEN" \
  --data-urlencode 'note=<img src=x onerror=alert(1)>'

curl -s -b "$ADMIN_JAR" "$BASE/approvals/$REQ_ID" \
  | grep -F '&lt;img src=x onerror=alert(1)&gt;'
```

Expected safe result: reject returns 302. The stored note appears as `&lt;img ...&gt;`. No `<img src=x onerror=alert(1)>` tag appears in the page source. No alert runs.

### 2.2 Stored XSS in the name field

```bash
EMAIL="xss-test-$(date +%s)@example.com"
ROLE_ID=$(php artisan tinker --execute="echo App\Models\Role::where('name','employee')->value('id');")
DEPARTMENT_ID=$(php artisan tinker --execute="echo App\Models\Department::where('name','IT')->where('is_active',1)->value('id');")
POSITION_ID=$(php artisan tinker --execute="echo App\Models\Position::where('department_id', $DEPARTMENT_ID)->where('is_active',1)->value('id');")
TOKEN=$(csrf "$ADMIN_JAR" /users/create)

LOCATION=$(curl -s -D - -o /dev/null \
  -b "$ADMIN_JAR" -c "$ADMIN_JAR" \
  -X POST "$BASE/users" \
  --data-urlencode "_token=$TOKEN" \
  --data-urlencode 'name=<script>alert(1)</script>' \
  --data-urlencode "email=$EMAIL" \
  --data-urlencode "role_id=$ROLE_ID" \
  --data-urlencode "department_id=$DEPARTMENT_ID" \
  --data-urlencode "position_id=$POSITION_ID" \
  --data-urlencode 'is_active=1' \
  | awk 'tolower($1)=="location:"{print $2}' | tr -d '\r')

echo "$LOCATION"
curl -s -b "$ADMIN_JAR" "$LOCATION" | grep -F '&lt;script&gt;alert(1)&lt;/script&gt;'
```

Expected safe result: the department and position are active and the position belongs to that department. The account is created. The name is shown as text. No `<script>alert(1)</script>` tag appears in the HTML. No alert runs.

### 2.3 Reflected XSS in the name search field

```bash
curl -s -b "$ADMIN_JAR" \
  "$BASE/users?q=%3Cscript%3Ealert(1)%3C%2Fscript%3E" \
  | grep -F '&lt;script&gt;alert(1)&lt;/script&gt;'
```

Expected safe result: the payload is printed as escaped text in the search box. No script tag in the HTML. No alert runs.

---

## 3. CSRF

```bash
curl -s -o /dev/null -w 'login without token: %{http_code}\n' \
  -X POST "$BASE/login" \
  --data 'email=victim@example.com&password=wrong'
```

Expected safe result: `419`.

Also check an authenticated action:

```bash
curl -s -o /dev/null -w 'create user without token: %{http_code}\n' \
  -b "$ADMIN_JAR" -X POST "$BASE/users" \
  --data 'name=No Token&email=no-token@example.com&role_id=2&title=Test'
```

Expected safe result: `419`. No user is created.

---

## 4. Brute force, lockout, and throttle

```bash
for i in $(seq 1 7); do
  TOKEN=$(login_token /tmp/brute.cookies)
  printf 'attempt %s: ' "$i"
  curl -s -b /tmp/brute.cookies -c /tmp/brute.cookies \
    -H 'Accept: application/json' -X POST "$BASE/login" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "email=$VICTIM" \
    --data-urlencode 'password=definitely-wrong' \
    | head -c 220
  echo
done
```

Expected safe result: attempts 1 to 5 return the generic credential error. Attempt 6 and later say too many attempts and show a wait time in seconds. The key is email plus IP. The wait is 300 seconds.

Reset for the next test:

```bash
php artisan cache:clear
```

Forgot-password limiter check:

```bash
for i in 1 2 3 4; do
  TOKEN=$(login_token /tmp/forgot.cookies)
  printf 'forgot %s: ' "$i"
  curl -s -o /dev/null -w '%{http_code}\n' \
    -b /tmp/forgot.cookies -c /tmp/forgot.cookies \
    -H 'Accept: application/json' -X POST "$BASE/forgot-password" \
    --data-urlencode "_token=$TOKEN" \
    --data-urlencode "email=$VICTIM"
done
```

Expected safe result: after 3 tries the response is a rate-limit error. The wait is 600 seconds.

---

## 5. DoS flood

Login POST flood with a valid CSRF token:

```bash
TOKEN=$(login_token /tmp/ab-login.cookies)
COOKIE=$(awk '/laravel-session/{print "laravel-session="$7}' /tmp/ab-login.cookies)

printf '_token=%s&email=%s&password=wrong' "$TOKEN" "$VICTIM" > /tmp/login-post.txt

ab -n 100 -c 10 -S \
  -p /tmp/login-post.txt -T application/x-www-form-urlencoded \
  -H "Cookie: $COOKIE" \
  "$BASE/login"
```

Expected safe result: no 5xx, no timeouts, lockout after 5 tries. If throttle middleware is added, expect 429. The current app returns a lockout validation error, so seeing 422 instead of 429 is a config gap to fix, not a pass.

Heavy page flood plus a responsiveness probe:

```bash
COOKIE=$(awk '/laravel-session/{print "laravel-session="$7}' "$ADMIN_JAR")

wrk -t2 -c20 -d30s -H "Cookie: $COOKIE" "$BASE/reports" &
FLOOD_PID=$!
sleep 2
curl -s -o /dev/null -w 'responsiveness: %{http_code} in %{time_total}s\n' "$BASE/login"
wait "$FLOOD_PID"
```

Expected safe result: probe returns 200 quickly and the server stays responsive. No 5xx and no request timeouts. If you require 429 on `/reports`, add throttle middleware; this page is not throttled today.

---

## 6. IDOR

Use a request owned by employee A. Employee B must not open it.

```bash
printf 'B opens A request: '
curl -s -o /dev/null -w '%{http_code}\n' -b "$EMP_B_JAR" "$BASE/requests/$REQ_ID"

printf 'B opens A attachment: '
curl -s -o /dev/null -w '%{http_code}\n' -b "$EMP_B_JAR" "$BASE/requests/$REQ_ID/attachment"

printf 'B cancels A request: '
TOKEN=$(csrf "$EMP_B_JAR" "/requests/$REQ_ID")
curl -s -o /dev/null -w '%{http_code}\n' \
  -b "$EMP_B_JAR" -X POST "$BASE/requests/$REQ_ID/cancel" \
  --data-urlencode "_token=$TOKEN"
```

Expected safe result: 403, or 404 if the id does not exist. Employee A gets 200 on their own request. Admin may view it. No body, attachment, or cancel action for employee B.

---

## 7. Session tests

### 7.1 Cookie flags

```bash
curl -s -D - -o /dev/null "$BASE/login" | grep -i '^set-cookie:'
```

Expected safe result: session cookie has `HttpOnly` and `SameSite=Lax`. Over HTTPS in production it must also have `Secure`. Locally over plain HTTP, `Secure` may be absent because `APP_ENV=local`.

### 7.2 Session fixation replay

```bash
rm -f /tmp/fix-old.cookies /tmp/fix-login.cookies /tmp/fix-replay.cookies

curl -s -c /tmp/fix-old.cookies -o /dev/null "$BASE/login"
cp /tmp/fix-old.cookies /tmp/fix-replay.cookies

login /tmp/fix-login.cookies "$VICTIM" 'your-password'

BEFORE=$(awk '/laravel-session/{print $7}' /tmp/fix-replay.cookies)
AFTER=$(awk '/laravel-session/{print $7}' /tmp/fix-login.cookies)

echo "before=$BEFORE"
echo "after=$AFTER"
[ "$BEFORE" != "$AFTER" ] && echo 'OK: session id changed' || echo 'FAIL: same session id'

printf 'pre-login cookie on dashboard: '
curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' \
  -b /tmp/fix-replay.cookies "$BASE/dashboard"
```

Expected safe result: the login session id differs from the pre-login id. Replaying the saved pre-login cookie on `/dashboard` redirects to `/login`.

### 7.3 Logout invalidation

```bash
cp "$ADMIN_JAR" /tmp/old.cookies
TOKEN=$(csrf "$ADMIN_JAR" /dashboard)

curl -s -o /dev/null -w 'logout: %{http_code}\n' \
  -b "$ADMIN_JAR" -c "$ADMIN_JAR" \
  -X POST "$BASE/logout" --data-urlencode "_token=$TOKEN"

printf 'replayed old session on dashboard: '
curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' \
  -b /tmp/old.cookies "$BASE/dashboard"
```

Expected safe result: logout returns 302 to `/login`. The copied old cookie no longer opens `/dashboard`; it redirects to `/login`.

---

## 8. Headers and misconfiguration

### 8.1 curl banner and header check

```bash
curl -sI "$BASE/login"

curl -sI "$BASE/login" \
  | grep -iE '^(server|x-powered-by|x-aspnet-version|via):' \
  || echo 'OK: no technology banners'

curl -sI "$BASE/login" \
  | grep -iE 'x-frame-options|content-security-policy|strict-transport-security|referrer-policy|permissions-policy' \
  || echo 'FINDING: security headers missing'
```

Expected safe result: no technology banners, no debug output, no stack trace. Security headers should be present. The current app does not set them globally, so `FINDING: security headers missing` is a real issue to fix.

### 8.2 Nikto

```bash
nikto -h "$BASE" -output /tmp/nikto-leave.txt
```

Expected safe result: no critical or high findings. No exposed `.env`, `.git`, `vendor`, or config files. Informational notes about the web server are acceptable but should be reviewed.

### 8.3 OWASP ZAP baseline

```bash
zap-baseline.py -t "$BASE" -r /tmp/zap-leave.html -a
```

Expected safe result: no High or Medium risk alerts. Any SQL injection, XSS, CSRF, or sensitive-file alert is a failure. Review the HTML report at `/tmp/zap-leave.html`.

---

## 9. Sensitive files

```bash
for PATH_TO_TEST in /.env /storage /vendor /.git /composer.json /package.json /.htaccess; do
  printf '%-28s ' "$PATH_TO_TEST"
  curl -s -o /dev/null -w '%{http_code}\n' "$BASE$PATH_TO_TEST"
done
```

Expected safe result: `403` or `404` for every path. `200` means the file or directory is reachable and must be fixed. Never paste the contents of `.env` into a report.
