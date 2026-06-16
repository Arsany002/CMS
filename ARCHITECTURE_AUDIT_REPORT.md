# Architecture Audit Report — CMS Backend

**Date:** 2026-06-16  
**Auditor:** Claude Code (claude-sonnet-4-6)  
**Scope:** `CMS-BACK/app/` — all Controllers, Services, Repositories, Models, FormRequests, Resources, Exceptions  
**Test verification:** 96/96 Playwright E2E tests passing before and after all fixes

---

## 1. Target Architecture

```
HTTP Request
    │
    ▼
FormRequest          ← validate & authorize (no business logic)
    │
    ▼
Controller           ← receive validated input, call service, return response
    │                  MUST NOT query Eloquent directly
    ▼
Service              ← business rules, orchestration, domain exceptions
    │                  MUST NOT return HTTP responses
    ▼
Repository           ← all Eloquent queries, no business logic
    │
    ▼
Model                ← schema, casts, relationships, HasUuids
```

**Additional conventions in this project:**
- All JSON responses via the `ApiResponse` trait: `{ success, message, data?, meta? }`
- All exceptions self-rendering via a `render()` method on the exception class
- All models use UUIDs (`HasUuids`), never auto-increment IDs
- Auth is Laravel Passport Personal Access Tokens

---

## 2. Files Inspected

| Layer | Files |
|---|---|
| Controllers | `Auth/AuthController`, `SuperAdmin/ClinicController`, `SuperAdmin/DashboardController`, `SuperAdmin/UserController`, `Doctor/AppointmentController`, `Doctor/PatientController`, `Doctor/PrescriptionController`, `Doctor/ScheduleController`, `Assistant/AppointmentController`, `Assistant/DoctorController`, `Assistant/PatientController` |
| Services | `AuthService`, `AppointmentService`, `ClinicService`, `DashboardService`, `PrescriptionService`, `UserService` |
| Repositories | `AppointmentRepository`, `ClinicRepository`, `PatientRepository`, `PrescriptionRepository`, `ScheduleRepository`, `UserRepository` |
| FormRequests | All 15 request classes across `Auth/`, `Appointment/`, `Clinic/`, `Patient/`, `Prescription/`, `Schedule/`, `User/` |
| Resources | `AppointmentResource`, `ClinicResource`, `PatientResource`, `PrescriptionResource`, `PrescriptionItemResource`, `ScheduleResource`, `UserResource` |
| Models | `Appointment`, `Clinic`, `DoctorSchedule`, `Patient`, `Prescription`, `PrescriptionItem`, `User` |
| Exceptions | `AccountDeactivatedException`, `AppointmentConflictException`, `ClinicScopeViolationException`, `InvalidAppointmentStateException`, `SelfDemotionException` |
| Infrastructure | `ApiResponse` trait, `ClinicScopeMiddleware`, `RoleMiddleware` |

---

## 3. What the Codebase Does Well

### 3.1 Exception Design
All five custom exceptions carry a `render()` method that maps directly to an HTTP status code and a standard `ApiResponse` JSON body. Controllers throw domain exceptions; they never build error responses themselves.

```php
// Example: ClinicScopeViolationException
public function render(): JsonResponse
{
    return response()->json([
        'success' => false,
        'message' => $this->message ?: 'Clinic scope violation.',
    ], 403);
}
```

### 3.2 Uniform API Response Shape
The `ApiResponse` trait is used consistently across all controllers. Every successful response follows `{ success: true, message: string, data?: ..., meta?: ... }`. No controller returns a raw `response()->json([...])`.

### 3.3 FormRequest Coverage
Every mutating endpoint has a dedicated `FormRequest` subclass with `authorize(): bool` and `rules(): array`. Validation never leaks into controllers (after FIX-3) or services. Read endpoints use the framework's `Request` injection.

### 3.4 AuthService — Textbook Example
`AuthService` is the cleanest service in the codebase:
- `login()`: credential check + active-status check → delegates token creation to `UserRepository::createApiToken()`
- `register()`: wraps user creation in `DB::transaction()`, hashes password, issues token — all domain work, no HTTP coupling
- `logout()`: delegates to `UserRepository::revokeCurrentToken()`

### 3.5 PrescriptionService — Business Rule Enforcement
`PrescriptionService::create()` enforces two business rules before delegating to the repository:
- **BR-04:** Only the appointment's assigned doctor can write a prescription (throws `ClinicScopeViolationException`)
- **BR-06:** Cannot write a prescription for a cancelled appointment (throws `InvalidAppointmentStateException`)

