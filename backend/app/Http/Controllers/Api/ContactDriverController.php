<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\ContactDriver\ContactDriverService;
use Illuminate\Validation\ValidationException;

class ContactDriverController extends Controller
{
    public function index(Request $request, ContactDriverService $svc)
    {
        $limit = max(1, min((int)$request->query('limit', 25), 200));
        $page  = max(1, (int)$request->query('page', 1));

        $res = $svc->listPaged(
            $limit,
            $page,
            (string)$request->query('q', ''),
            (string)$request->query('sort', 'name'),
            (string)$request->query('dir', 'asc')
        );

        return response()->json([
            'ok' => true,
            'rows' => $res['rows'] ?? [],
            'total' => (int)($res['total'] ?? 0),
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    public function show(int $contactId, ContactDriverService $svc)
    {
        return response()->json([
            'ok' => true,
            'row' => $svc->get($contactId),
        ]);
    }

    private function validatePayload(Request $request): array
    {
        $data = $request->all();
        $isDriver = (int)($data['driver']['is_driver'] ?? 0) === 1;

        $phoneRegex = '/^(\d{3}-\d{3}-\d{4}|\d-\d{3}-\d{3}-\d{4})$/';

        return $request->validate([
            'contact.first_name' => ['nullable','string','max:100'],
            'contact.last_name'  => ['nullable','string','max:100'],
            'contact.address'    => ['nullable','string','max:255'],
            'contact.state'      => ['nullable','string','max:2'],

            'contact.phone_number' => ['required','string','max:14', "regex:$phoneRegex"],

            // ✅ Email REQUIRED
            'contact.email'      => ['nullable','email','max:190'],

            'driver.is_driver'   => ['required','boolean'],

            // ✅ required only if is_driver=1
            'driver.id_carrier'  => [$isDriver ? 'required' : 'nullable', 'integer', 'min:1'],

            'driver.idprojects'  => ['nullable','integer','min:1'],
            'driver.mobile_app_pin' => ['nullable','regex:/^\d{4}$/'],

            'driver.spanish_language' => ['nullable','integer'],
            'driver.driver_shift' => ['nullable'],
            'driver.id_vehicle' => ['nullable','integer'],
            'driver.id_trailer' => ['nullable','integer'],
            'driver.id_device' => ['nullable','integer'],

            'driver.tcs_fuel_card_number' => ['nullable','string','max:50'],
            'driver.tcs_fuel_card_pin' => ['nullable','string','max:10'],
            'driver.tcs_fuel_card_limit' => ['nullable'],
        ]);
    }

    private function enforcePhoneDigitCount(array $validated): void
    {
        $phone = (string)($validated['contact']['phone_number'] ?? '');
        $digits = preg_replace('/\D+/', '', $phone);

        $ok = (strlen($digits) === 10) || (strlen($digits) === 11 && str_starts_with($digits, '1'));
        if (!$ok) {
            throw ValidationException::withMessages([
                'contact.phone_number' => ['Phone must contain 10 digits, or 11 digits starting with 1.'],
            ]);
        }
    }

    public function store(Request $request, ContactDriverService $svc)
    {
        $validated = $this->validatePayload($request);
        $this->enforcePhoneDigitCount($validated);

        return response()->json([
            'ok' => true,
            'row' => $svc->create($validated),
        ], 201);
    }

    public function update(int $contactId, Request $request, ContactDriverService $svc)
    {
        $validated = $this->validatePayload($request);
        $this->enforcePhoneDigitCount($validated);

        return response()->json([
            'ok' => true,
            'row' => $svc->update($contactId, $validated),
        ]);
    }

    public function destroy(int $contactId, ContactDriverService $svc)
    {
        $svc->delete($contactId);

        return response()->json(['ok' => true]);
    }

    public function lookups(ContactDriverService $svc)
    {
        return response()->json([
            'ok' => true,
            'lookups' => $svc->lookups(),
        ]);
    }

    public function states(ContactDriverService $svc)
    {
        return response()->json([
            'ok' => true,
            'states' => $svc->usStates(),
        ]);
    }
}
