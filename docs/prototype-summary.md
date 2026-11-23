# Prototype Architecture Summary

## Roles
- `employee`: Submits leave requests, tracks balances, views own history, updates profile/password.
- `admin`: Manages/reviews all leave applications, manages users and leave types, views reports and activity logs.

## Pages & Views
- Auth: Login (`/login`).
- Employee: Dashboard (`/dashboard`), Apply for Leave (`/apply`), My Requests (`/requests`), My Profile (`/profile`).
- Admin: Overview (`/overview`), Applications (`/requests`), Employees (`/users`), Leave Types (`/types`), Reports (`/reports`), Activity Log (`/activity`).
- Overlays: Request Details Drawer, Approve/Reject Modal, Add/Edit User Modal.

## Features
- RBAC role guards, rate limiting / lockout, session security, working-day calculation excluding weekends.
- Leave balance deductions, file attachments, search and status filtering, audit logging, reporting exports.

## Form Fields
- Login: `email`, `password`.
- Apply: `type` (select), `start` (date), `end` (date), `reason` (textarea), `file` (file attachment).
- Decision Modal: `note` (textarea), action button (`approve` / `reject`).
- User Form: `name`, `email`, `role`, `department`, `title`, `manager`, `password`, quota fields.
- Password Change: `current_password`, `new_password`, `confirm_password`.

## Status Flow
- `pending` -> `approved` (Admin decision)
- `pending` -> `rejected` (Admin decision with reason note)
- `pending` -> `cancelled` (Employee self-service cancellation while pending)
- Terminal states: `approved`, `rejected`, `cancelled`.

## Notable Animations & CSS Selectors
- Sidebar Indicator: `.nav-ind` (gliding pill via `top .35s var(--ease)`, `.nav-ind::after` shine).
- Button Sweeps: `.btn`, `.btn.primary` (`background-size: 260% 100%`, `:hover` shifts `background-position: 100% 0` in `.6s var(--ease)`).
- Glassmorphism: `.card`, `.tbl-wrap`, `.auth-card`, `.drawer`, `.modal`, `.toast` (`background: var(--glass)`, `backdrop-filter: blur(...) saturate(...)`, `border: 1px solid var(--glass-edge)`).
- View Transition: `.view-in :is(.ph,.sh,.toolbar,.tbl-wrap,.card)` (`animation: up .55s var(--ease-out)`, staggered `--i`/`--j` delays).
- Micro-interactions: `.auth-card.shake` (`@keyframes shake`), `.scrim` opacity fade, `.drawer` slide-in, `.btn:active` scale down (`.98`).
