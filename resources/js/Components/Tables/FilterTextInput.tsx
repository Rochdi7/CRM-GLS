import { useEffect, useRef, useState } from 'react';

interface FilterTextInputProps {
    id: string;
    value: string;
    onChange: (value: string) => void;
    placeholder?: string;
    debounceMs?: number;
}

/**
 * Debounced plain text input for labeled filter bars (TableToolbar) — same
 * 400ms pause-before-reload behavior as SearchInput, without the search-icon
 * markup, so per-column filters (Référence, Nom, Prénom, Téléphone…) read as
 * ordinary form controls like the reference CRM's filter rows.
 */
export default function FilterTextInput({ id, value, onChange, placeholder, debounceMs = 400 }: FilterTextInputProps) {
    const [draft, setDraft] = useState(value);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    useEffect(() => {
        setDraft(value);
    }, [value]);

    function handleChange(next: string) {
        setDraft(next);
        clearTimeout(timeoutRef.current);
        timeoutRef.current = setTimeout(() => onChange(next), debounceMs);
    }

    useEffect(() => () => clearTimeout(timeoutRef.current), []);

    return (
        <input
            id={id}
            type="text"
            className="form-control"
            placeholder={placeholder}
            value={draft}
            onChange={(event) => handleChange(event.target.value)}
        />
    );
}
