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
- 2026-11-25 — Grouped admin navigation into five sidebar items with unique icons, grouped active states, and a server-rendered pending badge that excludes the admin's own requests. Added shared tab bars for the Dashboard and Employees pages. Touched app/Support/PendingApprovalCount.php, app/Providers/AppServiceProvider.php, app/Http/Controllers/LeaveController.php, app/Http/Controllers/Admin/DashboardController.php, resources/views/partials/sidebar.blade.php, resources/views/partials/admin-tabs.blade.php, public/css/app.css, tests/Feature/AdminNavigationTest.php.
- 2026-11-25 — Reorganized the admin Overview into a readable order: KPI strip, Needs review table, then balance rings and Who's out next. Tables now use the full column width and spacing comes from shared tokens. Touched resources/views/admin/overview.blade.php, public/css/app.css, tests/Feature/DashboardActivityTest.php.
- 2026-11-25 — Removed the two Overview chart cards and the bottom Recent activity card. The top Activity tab and the full read-only log stayed in place. Removed their view data, CSS, and partial, then adjusted tests and docs. Touched app/Http/Controllers/Admin/DashboardController.php, resources/views/admin/overview.blade.php, resources/views/partials/chart-card.blade.php, public/css/app.css, tests/Feature/AdminNavigationTest.php, tests/Feature/DashboardActivityTest.php, docs/prototype-summary.md.
- 2026-11-25 — Capped Who's out next in a keyboard-scrollable region, matched Unpaid Leave to its width and height, then rebuilt the stretched Unpaid Leave card as a column with a header, centered donut, and three-column legend. Touched resources/views/admin/overview.blade.php, resources/views/partials/balance-ring.blade.php, public/css/app.css, tests/Feature/DashboardActivityTest.php, tests/Feature/AdminOverviewRingsTest.php, docs/prototype-summary.md.
- 2026-11-25 — Documentation updated: grouped navigation summary, removed hard-coded test and assertion counts from security docs, and added the production composer install note to README. Touched docs/prototype-summary.md, docs/SECURITY_REQUIREMENTS.md, docs/TEMP_PASSWORD_SECURITY_TESTS.md, README.md.
Revision: admin dashboard rings done
Revision: login theme Cavite State University done
Revision: employee and admin UI re-themed to Cavite State University colors; docs updated for the live website
Revision: admin nav grouped
Revision: dashboard charts removed
Revision: dashboard recent activity card removed
Revision: dashboard upcoming list capped
Revision: dashboard upcoming card aligned
Revision: unpaid leave matched to upcoming card
Revision: unpaid leave height matched to upcoming card
Revision: unpaid leave inner layout rebuilt
Revision: DOLE statutory leave types preset done
