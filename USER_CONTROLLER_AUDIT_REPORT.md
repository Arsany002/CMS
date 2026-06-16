# User Controller Architecture Audit Report

**Date:** 2026-06-16  
**Scope:** `app/Http/Controllers/SuperAdmin/UserController.php` and related user service/repository/request classes.

## Files Inspected

- `app/Http/Controllers/SuperAdmin/UserController.php`
- `app/Services/UserService.php`
- `app/Repositories/UserRepository.php`
- `app/Http/Requests/User/StoreUserRequest.php`
- `app/Http/Requests/User/updateUserRequest.php`
- `app/Http/Requests/User/UpdateUserRoleRequest.php`
- `app/Models/User.php`
- `app/Enums/UserRole.php`
- `app/Exceptions/SelfDemotionException.php`
- `routes/api.php`

## Previous Claude Changes Reviewed

Claude had already started the refactor:

- Added `UserService`.
- Added `UpdateUserRoleRequest`.
- Moved password hashing for create/update into `UserService`.
- Moved self-demotion protection into `UserService::updateRole()`.
- Removed inline `$request->validate()` from `UserController::updateRole()`.

These changes were correct but incomplete.

## Violations Found Before This Pass

No remaining `User::where()`, `User::find()`, `User::create()`, `User::update()`, `User::delete()`, `DB::`, or `query()` calls existed directly in `SuperAdmin\UserController` after Claude's partial changes.

Remaining separation-of-concerns violations:

- `UserController` still imported `App\Models\User`.
- `UserController` still imported and called `UserRepository` directly.
- `index()` called `UserRepository::getAllUsers()` directly.
- `show()` called `UserRepository::getUserById()` directly.
- `toggle()` called `UserRepository::toggleUserStatus()` directly.
- `show()`, `update()`, `toggle()`, and `updateRole()` used `User $user` route model binding, which lets Laravel fetch the model before the service/repository layer.

## Refactors Performed

### Controller

`UserController` now:

- Injects only `UserService`.
- Calls service methods for all user workflows.
- Accepts route user IDs as `string $user` instead of `User $user`.
- Contains no direct repository calls.
- Contains no direct model imports.
- Contains no inline validation.
- Contains no password hashing or self-demotion business logic.

### Service

Added/updated `UserService` methods:

- `paginate(?string $clinicId = null, int $perPage = 15)`
- `findById(string $id, ?string $clinicId = null)`
- `create(array $data)`
- `update(string $id, array $data, ?string $clinicId = null)`
- `toggleStatus(string $id, ?string $clinicId = null)`
- `updateRole(string $currentUserId, string $targetUserId, string $role)`

Business logic now in `UserService`:

- Password hashing on create/update.
- Default `is_active` assignment on create.
- Empty password removal on update.
- Self-demotion protection for super admins.
- Active/inactive status toggle orchestration.

### Repository

`UserRepository` remains the only layer using Eloquent for user persistence/fetching. Existing repository methods are used by the service:

- `getAllUsers()`
- `getUserById()`
- `createUser()`
- `updateUser()`
- `toggleUserStatus()`
- `updateUserRole()`
- `getDoctorsForClinic()`

### Validation

Validation remains in FormRequests:

- `StoreUserRequest`
- `updateUserRequest`
- `UpdateUserRoleRequest`

The controller uses `$request->validated()` or `$request->validated('role')` only.

## Direct Model Usage Removed From UserController

- Removed `use App\Models\User`.
- Removed `User $user` route model binding from `show()`.
- Removed `User $user` route model binding from `update()`.
- Removed `User $user` route model binding from `toggle()`.
- Removed `User $user` route model binding from `updateRole()`.

## Direct Repository Usage Removed From UserController

- Removed `use App\Repositories\UserRepository`.
- Removed `UserRepository` constructor injection.
- Removed direct `getAllUsers()` call from `index()`.
- Removed direct `getUserById()` call from `show()`.
- Removed direct `toggleUserStatus()` call from `toggle()`.

## Checks Run

- `git status --short`
- `git diff`
- `git diff --stat`
- `php -l app/Http/Controllers/SuperAdmin/UserController.php`
- `php -l app/Services/UserService.php`
- `php -l app/Repositories/UserRepository.php`
- `php -l app/Http/Requests/User/UpdateUserRoleRequest.php`
- `php artisan test`
- `./vendor/bin/phpunit`
- `npm run build`

## Results

- PHP syntax checks passed.
- `php artisan test` is not defined in this Laravel install.
- `./vendor/bin/phpunit` passed with elevated DB access: 80 tests, 271 assertions.
- `npm run build` passed.

## Remaining Risks / Manual Review

- `updateUserRequest` class name is lowercase; it works as currently referenced, but should eventually be renamed to `UpdateUserRequest` for PSR-4 style consistency.
- Other controllers still have separation-of-concerns issues documented in `ARCHITECTURE_AUDIT_REPORT.md`; this pass intentionally focused only on the super-admin user controller.
- Route URLs were preserved. The route parameter is still `{user}`, but the controller now receives it as an ID string to avoid implicit model fetching.
