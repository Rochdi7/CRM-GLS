import FormError from '@/Components/Forms/FormError';
import { COUNTRIES, dialFor } from '@/Data/countries';

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
                <div className="col-5">
                    <select
                        id={`${id}-country`}
                        className="form-select"
                        value={countryIso}
                        onChange={(event) => onCountryChange(event.target.value)}
                        aria-label="Indicatif pays"
                    >
                        {COUNTRIES.map((country) => (
                            <option key={country.iso} value={country.iso}>
                                {country.nom} {country.dial}
                            </option>
                        ))}
                    </select>
                </div>
                <div className="col-7">
                    <div className="input-group">
                        <span className="input-group-text">{dialFor(countryIso)}</span>
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
