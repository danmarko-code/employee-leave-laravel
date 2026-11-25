# Leave System Architecture Summary

## Roles
- Only two fixed roles exist: `employee` and `admin`. There is no manager or Approver role.
- `employee`: Submits leave requests, tracks balances, views own history, updates profile/password.
- `admin`: Approves or rejects leave requests, manages users, views reports and activity logs, and maintains settings.

## Pages & Views
- Auth: Login (`/login`), Cavite State University themed with a split institutional brand panel and a quiet sign-in form.
- Employee: Dashboard (`/dashboard`), Apply for Leave (`/apply`), My Requests (`/requests`), My Profile (`/profile`).
- Admin: Overview (`/dashboard` or `/overview`), Applications (`/applications`), Approval queue (`/approvals`), Employees (`/users`), Reports (`/reports`), Activity Log (`/activity`), Settings (`/settings`). `/dashboard` is role-aware: administrators see the admin Overview and employees keep their own dashboard.
- Settings tabs: Departments (`/settings/departments`), Positions (`/settings/positions`), Leave types (`/settings/leave-types`). Roles are fixed and read-only. Old management URLs redirect to these tabs.
- Overlays: Request Details Drawer, Approve/Reject Modal, Add/Edit User Modal.

## Features
- RBAC for `employee` and `admin` only, admin-only approvals, rate limiting / lockout, session security, working-day calculation excluding weekends.
- Departments contain positions. A position must belong to the selected department when a user is created or edited.
- Leave balance deductions, file attachments, search and status filtering, audit logging, reporting exports.
- Settings tabs keep departments, positions, and leave types in one place for administrators.
- Admin Overview reuses the employee balance ring partial for current-year totals across active employees; each ring links to the Balances filter for that leave type. With no balances for the year, it shows one empty card linking to Balances.
- Admin Overview also shows up to three approved upcoming leave requests, including requests already in progress, with employee and leave type eager loaded.
- Admin Overview at `/overview` returns 403 for employees; other admin pages also return 403. `/dashboard` stays open to both roles and shows the right dashboard for each.

## Grouped Navigation
- The admin sidebar has exactly five items: Dashboard, Employees, Reports, My leave, Settings. Each has its own icon. Applications, Approvals, Balances, and Activity are reached through the shared tab bars instead of sidebar links.
- Dashboard stays active on `/dashboard`, `/overview`, `/approvals*`, `/applications`, `/activity`, request detail pages reached from the approval queue, and other grouped dashboard URLs. Employees stays active on `/users*` and `/balances*`.
- A shared tab bar puts Overview, Approvals, Applications, and Activity on all four dashboard pages. It uses real links, `aria-current="page"` for the active tab, and the same styling and transitions as the settings tabs.
- A shared Employees tab bar puts People and Balances on both pages. Balances keeps its existing content, filters, and Edit action.
- The sidebar badge counts only pending requests from other users. It hides at zero and includes visually hidden text such as `3 pending approvals`. The count is memoized for one request, so the badge and overview card share one cheap count query.
- The Overview stat cards use an auto-fit grid that avoids one orphan card on a second row. Needs review lists the oldest five pending requests from other users with an Open link and a View all approvals link. Unpaid Leave and Who's out next share the second row at equal width and height, aligned with the cards above. The stretched Unpaid Leave card uses a column layout with a centered donut and a three-column legend; Who's out next scrolls inside a capped region. The full activity log stays on its own tab.
- Reports keeps its existing filters, table, and pagination; only the grid was adjusted so the fifth stat card does not sit alone.

