<?php

declare(strict_types=1);

namespace App\Livewire\Backoffice\Concerns;

use App\Support\Phone\Countries;
use Illuminate\Validation\Rule;

/**
 * Country-code handling for phone fields (<x-backoffice.forms.phone-input>).
 * The Livewire property keeps the NATIONAL part typed by the user; the
 * selected country lives in $phonePays[field]; the database stores the
 * combined "+212661954125" produced by phoneValue().
 */
trait WithPhoneCountry
{
    /**
     * ONE country dial code shared by every phone field on the form
     * (single "Pays" selector controls all — telephone, whatsapp, parent…).
     */
    public string $phonePays = '';

    /**
     * @return array<string, mixed>
     */
    protected function phonePaysRules(): array
    {
        return ['phonePays' => ['required', Rule::in(array_keys(Countries::LIST))]];
    }

    /** @param  string  ...$fields  ignored — kept for call-site compatibility */
    protected function resetPhonePays(string ...$fields): void
    {
        $this->phonePays = Countries::DEFAULT;
    }

    /**
     * Load a stored combined number into the national-part property.
     * The first non-empty stored number sets the shared country; others
     * just keep their national part (they share the same dial code on save).
     */
    protected function fillPhone(string $field, ?string $stored): void
    {
        [$pays, $national] = Countries::split($stored);
        $this->{$field} = $national;

        if ($stored !== null && $stored !== '' && ($this->phonePays === '' || $this->phonePays === Countries::DEFAULT)) {
            $this->phonePays = $pays;
        }
    }

    /** Combined value to persist ("+212661954125"), or null when empty. */
    protected function phoneValue(string $field): ?string
    {
        return Countries::join($this->phonePays ?: Countries::DEFAULT, (string) $this->{$field});
    }
}
