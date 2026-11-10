<?php

namespace App\Support;

use App\Rules\ContactNumber;
use App\Rules\PersonName;
use App\Rules\PlaceText;
use Carbon\Carbon;
use Closure;
use Illuminate\Validation\Rule;

class ProfileFields
{
    public const EXTENSIONS = ['Jr.', 'Sr.', 'II', 'III', 'IV'];

    public const SEXES = ['Male', 'Female'];

    /**
     * @return array<string, mixed>
     */
    public static function registrationRules(): array
    {
        return [
            'extension_name' => ['nullable', 'string', Rule::in(self::EXTENSIONS)],
            'birthdate' => [
                'required',
                'date',
                'before:today',
                function (string $attribute, mixed $value, Closure $fail) {
                    try {
                        $age = Carbon::parse((string) $value)->age;
                    } catch (\Throwable) {
                        $fail('Enter a valid birthdate.');

                        return;
                    }
                    if ($age < 18) {
                        $fail('You must be at least 18 years old.');
                    }
                },
            ],
            'sex' => ['required', 'string', Rule::in(self::SEXES)],
            'purok_street' => ['required', 'string', 'max:150', new PlaceText],
            'barangay' => ['required', 'string', 'max:100', new PlaceText],
            'city_municipality' => ['required', 'string', 'max:100', new PlaceText],
            'province' => ['required', 'string', 'max:100', new PlaceText],
            'zipcode' => ['required', 'digits:4'],
            'contact_number' => ['required', 'string', new ContactNumber],
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public static function extract(array $validated): array
    {
        $street = $validated['purok_street'] ?? null;
        $barangay = $validated['barangay'] ?? null;
        $city = $validated['city_municipality'] ?? null;
        $province = $validated['province'] ?? null;
        $zip = $validated['zipcode'] ?? null;

        return [
            'extension_name' => $validated['extension_name'] ?? null,
            'birthdate' => $validated['birthdate'] ?? null,
            'sex' => $validated['sex'] ?? null,
            'purok_street' => $street,
            'barangay' => $barangay,
            'city_municipality' => $city,
            'province' => $province,
            'zipcode' => $zip,
            'contact_number' => $validated['contact_number'] ?? null,
            'address' => self::composeAddress($street, $barangay, $city, $province, $zip),
        ];
    }

    public static function composeAddress(?string $street, ?string $barangay, ?string $city, ?string $province, ?string $zip): ?string
    {
        $parts = array_values(array_filter([
            trim((string) $street),
            trim((string) $barangay),
            trim((string) $city),
            trim((string) $province),
            trim((string) $zip),
        ], fn ($part) => $part !== ''));

        return $parts === [] ? null : implode(', ', $parts);
    }
}
