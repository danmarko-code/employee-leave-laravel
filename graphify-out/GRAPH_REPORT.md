# Graph Report - leave-system  (2026-11-26)

## Corpus Check
- 234 files · ~105,907 words
- Verdict: corpus is large enough that graph structure adds value.
- Unclassified: 23 file(s) not represented in the graph (top: (none) 17, .css 3, .example 1)

## Summary
- 872 nodes · 1672 edges · 97 communities (19 shown, 78 thin omitted)
- Extraction: 99% EXTRACTED · 1% INFERRED · 0% AMBIGUOUS · INFERRED: 19 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Community Hubs (Navigation)
- User group
- AuditLog group
- composer.json group
- Blueprint group
- Request group
- UserController group
- FormRequest group
- AdminPagesTest group
- LeaveController group
- package.json group
- LeaveType group
- TestCase group
- AdminPagesTest group
- Controller group
- SettingsController group
- CreateAdmin group
- ReportController group
- AdminNavigationTest group
- OrganizationManagementTest group
- Rule group
- AppServiceProvider group
- SecurityAuthenticationTest group
- View group
- Str group
- EmployeeLeaveSystemTest group
- RedirectResponse group
- AdminOverviewRingsTest group
- DashboardActivityTest group
- DashboardController group
- date-utils.js group
- AdminLeaveRequestTest group
- Department group
- UpdatePositionRequest group
- UpdateLeaveBalanceRequest group
- UserController group
- LoginRequest group
- ForgotPasswordRequest group
- app.blade group
- UpdateLeaveTypeStatusRequest group
- ExampleTest group
- overview.blade group
- laravel-boost group
- activity/index.blade group
- applications.blade group
- balances/index.blade group
- users/index.blade group
- approvals/index.blade group
- approvals/show.blade group
- dashboard.blade group
- employee/show.blade group
- settings/departments.blade group
- settings/leave-types.blade group
- settings/positions.blade group
- balances/edit.blade group
- reports/index.blade group
- create.blade group
- users/edit.blade group
- users/show.blade group
- change-password.blade group
- forgot-password.blade group
- reset-password.blade group
- apply.blade group
- balances.blade group
- profile.blade group
- requests.blade group
- 403.blade group
- 404.blade group
- 419.blade group
- 429.blade group
- 500.blade group
- errors/layout.blade group
- settings/layout.blade group

## God Nodes (most connected - your core abstractions)
1. `User` - 96 edges
2. `LeaveRequest` - 56 edges
3. `LeaveType` - 39 edges
4. `AdminPagesTest` - 31 edges
5. `TestCase` - 31 edges
6. `Department` - 27 edges
7. `AuditLog` - 23 edges
8. `LeaveBalance` - 23 edges
9. `Role` - 23 edges
10. `ApprovalFlowTest` - 19 edges

## Surprising Connections (you probably didn't know these)
- `AdminPagesTest` --references--> `Department`  [EXTRACTED]
  tests/Feature/AdminPagesTest.php → app/Models/Department.php
- `ApprovalFlowTest` --references--> `Department`  [EXTRACTED]
  tests/Feature/ApprovalFlowTest.php → app/Models/Department.php
- `OrganizationManagementTest` --references--> `Department`  [EXTRACTED]
  tests/Feature/OrganizationManagementTest.php → app/Models/Department.php
- `{closure#1}()` --references--> `LeaveRequest`  [EXTRACTED]
  tests/Feature/AdminOverviewRingsTest.php → app/Models/LeaveRequest.php
- `AdminLeaveRequestTest` --references--> `LeaveType`  [EXTRACTED]
  tests/Feature/AdminLeaveRequestTest.php → app/Models/LeaveType.php

## Import Cycles
- None detected.

## Communities (97 total, 78 thin omitted)

