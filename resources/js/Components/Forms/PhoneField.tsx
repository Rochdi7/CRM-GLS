import FormError from '@/Components/Forms/FormError';
import { COUNTRIES } from '@/Data/countries';
import SelectField from '@/Components/Forms/SelectField';

interface PhoneFieldProps {
    id: string;
    label: string;
    countryIso: string;
    national: string;
    onCountryChange: (iso: string) => void;
    onNationalChange: (value: string) => void;
    error?: string;
    required?: boolean;
}

/**
 * Country-dial + national-number combo, matching
 * components/backoffice/forms/phone-input.blade.php's `.input-group`
 * markup (dial-code prefix + input) with the country picked from a plain
 * native <select> (no Select2/jQuery on Inertia pages — see SelectField).
 * The Livewire version shares ONE country selector across every phone
 * field on a form (WithPhoneCountry::$phonePays); Etablissements has only
 * one phone field, so this component owns its own country state directly
 * rather than needing that cross-field sharing mechanism.
 */
export default function PhoneField({
    id,
    label,
    countryIso,
    national,
    onCountryChange,
    onNationalChange,
    error,
    required,
}: PhoneFieldProps) {
    return (
        <div className="mb-3">
            <label className="form-label" htmlFor={id}>
                {label}
                {required && <span className="text-danger ms-1">*</span>}
            </label>
            <div className="row g-2">
                <div className="col-6 col-sm-5">
                    {/* Same Select2-styled control as every other dropdown
                        (bare mode: no label/margin of its own). The closed
                        control shows just flag + dial code (shortLabel) —
                        the full country name doesn't fit this column —
                        while the open option list still shows the full
                        name via `label` for search/readability. */}
                    <SelectField
                        id={`${id}-country`}
                        options={COUNTRIES.map((country) => ({
                            value: country.iso,
                            label: `${country.nom} (${country.dial})`,
                            shortLabel: country.dial,
                            icon: (
                                <i
                                    className={`flag flag-${country.iso.toLowerCase()} flex-shrink-0`}
                                    aria-hidden="true"
                                    title={country.nom}
                                />
                            ),
                        }))}
                        value={countryIso}
                        onChange={(event) => onCountryChange(event.target.value)}
                    />
                </div>
                <div className="col-6 col-sm-7">
                    <div className="input-group">
                        <span className="input-group-text">
                            <i className="ti ti-phone" aria-hidden="true" />
                        </span>
                        <input
                            id={id}
                            type="tel"
                            className={`form-control${error ? ' is-invalid' : ''}`}
                            placeholder="ex : 661954125"
                            value={national}
                            onChange={(event) => onNationalChange(event.target.value)}
                            aria-invalid={error ? true : undefined}
                            aria-describedby={error ? `${id}-error` : undefined}
                        />
                    </div>
                </div>
            </div>
            {error && (
                <div id={`${id}-error`}>
                    <FormError message={error} />
                </div>
            )}
        </div>
    );
}
