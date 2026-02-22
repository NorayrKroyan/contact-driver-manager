# Contact & Driver Manager (Laravel + Vue 3)

This project provides a CRUD UI and API for managing **Contacts** and their linked **Driver** records.

Key requirements implemented:
- Every Contact must have a `driver` row (even if not a driver).
- A Contact becomes a “full driver” only when `driver.is_driver = 1`.
- If `is_driver != 1`, driver-only fields must be `NULL`.
- Phone is **required** and must match US formatted rules.
- Email is **NOT required**, but if filled must be a valid email.
- If `is_driver = 1`, `driver.id_carrier` is **required**.
- On CREATE only:
    - default `driver.idprojects = 10` if missing
    - default `driver.mobile_app_pin = last 4 digits of phone` if missing
- Soft delete uses `is_deleted = 1` on both contact + driver.

---

## Tech Stack
- Backend: Laravel (API routes)
- DB: MySQL (via Laravel DB facade)
- Frontend: Vue 3 + Tailwind (no custom CSS)
- Select component: `vue-select`

---

## Backend (Laravel)

### API Routes
Defined under `/api`:

| Method | Endpoint | Description |
|-------:|----------|-------------|
| GET    | `/api/contact-driver` | List contacts (server pagination, q, sort, dir) |
| GET    | `/api/contact-driver/lookups` | Lookup data for form selects |
| GET    | `/api/contact-driver/{contactId}` | Get a single contact+driver |
| POST   | `/api/contact-driver` | Create contact+driver |
| PUT    | `/api/contact-driver/{contactId}` | Update contact+driver |
| DELETE | `/api/contact-driver/{contactId}` | Soft delete contact+driver |

Routes file example:
```php
Route::get('/contact-driver', [ContactDriverController::class, 'index']);
Route::get('/contact-driver/lookups', [ContactDriverController::class, 'lookups']);
Route::get('/contact-driver/{contactId}', [ContactDriverController::class, 'show']);
Route::post('/contact-driver', [ContactDriverController::class, 'store']);
Route::put('/contact-driver/{contactId}', [ContactDriverController::class, 'update']);
Route::delete('/contact-driver/{contactId}', [ContactDriverController::class, 'destroy']);
```

---

## List API (Search / Sort / Pagination)

### Query Params
- `limit` (default 25, max 200)
- `page` (default 1)
- `q` (search across columns)
- `sort` (name|phone|state|carrier|driver)
- `dir` (asc|desc)

Example:
```
GET /api/contact-driver?limit=25&page=1&q=TX&sort=name&dir=asc
```

### Search behavior
Search is implemented in `ContactDriverService::baseListQuery($q)` against:
- full name concat
- first_name
- last_name
- phone_number
- carrier_name
- state
- contact.email
- contact.address
  Additionally, if `q` looks like a 2-letter state code, it adds an exact match on upper(state).

### Sort behavior
Sort keys map to DB columns:
- name → `v.last_name` then `v.first_name`
- phone → `v.phone_number`
- state → `v.state`
- carrier → `v.carrier_name`
- driver → `v.is_driver`

---

## Lookups API

Endpoint:
```
GET /api/contact-driver/lookups
```

Returns:
- carriers (from `carrier`, not deleted)
- projects (from `projects`)
- trucks (vehicle_type = 2)
- trailers (vehicle_type = 1)
- states (from `us_states`)

---

## Validation Rules (Backend)

### Phone (`contact.phone_number`)
- required
- must match:
    - `###-###-####` OR `1-###-###-####`
- max length 14
- digit count must be:
    - 10 digits OR 11 digits starting with `1`

Controller enforces format via regex + digit count via `enforcePhoneDigitCount()`.
Service also validates in `assertPhoneValid()`.

### Email (`contact.email`)
- NOT required
- if provided (non-empty), must be a valid email
- max 190 chars

Controller:
```php
'contact.email' => ['nullable','email','max:190'],
```
Service:
- `assertEmailValid()` skips empty and validates with `FILTER_VALIDATE_EMAIL`.

### Driver carrier (`driver.id_carrier`)
- required only when `driver.is_driver = 1`

Controller:
```php
'driver.id_carrier' => [$isDriver ? 'required' : 'nullable', 'integer', 'min:1'],
```

Service:
- if `is_driver === 1` and `id_carrier` null => throws 422 with field error.

---

## Create/Update Behavior

### Create (`POST /api/contact-driver`)
- Inserts into `contact`
- Normalizes driver payload with `normalizeDriverForSave($d, $phone, true)`
- Inserts into `driver`
- CREATE-only defaults applied when `is_driver=1` and new driver:
    - `idprojects = 10` if missing
    - `mobile_app_pin = last4(phone)` if missing
- Returns full row via `get($contactId)`.

### Update (`PUT /api/contact-driver/{id}`)
- Updates `contact`
- Ensures `driver` row exists (creates empty if missing)
- Normalizes driver payload with `normalizeDriverForSave($d, $phone, false)`
- Defaults (project=10, pin=last4) do NOT apply on update
- If `is_driver != 1` then all driver-only fields are set to `NULL`
- Updates `driver`

### Delete (`DELETE /api/contact-driver/{id}`)
Soft delete:
- `contact.is_deleted = 1`
- `driver.is_deleted = 1`

---

## Frontend (Vue 3)

### Main page
Features:
- Single global search input (`filters.q`)
- Server pagination (page, pageSize)
- Sortable columns (name/phone/state/carrier/driver)
- Row click opens edit modal
- New Contact opens create modal
- Driver pill: Yes/No

### Modal (`ContactDriverModal`)
- Contact section
- Driver toggle checkbox
- When driver enabled:
    - Carrier select (required)
    - Project select
    - Shift radios
    - Spanish radios
    - Truck/Trailer selects
    - GPS device input
    - Mobile app PIN input
    - TCS fuel card section

### Client-side validation (before submit)
`validateContactDriver({ form, driverToggle })` should enforce:
- phone required + formatted
- email optional but if filled must be valid
- if driverToggle true → carrier required

### Server-side validation errors
- API returns 422 with Laravel error structure
- UI uses `flattenLaravelErrors(err)` + `applyServerErrors(e)` to display field-level errors in the modal.

---

## Required Database Objects (from code)

### Tables (confirmed by code usage)
- `contact`
    - fields used: `id_contact`, `first_name`, `last_name`, `address`, `state`, `phone_number`, `email`, `is_deleted`, `date_created`, `date_modified`
- `driver`
    - fields used: `id_driver`, `id_contact`, `is_driver`, `is_deleted`, `date_created`, `date_modified`,
      `id_vehicle`, `id_trailer`, `id_carrier`, `id_device`, `status`, `driver_shift`, `spanish_language`, `idprojects`,
      `mobile_app_pin`, `driver_profile_url`,
      `tcs_fuel_card_number`, `tcs_fuel_card_pin`, `tcs_fuel_card_limit`, `tcs_fuel_card_last_updated`
- `carrier`
    - fields used: `id_carrier`, `carrier_name`, `is_deleted`
- `projects`
    - fields used: `idprojects`, `projectname`
- `vehicle`
    - fields used: `id_vehicle`, `id_vehicle_type`, `vehicle_name`, `vehicle_number`, `is_deleted`
- `us_states`
    - fields used: `id`, `state_name`, `state_code`

### View (confirmed by code usage)
- `contactdriverview as v`
    - fields used: `id_contact`, `first_name`, `last_name`, `phone_number`, `state`, `is_driver`, `carrier_name`

> Note: listing joins `contactdriverview v` + `contact c` and also searches `c.email` and `c.address`.

---
