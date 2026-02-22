<?php

namespace App\Services\ContactDriver;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ContactDriverService
{
    public function usStates()
    {
        return DB::table('us_states')
            ->select(['id', 'state_name', 'state_code'])
            ->orderBy('state_name')
            ->get();
    }

    public function lookups(): array
    {
        $carriers = DB::table('carrier')
            ->where('is_deleted', 0)
            ->select(['id_carrier as id', 'carrier_name as name'])
            ->orderBy('carrier_name')
            ->get();

        $projects = DB::table('projects')
            ->select(['idprojects as id', 'projectname as name'])
            ->orderBy('projectname')
            ->get();

        $trucks = DB::table('vehicle')
            ->where('is_deleted', 0)
            ->where('id_vehicle_type', 2)
            ->select(['id_vehicle as id', 'vehicle_name', 'vehicle_number'])
            ->orderBy('vehicle_name')
            ->get();

        $trailers = DB::table('vehicle')
            ->where('is_deleted', 0)
            ->where('id_vehicle_type', 1)
            ->select(['id_vehicle as id', 'vehicle_name', 'vehicle_number'])
            ->orderBy('vehicle_name')
            ->get();

        return [
            'carriers' => $carriers,
            'projects' => $projects,
            'trucks' => $trucks,
            'trailers' => $trailers,
            'states' => $this->usStates(),
        ];
    }

    private function baseListQuery(string $q)
    {
        $qb = DB::table('contactdriverview as v')
            ->join('contact as c', 'c.id_contact', '=', 'v.id_contact')
            ->where('c.is_deleted', 0)
            ->select([
                'v.id_contact',
                'v.first_name',
                'v.last_name',
                'v.phone_number',
                'v.state',
                'v.is_driver',
                'v.carrier_name',
            ]);

        $q = trim($q);

        if ($q !== '') {
            $qq = '%' . str_replace(['%','_'], ['\%','\_'], $q) . '%';

            $state2 = strtoupper($q);
            $isStateCode = (strlen($state2) === 2 && ctype_alpha($state2));

            $qb->where(function ($w) use ($qq, $isStateCode, $state2) {
                $w->where(DB::raw("CONCAT(v.first_name,' ',v.last_name)"), 'LIKE', $qq)
                    ->orWhere('v.first_name', 'LIKE', $qq)
                    ->orWhere('v.last_name', 'LIKE', $qq)
                    ->orWhere('v.phone_number', 'LIKE', $qq)
                    ->orWhere('v.carrier_name', 'LIKE', $qq)
                    ->orWhere('v.state', 'LIKE', $qq);

                if ($isStateCode) {
                    $w->orWhere(DB::raw('UPPER(v.state)'), '=', $state2);
                }

                $w->orWhere('c.email', 'LIKE', $qq)
                    ->orWhere('c.address', 'LIKE', $qq);
            });
        }

        return $qb;
    }

    public function listPaged(int $limit, int $page, string $q, string $sort, string $dir): array
    {
        $limit = max(1, min($limit, 200));
        $page  = max(1, $page);
        $dir   = strtolower($dir) === 'desc' ? 'desc' : 'asc';

        $qb = $this->baseListQuery($q);

        $countQb = clone $qb;
        $total = (int) $countQb
            ->select(DB::raw('COUNT(DISTINCT v.id_contact) as aggregate'))
            ->value('aggregate');

        $sortMap = [
            'name'   => 'v.last_name',
            'phone'  => 'v.phone_number',
            'state'  => 'v.state',
            'carrier'=> 'v.carrier_name',
            'driver' => 'v.is_driver',
        ];

        $qb->orderBy($sortMap[$sort] ?? 'v.last_name', $dir)
            ->orderBy('v.first_name', $dir);

        $offset = ($page - 1) * $limit;

        $rows = $qb->distinct('v.id_contact')
            ->offset($offset)
            ->limit($limit)
            ->get();

        return [
            'rows' => $rows,
            'total' => $total,
        ];
    }

    private function intOrNull($v): ?int
    {
        if ($v === null) return null;
        if ($v === '') return null;
        if (is_bool($v)) return $v ? 1 : 0;
        if (is_numeric($v)) return (int)$v;
        return null;
    }

    private function digitsOnly(?string $s): string
    {
        return preg_replace('/\D+/', '', (string)$s);
    }

    private function last4Phone(?string $phone): ?string
    {
        $digits = $this->digitsOnly($phone);
        return strlen($digits) >= 4 ? substr($digits, -4) : null;
    }

    private function throw422(array $messages): void
    {
        throw ValidationException::withMessages($messages);
    }