### Community 0 - "User group"
Cohesion: 0.05
Nodes (11): ApprovalController, {closure#1}(), {closure#1}(), LeaveRequest, User, AdminPolicy, LeaveRequestPolicy, AdminGuard (+3 more)

### Community 1 - "AuditLog group"
Cohesion: 0.06
Nodes (4): AuditLog, LeaveBalance, RequestHistory, ActivityPresentation

### Community 2 - "composer.json group"
Cohesion: 0.04
Nodes (48): pestphp/pest-plugin, php-http/discovery, autoload, autoload-dev, psr-4, psr-4, config, allow-plugins (+40 more)

### Community 3 - "Blueprint group"
Cohesion: 0.08
Nodes (25): {closure#1}(), {closure#2}(), {closure#3}(), {closure#4}(), {closure#5}(), {closure#6}(), {closure#7}(), {closure#8}() (+17 more)

### Community 4 - "Request group"
Cohesion: 0.12
Nodes (9): EnforceRememberIdleTimeout, EnsureAdminOverviewAccess, EnsurePasswordIsChanged, EnsureUserHasRole, PreventBackHistory, {closure#1}(), {closure#2}(), {closure#3}() (+1 more)

### Community 5 - "UserController group"
Cohesion: 0.09
Nodes (5): {closure#4}(), ValidatesUserAssignment, StoreUserRequest, UpdateUserRequest, TemporaryPassword

### Community 6 - "FormRequest group"
Cohesion: 0.10
Nodes (5): ApproveLeaveRequest, RejectLeaveRequest, ResetTemporaryPasswordRequest, StoreLeaveTypeRequest, UpdateProfileRequest

### Community 9 - "package.json group"
Cohesion: 0.12
Nodes (21): devDependencies, concurrently@^10.0.3, laravel-vite-plugin@^3.1, tailwindcss@^4.0.0, @tailwindcss/vite@^4.0.0, vite@^8.0.0, optionalDependencies, @laravel/multiplex@^0.4.1 (+13 more)

### Community 11 - "TestCase group"
Cohesion: 0.13
Nodes (4): AppThemeTest, ExampleTest, LoginThemeTest, TestCase

### Community 12 - "AdminPagesTest group"
Cohesion: 0.15
Nodes (5): DatabaseSeeder, DepartmentSeeder, LeaveTypeSeeder, PositionSeeder, RoleSeeder

### Community 13 - "Controller group"
Cohesion: 0.16
Nodes (6): AuditLogController, DashboardController, LeaveBalanceController, Controller, AuditLogFilterRequest, LeaveBalanceFilterRequest

### Community 14 - "SettingsController group"
Cohesion: 0.13
Nodes (4): SettingsPositionsRequest, StoreDepartmentRequest, StorePositionRequest, Position

### Community 15 - "CreateAdmin group"
Cohesion: 0.13
Nodes (3): CreateAdmin, ChangePasswordRequest, ResetPasswordRequest

### Community 16 - "ReportController group"
Cohesion: 0.18
Nodes (3): ReportController, ReportFilterRequest, ReportQuery

### Community 20 - "AppServiceProvider group"
Cohesion: 0.16
Nodes (3): AppServiceProvider, {closure#1}(), Report

### Community 29 - "DashboardController group"
Cohesion: 0.24
Nodes (4): {closure#2}(), {closure#3}(), {closure#4}(), PendingApprovalCount

### Community 30 - "date-utils.js group"
Cohesion: 0.38
Nodes (7): addLocalDays(), countLocalWorkingDays(), endAfterLocal(), formatLocalDate(), isWorkingDay(), pad(), parseLocalDate()

### Community 40 - "app.blade group"
Cohesion: 0.40
Nodes (4): partials.sidebar, partials.splash, partials.topbar, partials.icons

### Community 44 - "overview.blade group"
Cohesion: 0.50
Nodes (3): layouts.app, partials.admin-tabs, partials.balance-ring

## Knowledge Gaps
- **49 isolated node(s):** `php`, `layouts.app`, `partials.admin-tabs`, `layouts.app`, `partials.admin-tabs` (+44 more)
  These have ≤1 connection - possible missing edges. (Counts symbols only; 377 node(s) total have ≤1 connection when file, concept and rationale nodes are included.)
- **78 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **Why does `User` connect `User group` to `AuditLog group`, `Request group`, `UserController group`, `FormRequest group`, `AdminPagesTest group`, `LeaveController group`, `LeaveType group`, `AdminPagesTest group`, `Controller group`, `CreateAdmin group`, `ReportController group`, `AdminNavigationTest group`, `OrganizationManagementTest group`, `AppServiceProvider group`, `SecurityAuthenticationTest group`, `View group`, `Str group`, `EmployeeLeaveSystemTest group`, `AdminOverviewRingsTest group`, `DashboardActivityTest group`, `DashboardController group`, `AdminLeaveRequestTest group`, `DashboardActivityTest group`, `UserController group`?**
  _High betweenness centrality (0.196) - this node is a cross-community bridge._
- **What connects `php`, `layouts.app`, `partials.admin-tabs` to the rest of the system?**
  _49 weakly-connected nodes found - possible missing edges._
- **Should `User group` be split into smaller, more focused modules?**
  _Cohesion score 0.053703703703703705 - nodes in this community are weakly interconnected._
- **Why does `LeaveType` connect `LeaveType group` to `User group`, `AuditLog group`, `FormRequest group`, `AdminPagesTest group`, `LeaveController group`, `AdminPagesTest group`, `Controller group`, `SettingsController group`, `ReportController group`, `AdminNavigationTest group`, `OrganizationManagementTest group`, `Rule group`, `AppServiceProvider group`, `EmployeeLeaveSystemTest group`, `RedirectResponse group`, `AdminOverviewRingsTest group`, `DashboardController group`, `AdminLeaveRequestTest group`, `Department group`, `DashboardActivityTest group`, `UpdateLeaveTypeStatusRequest group`?**
  _High betweenness centrality (0.052) - this node is a cross-community bridge._
- **Should `AuditLog group` be split into smaller, more focused modules?**
  _Cohesion score 0.06313497822931785 - nodes in this community are weakly interconnected._
- **Why does `LeaveRequest` connect `User group` to `AuditLog group`, `DashboardActivityTest group`, `FormRequest group`, `LeaveController group`, `LeaveType group`, `AdminPagesTest group`, `ReportController group`, `AdminNavigationTest group`, `Str group`, `AdminOverviewRingsTest group`, `DashboardController group`, `AdminLeaveRequestTest group`?**
  _High betweenness centrality (0.046) - this node is a cross-community bridge._
- **Should `composer.json group` be split into smaller, more focused modules?**
  _Cohesion score 0.04081632653061224 - nodes in this community are weakly interconnected._