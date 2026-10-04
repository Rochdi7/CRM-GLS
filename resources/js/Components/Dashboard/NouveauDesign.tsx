import { useState } from 'react';
import { t } from '@/Lib/i18n';

/** Bump the suffix to show the note again after a future redesign. */
const STORAGE_KEY = 'gls-nouveau-design-2026-10';

function dejaVu(): boolean {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

/**
 * « Une nouvelle vie pour votre CRM » — short note announcing the colour
 * redesign. Dismissal is a per-viewer convenience (localStorage, wrapped in
 * try/catch); if storage is unavailable the note simply shows again.
 */
export default function NouveauDesign() {
    const [visible, setVisible] = useState(() => !dejaVu());

    if (!visible) return null;

    function fermer() {
        try {
            window.localStorage.setItem(STORAGE_KEY, '1');
        } catch {
            // storage blocked: hide for this visit only
        }
        setVisible(false);
    }

    return (
        <div className="gls-nouveau-design">
            <span className="gls-nouveau-design-icon" aria-hidden="true">
                <i className="ti ti-sparkles" />
            </span>
            <div className="flex-fill">
                <h5 className="gls-nouveau-design-title">
                    {t('A new life for your CRM')}
                    <span className="badge badge-soft-primary ms-2">{t('New')}</span>
                </h5>
                <p className="mb-0">
                    {t('The CRM has a fresh new look: more colours to find your way faster, clearer tables, modernised windows and cards. Everything works exactly as before — only the design has changed.')}
                </p>
            </div>
            <button type="button" className="btn btn-sm btn-outline-primary flex-shrink-0" onClick={fermer}>
                {t('Got it')}
            </button>
        </div>
    );
}