    /**
     * Phone rule:
     * - required
     * - must be ###-###-#### OR 1-###-###-####
     * - max length 14
     * - digits must be 10 OR 11 starting with 1
     */
    private function assertPhoneValid(?string $phone): void
    {
        $phone = trim((string)$phone);

        if ($phone === '') {
            $this->throw422(['contact.phone_number' => ['Phone is required.']]);
        }

        if (strlen($phone) > 14) {
            $this->throw422(['contact.phone_number' => ['Phone must be ###-###-#### or 1-###-###-####.']]);
        }

        $okFmt = preg_match('/^(\d{3}-\d{3}-\d{4}|\d-\d{3}-\d{3}-\d{4})$/', $phone) === 1;
        if (!$okFmt) {
            $this->throw422(['contact.phone_number' => ['Phone must be ###-###-#### or 1-###-###-####.']]);
        }

        $digits = $this->digitsOnly($phone);
        $okDigits = (strlen($digits) === 10) || (strlen($digits) === 11 && str_starts_with($digits, '1'));
        if (!$okDigits) {
            $this->throw422(['contact.phone_number' => ['Phone must contain 10 digits, or 11 digits starting with 1.']]);
        }
    }

    /**
     * Email rule:
     * - NOT required
     * - if provided → must be valid email
     * - max 190 chars
     */
    private function assertEmailValid(?string $email): void
    {
        $email = trim((string)$email);

        // ✅ If empty → skip validation completely
        if ($email === '') {
            return;
        }

        if (strlen($email) > 190) {
            $this->throw422([
                'contact.email' => ['Email may not be greater than 190 characters.']
            ]);
        }

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $this->throw422([
                'contact.email' => ['Email must be like name@company.com.']
            ]);
        }
    }

    private function normalizeContactForSave(array $c): array
    {
        $first = (string)($c['first_name'] ?? '');
        $last  = (string)($c['last_name'] ?? '');

        $phone = (string)($c['phone_number'] ?? '');
        $email = (string)($c['email'] ?? '');

        $this->assertPhoneValid($phone);
        $this->assertEmailValid($email);

        return [
            'first_name' => $first,
            'last_name' => $last,
            'address' => $c['address'] ?? null,
            'state' => $c['state'] ?? null,
            'phone_number' => $phone,
            'email' => trim($email),
        ];
    }

    /**
     * Driver rules:
     * - If is_driver=1 => carrier REQUIRED
     * - NEW driver: if idprojects missing => 10
     * - NEW driver: if mobile_app_pin missing => last4(phone)
     * - If is_driver!=1 => driver-only fields must be NULL
     */
    private function normalizeDriverForSave(array $d, ?string $contactPhone = null, bool $isCreate = false): array
    {
        $isDriver = (int)($d['is_driver'] ?? 0);

        $spanish = array_key_exists('spanish_language', $d)
            ? $this->intOrNull($d['spanish_language'])
            : ($isCreate ? 0 : $this->intOrNull($d['spanish_language'] ?? null));

        $tcsNum = ($d['tcs_fuel_card_number'] ?? null) ?: null;
        $tcsPin = ($d['tcs_fuel_card_pin'] ?? null) ?: null;

        if (($tcsPin === null || $tcsPin === '') && ($tcsNum !== null && $tcsNum !== '')) {
            $tcsPin = $this->last4Phone($contactPhone);
        }

        $out = [
            'is_driver' => $isDriver,
            'id_carrier' => $this->intOrNull($d['id_carrier'] ?? null),
            'driver_shift' => $this->intOrNull($d['driver_shift'] ?? null),
            'spanish_language' => $spanish,
            'id_vehicle' => $this->intOrNull($d['id_vehicle'] ?? null),
            'id_trailer' => $this->intOrNull($d['id_trailer'] ?? null),
            'id_device' => $this->intOrNull($d['id_device'] ?? null),
            'status' => $this->intOrNull($d['status'] ?? null),
            'idprojects' => $this->intOrNull($d['idprojects'] ?? null),
            'mobile_app_pin' => ($d['mobile_app_pin'] ?? null) ?: null,
            'driver_profile_url' => ($d['driver_profile_url'] ?? null) ?: null,
            'tcs_fuel_card_number' => $tcsNum,
            'tcs_fuel_card_pin' => ($tcsPin ?? null) ?: null,
            'tcs_fuel_card_limit' => ($d['tcs_fuel_card_limit'] ?? null) ?: null,
        ];

        if ($isDriver === 1) {
            if ($out['id_carrier'] === null) {
                $this->throw422([
                    'driver.id_carrier' => ['Carrier is required when "Is a Driver" is checked.'],
                ]);
            }

            // NEW driver defaults ONLY
            if ($isCreate) {
                if ($out['idprojects'] === null) {
                    $out['idprojects'] = 10;
                }
                if ($out['mobile_app_pin'] === null || $out['mobile_app_pin'] === '') {
                    $out['mobile_app_pin'] = $this->last4Phone($contactPhone);
                }
            }
        } else {
            foreach ([
                         'id_carrier','driver_shift','spanish_language','id_vehicle','id_trailer','id_device',
                         'status','idprojects','mobile_app_pin','driver_profile_url',
                         'tcs_fuel_card_number','tcs_fuel_card_pin','tcs_fuel_card_limit'
                     ] as $k) {
                $out[$k] = null;
            }
        }

        return $out;
    }

    public function get(int $contactId): array
    {
        $contact = DB::table('contact')
            ->where('id_contact', $contactId)
            ->where('is_deleted', 0)
            ->first();

        if (!$contact) abort(404, 'Contact not found');

        $driver = DB::table('driver')->where('id_contact', $contactId)->first();
        if (!$driver) {
            $id = DB::table('driver')->insertGetId([
                'id_contact' => $contactId,
                'is_driver' => 0,
                'is_deleted' => 0,
                'date_created' => time(),

                'id_vehicle' => null,
                'id_trailer' => null,
                'id_carrier' => null,
                'id_device' => null,
                'status' => null,
                'driver_shift' => null,
                'spanish_language' => 0,
                'idprojects' => null,

                'mobile_app_pin' => null,
                'driver_profile_url' => null,
                'tcs_fuel_card_number' => null,
                'tcs_fuel_card_pin' => null,
                'tcs_fuel_card_limit' => null,
                'tcs_fuel_card_last_updated' => null,
            ]);

            $driver = DB::table('driver')->where('id_driver', $id)->first();
        }

        return ['contact' => $contact, 'driver' => $driver];
    }

    public function create(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $c = $payload['contact'] ?? [];
            $d = $payload['driver'] ?? [];

            $cNorm = $this->normalizeContactForSave($c);

            $contactId = DB::table('contact')->insertGetId([
                'first_name' => $cNorm['first_name'],
                'last_name' => $cNorm['last_name'],
                'address' => $cNorm['address'],
                'state' => $cNorm['state'],
                'phone_number' => $cNorm['phone_number'],
                'email' => $cNorm['email'],
                'is_deleted' => 0,
                'date_created' => time(),
            ]);

            $normalizedDriver = $this->normalizeDriverForSave($d, $cNorm['phone_number'], true);

            DB::table('driver')->insert(array_merge($normalizedDriver, [
                'id_contact' => $contactId,
                'is_deleted' => 0,
                'date_created' => time(),
            ]));

            return $this->get($contactId);
        });
    }

    public function update(int $contactId, array $payload): array
    {
        return DB::transaction(function () use ($contactId, $payload) {
            $c = $payload['contact'] ?? [];
            $d = $payload['driver'] ?? [];

            $cNorm = $this->normalizeContactForSave($c);

            DB::table('contact')->where('id_contact', $contactId)->update([
                'first_name' => $cNorm['first_name'],
                'last_name' => $cNorm['last_name'],
                'address' => $cNorm['address'],
                'state' => $cNorm['state'],
                'phone_number' => $cNorm['phone_number'],
                'email' => $cNorm['email'],
                'date_modified' => time(),
            ]);

            $driverRow = DB::table('driver')->where('id_contact', $contactId)->first();
            if (!$driverRow) {
                DB::table('driver')->insert([
                    'id_contact' => $contactId,
                    'is_driver' => 0,
                    'is_deleted' => 0,
                    'date_created' => time(),
                    'spanish_language' => 0,
                ]);
            }

            // NOTE: defaults (project=10, pin=last4) are ONLY for CREATE
            $normalizedDriver = $this->normalizeDriverForSave($d, $cNorm['phone_number'], false);

            $fuelChanged =
                array_key_exists('tcs_fuel_card_number', $d) ||
                array_key_exists('tcs_fuel_card_pin', $d) ||
                array_key_exists('tcs_fuel_card_limit', $d);

            $update = array_merge($normalizedDriver, [
                'date_modified' => time(),
            ]);

            if ((int)($normalizedDriver['is_driver'] ?? 0) === 1) {
                $update['tcs_fuel_card_last_updated'] = $fuelChanged
                    ? Carbon::now()
                    : DB::raw('tcs_fuel_card_last_updated');
            } else {
                $update['tcs_fuel_card_last_updated'] = DB::raw('tcs_fuel_card_last_updated');
            }

            DB::table('driver')->where('id_contact', $contactId)->update($update);

            return $this->get($contactId);
        });
    }

    public function delete(int $contactId): void
    {
        DB::transaction(function () use ($contactId) {
            DB::table('contact')
                ->where('id_contact', $contactId)
                ->update([
                    'is_deleted' => 1,
                    'date_modified' => time(),
                ]);

            DB::table('driver')
                ->where('id_contact', $contactId)
                ->update([
                    'is_deleted' => 1,
                    'date_modified' => time(),
                ]);
        });
    }
}
