import { useState } from 'react';
import type { KeyboardEvent } from 'react';
import FormError from '@/Components/Forms/FormError';

interface TagsInputProps {
    id: string;
    label: string;
    /** Comma-separated string — same wire format the field already used as plain text, so the backend needs no change. */
    value: string;
    onChange: (value: string) => void;
    error?: string;
    placeholder?: string;
}

/**
 * Enter-to-add tag/chip input. Value stays a comma-separated string on the
 * wire (unchanged StoreDepenseRequest validation) — this component only
 * changes how the user builds that string: type a word, press Enter (or
 * comma) to turn it into a removable chip, repeat for more.
 */
export default function TagsInput({ id, label, value, onChange, error, placeholder }: TagsInputProps) {
    const [draft, setDraft] = useState('');

    const tags = value === '' ? [] : value.split(',').map((t) => t.trim()).filter(Boolean);

    function commitDraft() {
        const tag = draft.trim();
        if (tag === '' || tags.includes(tag)) {
            setDraft('');
            return;
        }
        onChange([...tags, tag].join(','));
        setDraft('');
    }

    function handleKeyDown(event: KeyboardEvent<HTMLInputElement>) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            commitDraft();
        } else if (event.key === 'Backspace' && draft === '' && tags.length > 0) {
            onChange(tags.slice(0, -1).join(','));
        }
    }

    function removeTag(tag: string) {
        onChange(tags.filter((t) => t !== tag).join(','));
    }

    return (
        <div className="mb-3">
            <label className="form-label" htmlFor={id}>
                {label}
            </label>
            <div
                className={`form-control d-flex flex-wrap align-items-center gap-1${error ? ' is-invalid' : ''}`}
                style={{ minHeight: 44, height: 'auto', cursor: 'text' }}
                onClick={() => document.getElementById(id)?.focus()}
            >
                {tags.map((tag) => (
                    <span key={tag} className="badge bg-primary d-inline-flex align-items-center fw-normal fs-13">
                        {tag}
                        <i
                            role="button"
                            aria-label={`Retirer ${tag}`}
                            className="fa fa-xmark ms-1"
                            onClick={(event) => {
                                event.stopPropagation();
                                removeTag(tag);
                            }}
                        />
                    </span>
                ))}
                <input
                    id={id}
                    type="text"
                    className="border-0 flex-grow-1 p-0"
                    style={{ minWidth: 120, outline: 'none' }}
                    value={draft}
                    onChange={(event) => setDraft(event.target.value)}
                    onKeyDown={handleKeyDown}
                    onBlur={commitDraft}
                    placeholder={tags.length === 0 ? placeholder : undefined}
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