This is exactly the right layer for these checks.

### 3.6 AppointmentService — Cache Locking
`AppointmentService::book()` uses `Cache::lock($lockKey, 10)->block(5, ...)` to prevent double-booking of a slot under concurrent requests. This is a correct, non-trivial concurrency pattern placed squarely in the service layer.

### 3.7 ClinicScopeMiddleware — Cross-Clinic Protection
`ClinicScopeMiddleware` force-merges `clinic_id` from the authenticated user's record into the incoming `Request` for all `doctor` and `assistant` routes. This prevents any client-side manipulation of the `clinic_id` parameter. It is a defence-in-depth measure that works alongside service-layer checks.

### 3.8 DashboardService — Aggregate Coordination
`DashboardService` injects five repositories and composes a stats array. No Eloquent code lives in `DashboardController`; all query coordination is in the service.

### 3.9 Model Layer Hygiene
All models use `HasUuids`. Relationships are defined cleanly (e.g. `Appointment::patient()`, `Prescription::items()`). Models have no business logic — they are schema + cast + relationship declarations only.

---

## 4. Violations Found

Eleven violations were identified. Each is categorised by severity and the correct layer for the misplaced logic.

### Severity definitions
| Severity | Definition |
|---|---|
| HIGH | Direct Eloquent query in a controller, or business rule enforced in the wrong layer |
| MEDIUM | Minor layer bleed: ownership check in controller, redundant queries, missing FormRequest |
| LOW | Code smell or efficiency issue that does not violate the architecture contract |

---

### 4.1 HIGH — FIX-1 ✅ (Implemented)

**File:** `app/Http/Controllers/Assistant/DoctorController.php` (before fix)  
**Violation:** The controller contained a raw Eloquent query to list clinic doctors:

```php
// BEFORE (controller querying Eloquent directly)
$doctors = User::where('clinic_id', $request->user()->clinic_id)
    ->where('role', UserRole::DOCTOR)
    ->where('is_active', true)
    ->select(['id', 'name', 'email', 'role'])
    ->orderBy('name')
    ->get();
```

**Fix:** Added `UserRepository::getDoctorsForClinic(string $clinicId): Collection` and rewired the controller to call it.

```php
// AFTER (controller delegates to repository)
$doctors = $this->userRepo->getDoctorsForClinic($request->user()->clinic_id);
```

---

### 4.2 HIGH — FIX-2 ✅ (Implemented)

**File:** `app/Http/Controllers/SuperAdmin/UserController.php` (before fix)  
**Violation:** Password hashing with `bcrypt()` and the `is_active` default were performed inline in the controller's `store()` and `update()` actions. This is business logic that belongs in the service layer.

```php
// BEFORE (business logic in controller)
$data['password'] = bcrypt($request->validated('password'));
$data['is_active'] = $data['is_active'] ?? true;
$user = $this->repo->createUser($data);
```

**Fix:** Created `app/Services/UserService.php` with `create()`, `update()`, and `updateRole()` methods. The controller now delegates to the service.

```php
// AFTER (controller delegates to service)
$user = $this->service->create($request->validated());
```

---

### 4.3 MEDIUM — FIX-3 ✅ (Implemented)

**File:** `app/Http/Controllers/SuperAdmin/UserController.php::updateRole()` (before fix)  
**Violation:** `$request->validate([...])` was called inline in the controller action instead of using a dedicated `FormRequest`. This is the only instance in the codebase where validation was not extracted into a FormRequest.

```php
// BEFORE (inline validation in controller)
$validated = $request->validate([
    'role' => ['required', 'string', 'in:super_admin,doctor,assistant'],
]);
```

**Fix:** Created `app/Http/Requests/User/UpdateUserRoleRequest.php`.

---

### 4.4 HIGH — FIX-4 ✅ (Implemented)

**File:** `app/Http/Controllers/Assistant/AppointmentController.php::store()` (before fix)  
**Violation:** Business Rule 07 (a patient must belong to the same clinic as the booking assistant) was enforced directly in the controller, including injecting `PatientRepository` and `ClinicScopeViolationException` into the controller class.

```php
// BEFORE (business rule in controller)
$patient = $this->patientRepo->getPatientById($request->patient_id);
if ($patient->clinic_id !== $request->user()->clinic_id) {
    throw new ClinicScopeViolationException('Patient does not belong to this clinic.');
}
```

**Fix:** Moved BR-07 into `AppointmentService::book()`. The controller now simply merges `clinic_id`/`booked_by` and calls `$this->service->book($data)`.

