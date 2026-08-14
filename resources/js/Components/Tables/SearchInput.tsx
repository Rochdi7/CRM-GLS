import { useEffect, useRef, useState } from 'react';

interface SearchInputProps {
    value: string;
    onSearch: (value: string) => void;
    placeholder?: string;
    debounceMs?: number;
}

/**
 * Debounced search box matching the `.input-icon-start` markup used by
 * every existing filter-bar (components/backoffice/ui/filter-bar.blade.php).
 * Mirrors the Livewire `wire:model.live.debounce.400ms` behavior — the
 * parent only sees onSearch calls after the user pauses typing.
 */
export default function SearchInput({ value, onSearch, placeholder = 'Rechercher', debounceMs = 400 }: SearchInputProps) {
    const [draft, setDraft] = useState(value);
    const timeoutRef = useRef<ReturnType<typeof setTimeout> | undefined>(undefined);

    useEffect(() => {
        setDraft(value);
    }, [value]);

    function handleChange(next: string) {
        setDraft(next);
        clearTimeout(timeoutRef.current);
        timeoutRef.current = setTimeout(() => onSearch(next), debounceMs);
    }

    useEffect(() => () => clearTimeout(timeoutRef.current), []);

    return (
        <div className="input-icon-start position-relative">
            <span className="input-icon-addon">
                <i className="ti ti-search" />
            </span>
            <input
                type="text"
                className="form-control"
                placeholder={placeholder}
                value={draft}
                onChange={(event) => handleChange(event.target.value)}
            />
        </div>
    );
}