## Brand & Visual Context
- Status: this is a live, usable website, not a prototype. `docs/prototype.html` is an older static mock-up kept for history; it is not the current UI.
- Identity: Cavite State University (CvSU) leave system. The tone is formal and institutional, not playful: deep forest green, university green, gold accents, white surfaces, and a calm pale-green page background.
- Shared CvSU palette: deep forest `#052e0b`, university green `#018002`, hover green `#016602`, gold `#e0cb74`, gold hairline `#b8962a`, ink `#13241a`, muted `#5a6b60`, line `#d9e2da`, page `#f3f6f2`, error `#b3261e` on `#fdeceb`.
- Login palette in `public/css/login.css`: `--cv-dark` `#052e0b`, `--cv-deep` `#0a4a12`, `--cv-green` `#018002`, `--cv-green-hover` `#016602`, `--cv-gold` `#e0cb74`, `--cv-gold-strong` `#b8962a`, `--cv-ink` `#13241a`, `--cv-muted` `#5a6b60`, `--cv-line` `#d9e2da`, `--cv-page` `#f3f6f2`, `--cv-danger` `#b3261e`, `--cv-danger-bg` `#fdeceb`.
- App palette in `public/css/app.css`: the old token names are kept because views and scripts depend on them. `--tide-950` `#052e0b`, `--tide-900` `#0a4a12`, `--lagoon` `#018002`, `--lagoon-700` `#016602`, `--lagoon-100` `#d8ebd8`, `--lagoon-50` `#eef6ef`, `--sun` `#e0cb74`, `--sun-100` `#f8f2dc`, `--bg` `#f3f6f2`. Status colors: approved `#0f7a34`, pending `#7a5b00` on pale gold, rejected `#b3261e`, cancelled muted green-gray.
- Login assets: `cvsu-seal.webp` on the brand panel, `cvsu-logo.webp` above the form title and in the one-time splash, `cvsu-pattern.svg` as a faint diamond field, `cvsu-favicon.png` as the shared favicon, and `cv-mail.svg`, `cv-lock.svg`, `cv-eye.svg`, `cv-eye-off.svg`, `cv-alert.svg`, `cv-check.svg` inside the form.
- Login typography: Georgia for the university name, system sans for the interface. No Google Fonts or other external request is needed on the login page.
- Login layout: two columns on desktop (brand 5fr, form 7fr); the brand collapses to a compact header on mobile. The form is left aligned, with one gold rule under the title and one gold edge on the brand panel.
- Preserved login hooks: `#loginCard`, `#loginForm`, `#loginBtn`, `#passwordToggle`, `#email`, `#password`, `#remember`, `.login-btn-label`, `.login-spinner`, `data-icon-on`, `data-icon-off`, `has-error`, `is-submitting`, and `is-loading`. `login.js` is unchanged.
- Preserved login behavior: CSRF, old input, per-field errors, first-error alert, success notice, forgot-password link, remember value `1`, password visibility toggle with eye/eye-off icons, spinner, submit fade-out, error shake, page-load reveal, and reduced-motion fallback.
- Authenticated UI: employee and admin screens share the login palette. The sidebar is deep green with a gold edge, primary buttons and focus rings are university green, pending is gold, approved is green, rejected is CvSU red. Spacing, layout, selectors, motion, and behavior are unchanged. The shared `#logo-mark` in `resources/views/partials/icons.blade.php` is green with a gold check.
- Balance rings keep their hooks: `stroke="var(--lagoon)"` for available, `stroke="var(--sun)"` for pending, and `#b8c5ba` for used days. Do not rename these hooks while retheming.

## Form Fields
- Login: `email`, `password`.
- Apply: `type` (select), `start` (date), `end` (date), `reason` (textarea), `file` (file attachment).
- Decision Modal: `note` (textarea), action button (`approve` / `reject`).
- User Form: `name`, `email`, `role`, `department`, `position`, `phone`, `is_active`. A temporary password is generated by the system.
- Password Change: `current_password`, `new_password`, `confirm_password`.

## Status Flow
- `pending` -> `approved` (Admin decision only)
- `pending` -> `rejected` (Admin decision only, with reason note)
- `pending` -> `cancelled` (Employee self-service cancellation while pending)
- Terminal states: `approved`, `rejected`, `cancelled`.

## Notable Animations & CSS Selectors
- Sidebar Indicator: `.nav-ind` (gliding pill via `top .35s var(--ease)`, `.nav-ind::after` shine).
- Button Sweeps: `.btn`, `.btn.primary` (`background-size: 260% 100%`, `:hover` shifts `background-position: 100% 0` in `.6s var(--ease)`).
- Glassmorphism: `.card`, `.tbl-wrap`, `.auth-card`, `.drawer`, `.modal`, `.toast` (`background: var(--glass)`, `backdrop-filter: blur(...) saturate(...)`, `border: 1px solid var(--glass-edge)`).
- View Transition: `.view-in :is(.ph,.sh,.toolbar,.tbl-wrap,.card)` (`animation: up .55s var(--ease-out)`, staggered `--i`/`--j` delays).
- Micro-interactions: `.auth-card.shake` (`@keyframes shake`), `.scrim` opacity fade, `.drawer` slide-in, `.btn:active` scale down (`.98`).
- Balance rings: `resources/views/partials/balance-ring.blade.php` is shared by employee and admin dashboards; `.legend i.available`, `.legend i.pending`, and `.legend i.used` set the dot colors in `public/css/app.css`.
- Login: `.cv-card` reuses `cv-in`/`cv-shake`/`cv-card-out`; `.cv-brand::before` uses `cv-pattern-drift`; `.login-submit.is-loading .login-spinner` uses `cv-spin`. All motion is disabled under `prefers-reduced-motion`.