---

### 4.5 LOW — FIX-5 ✅ (Implemented)

**File:** `app/Services/AppointmentService.php` (before fix)  
**Violation:** `reschedule()` issued 3 separate DB round-trips to read one appointment (called `getStatus()`, then `find()` twice). `cancel()` called `find()` twice for the same record.

**Fix:** Consolidated to a single `find()` call at the top of each method. The loaded model is reused for all subsequent attribute reads.

---

### 4.6 MEDIUM — Recommended, Not Implemented

**File:** `app/Http/Controllers/Doctor/ScheduleController.php` — lines 48, 57, 69  
**Violation:** Doctor ownership checks on schedule records are performed with `abort_if()` in three controller actions (`show`, `update`, `destroy`). This is authorization / business-rule logic that belongs in a `ScheduleService`.

```php
// In controller — should be in a ScheduleService
abort_if($schedule->doctor_id !== $request->user()->id, 403, 'You do not own this schedule.');
```

**Recommended fix:** Create `app/Services/ScheduleService.php` with `get()`, `update()`, and `delete()` methods that throw a domain exception (e.g. `ClinicScopeViolationException`) when the doctor doesn't own the record. The controller becomes a thin pass-through.

**Why not implemented now:** The current implementation is functionally correct and safe. The refactor requires a new service class and carries no risk of breaking API contracts, but its benefit is consistency rather than correctness. Should be the first item in the next refactor pass.

---

### 4.7 MEDIUM — Recommended, Not Implemented

**File:** `app/Http/Controllers/Doctor/PrescriptionController.php` — lines 56–58, 67–69  
**Violation:** Doctor ownership checks on prescriptions are performed in `show()` and `update()`. The `PrescriptionService` already exists and handles the BR-04 check in `create()` — `show()` and `update()` ownership should follow the same pattern.

```php
// In controller — should be in PrescriptionService
if ($prescription->doctor_id !== $request->user()->id) {
    throw new ClinicScopeViolationException('You do not have access to this prescription.');
}
```

**Recommended fix:** Add `PrescriptionService::findForDoctor(Prescription $p, string $doctorId)` and `updateForDoctor()` that perform the ownership check and throw if violated. Move the check out of both controller actions.

---

### 4.8 MEDIUM — Recommended, Not Implemented

**File:** `app/Http/Controllers/SuperAdmin/ClinicController.php::store()` — line 38  
**Violation:** `store()` calls `$this->repo->createClinic()` directly, bypassing `ClinicService`. The `toggle()` action in the same controller correctly goes through `ClinicService`. This inconsistency means `store` is the only write action in the controller that skips the service layer.

**Recommended fix:** Add `ClinicService::create(array $data): Clinic` that calls `$this->repo->createClinic($data)` and route `ClinicController::store()` through it.

---

### 4.9 LOW — Recommended, Not Implemented

**File:** `app/Http/Controllers/SuperAdmin/ClinicController.php::store()` — line 41  
**Violation:** The resource is unnecessarily serialised to an array and then re-deserialised:

```php
$data = (new ClinicResource($clinic))->toArray(request());
return $this->success(data: $data, ...);
```

All other controller actions in the same file pass the resource object directly (`new ClinicResource(...)` or `ClinicResource::collection(...)`). The `ApiResponse` trait wraps it correctly in both cases. The `toArray()` call is redundant and strips the resource's lazy-evaluation benefits.

**Recommended fix:**
```php
return $this->success(data: new ClinicResource($clinic), message: 'Clinic created', status: 201);
```

---

### 4.10 MEDIUM — Recommended, Not Implemented

**Files:** `app/Http/Controllers/Assistant/PatientController.php` (lines 44–46, 53–55), `app/Http/Controllers/Doctor/PatientController.php` (lines 31–33)  
**Violation:** Clinic-scope ownership checks on `Patient` records are performed inside controllers for `show` and `update` actions in both the assistant and doctor namespaces.

```php
// In controllers — should be in a service
if ($patient->clinic_id !== $request->clinic_id) {
    throw new ClinicScopeViolationException();
}
```

