import { useState } from 'react';
import type { InputHTMLAttributes } from 'react';
import FormError from '@/Components/Forms/FormError';

interface PasswordFieldProps extends Omit<InputHTMLAttributes<HTMLInputElement>, 'id' | 'type'> {
    id: string;
    label: string;
    error?: string;
    required?: boolean;
}

/**
 * Matches the `.pass-group`/`.toggle-password` markup used by the existing
 * auth Blade pages, but the visibility toggle is React state instead of the
 * jQuery click handler in public/assets/preskool/js/script.js (that script
 * is never loaded on Inertia pages — see docs/bootstrap-react-integration-decision.md).
 */
export default function PasswordField({ id, label, error, required, className, ...inputProps }: PasswordFieldProps) {
    const [visible, setVisible] = useState(false);

    return (
        <div className="mb-3">
            <label className="form-label" htmlFor={id}>
                {label}
                {required && <span className="text-danger ms-1">*</span>}
            </label>
            <div className="pass-group">
                <input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    className={`pass-input form-control${error ? ' is-invalid' : ''}${className ? ` ${className}` : ''}`}
                    required={required}
                    aria-invalid={error ? true : undefined}
                    aria-describedby={error ? `${id}-error` : undefined}
                    {...inputProps}
                />
                <button
                    type="button"
                    className={`fa toggle-password border-0 bg-transparent ${visible ? 'fa-eye' : 'fa-eye-slash'}`}
                    onClick={() => setVisible((value) => !value)}
                    aria-label={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'}
                />
            </div>
            {error && (
                <div id={`${id}-error`}>
                    <FormError message={error} />
                </div>
            )}
        </div>
    );
}
