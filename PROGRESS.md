Step 1 done
Step 2 done
Step 3 done
Step 4 done
Step 5a done
Step 5b done
Step 5c done
Step 7 done
Step 8 done
Revision: positions + manager autofill done
Revision: two roles, admin-only approvals, settings tabs done
Revision: temp password display done
Revision: docs updated
Revision: remember me fix done
Revision: admin leave requests done
Revision: data persistence fix done
Revision: date timezone fix done
Revision: attachment preview done
Revision: activity moved to dashboard done
Revision: dashboard polish done

# PROGRESS.md — Leave System (Laravel)
## 2026-11-24
- Design: keep dashboard teal theme; login = glass card over teal night-sky bg; animations on login only; splash shown once after login.
- Assets (public/assets/auth/): logo, favicon, login-bg, splash-ring, icons (mail, lock, eye, eye-off, check, alert, google).
- Bug diagnosed: Back after login reaches login page + stale cached admin dashboard (old CSRF/session → 419/403). Cause: login reachable while authed + browser bfcache. Fix: guest middleware, no-store headers, pageshow reload, session regenerate, POST logout, role-aware redirects.
- Test: admin login → logout → employee login → Back → should land on employee dashboard or login should bounce.
## Implementation log
- 2026-11-24 — Task 1: Rebuilt the login page with the glass card, teal night-sky background, SVG icons, login-only CSS/JS, and reduced-motion-safe animations; touched resources/views/auth/login.blade.php, public/css/login.css, public/js/login.js.
- 2026-11-24 — Task 2: Added the one-time post-login splash and flashed the session flag in the success path; touched app/Http/Controllers/AuthController.php, resources/views/partials/splash.blade.php, resources/views/layouts/app.blade.php.
- 2026-11-24 — Task 3: Hardened auth routing and back-button behavior with guest redirects, no-store headers, pageshow reload, session regeneration, POST logout, and role-aware redirects; touched routes/web.php, bootstrap/app.php, app/Http/Middleware/PreventBackHistory.php, app/Http/Middleware/EnsureUserHasRole.php, app/Models/User.php, app/Http/Controllers/AuthController.php, resources/views/layouts/app.blade.php, tests/Feature/AdminPagesTest.php, tests/Feature/ApprovalFlowTest.php, tests/Feature/DashboardActivityTest.php.
- 2026-11-25 — Fixed bfcache login back-button bug: scoped no-store headers to the guest GET /login route, kept role-aware redirects for authed users, added pageshow reload and load-time state reset in login.js, and clarified submit-only card fade-out in login.css; touched routes/web.php, public/js/login.js, public/css/login.css, PROGRESS.md.
- 2026-11-25 — Cleanup (commits 205c834, d1f15ac, dc7001f): removed resources/views/welcome.blade.php, resources/views/dashboard.blade.php; unused imports in Admin/DashboardController.php and Admin/ReportController.php; dead loginForm spinner handler in app.js with its .spin/.btn.loading CSS and @keyframes spin in app.css; unused --login-teal-2/--login-accent in login.css. Kept-but-unsure: UserFactory import, AuthController reset-link Log::info, window.showToast/#toasts, prototype-only app.css selectors, resources/{css,js}/app.js + vite.config.js, leave-assets/*.svg, public/favicon.ico, unused public/assets/auth SVGs, data-* hooks, config comments, console inspire command. Baseline was 84 tests/607 assertions (one regression test added earlier; tests untouched); every batch stayed 84/607 with no route-list diff. Smoke checks passed and laravel.log stayed empty.