Note: `ClinicScopeMiddleware` already injects `clinic_id` on these routes, so the check is partially redundant (the repository's `getPatientById($id, $clinicId)` also scopes by clinic). However the explicit throw in the controller means the ownership check is layered in two different places with no single source of truth.

**Recommended fix:** Create a `PatientService` with `findForClinic()` and `updateForClinic()` methods that are the canonical enforcement point. Remove the checks from both controllers and let the repository's scoped queries be the backstop.

---

### 4.11 LOW — Orphaned Method

**File:** `app/Repositories/AppointmentRepository.php` — `getStatus()` method (line 108)  
**Context:** This method was added to support the original (pre-FIX-5) implementation of `AppointmentService::reschedule()` which called `getStatus()` as its first separate DB hit. After FIX-5 consolidated that into a single `find()`, `getStatus()` is no longer called anywhere in the codebase.

**Recommended fix:** Remove `AppointmentRepository::getStatus()`. It is dead code. Verify with `grep -r "getStatus" app/` before deleting.

---

## 5. Refactors Implemented — Summary

| ID | File(s) Changed | Description | Severity |
|---|---|---|---|
| FIX-1 | `UserRepository.php`, `Assistant/DoctorController.php` | Moved Eloquent doctor query into `UserRepository::getDoctorsForClinic()` | HIGH |
| FIX-2 | `UserService.php` (created), `SuperAdmin/UserController.php` | Extracted password hashing and `is_active` defaulting into `UserService` | HIGH |
| FIX-3 | `UpdateUserRoleRequest.php` (created), `SuperAdmin/UserController.php` | Replaced inline `$request->validate()` with a `FormRequest` class | MEDIUM |
| FIX-4 | `AppointmentService.php`, `Assistant/AppointmentController.php` | Moved BR-07 patient-clinic ownership check into `AppointmentService::book()` | HIGH |
| FIX-5 | `AppointmentService.php` | Consolidated 3→1 and 2→1 redundant `find()` calls in `reschedule()` and `cancel()` | LOW |

All 5 fixes were verified by running the full Playwright E2E suite: **96/96 tests passing**.

---

## 6. Refactors Recommended — Priority Order

| Priority | File(s) | Description | Effort |
|---|---|---|---|
| 1 | `Doctor/ScheduleController.php` | Create `ScheduleService`; move 3× `abort_if` ownership checks | Small |
| 2 | `Doctor/PrescriptionController.php` | Add `findForDoctor()`/`updateForDoctor()` to `PrescriptionService`; remove controller checks | Small |
| 3 | `SuperAdmin/ClinicController.php::store` | Route through `ClinicService::create()` for consistency | Trivial |
| 4 | `SuperAdmin/ClinicController.php::store` | Replace `->toArray(request())` with direct resource object | Trivial |
| 5 | `Assistant/PatientController.php`, `Doctor/PatientController.php` | Create `PatientService` as canonical ownership enforcement point | Medium |
| 6 | `AppointmentRepository.php::getStatus` | Delete orphaned dead-code method | Trivial |

---

## 7. Layer Rules — Quick Reference

Use this as a checklist when adding new features:

| Layer | Allowed | Not Allowed |
|---|---|---|
| **Controller** | Inject FormRequest & Service/Repository; call service; return `ApiResponse` | Eloquent queries, `Hash::make()`, `DB::transaction()`, business rule conditionals |
| **FormRequest** | `authorize()`, `rules()`, `messages()` | Business logic, DB queries |
| **Service** | Business rules, domain exceptions, `DB::transaction()`, `Cache::lock()`, cross-repo orchestration | `return response()->json(...)`, `abort()`, `$request->...` |
| **Repository** | Eloquent queries only — `where()`, `with()`, `paginate()`, `create()`, `update()` | Domain exceptions based on business meaning, HTTP responses |
| **Model** | `$fillable`, `$casts`, relationships, `HasUuids`, scopes | Business logic, HTTP responses |
| **Exception** | `render(): JsonResponse` returning a status code + ApiResponse shape | Business rules, DB calls |

---

## 8. Remaining Risks

1. **`ScheduleController` ownership checks** — until a `ScheduleService` is created, three controller actions each contain a raw `abort_if`. Any change to schedule ownership logic must be applied in three places.

2. **`PrescriptionController` ownership inconsistency** — `create()` uses the service (correct); `show()` and `update()` bypass it (incorrect). A future developer may assume all ownership checks live in the service and miss the controller-level checks.

3. **PatientController dual-enforcement** — Both the controller's explicit `ClinicScopeViolationException` throw and the repository's scoped `getPatientById($id, $clinicId)` enforce clinic scope independently. If either is relaxed in isolation the enforcement silently becomes weaker without a test failure, because the other remains.

4. **`AppointmentRepository::getStatus()` dead code** — if another developer adds a new caller to this method in the future they will unknowingly reintroduce the redundant-query pattern. The method should be deleted.
