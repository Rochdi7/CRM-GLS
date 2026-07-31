import { t, type TranslationReplacements } from '@/Lib/i18n';

/**
 * Ergonomic wrapper around `t()` for page/component imports — GLS CRM is
 * French-only (CLAUDE.md §12), so this has no locale-switching logic today;
 * it exists so call sites can `const { t } = useTranslation();` like a
 * conventional i18n hook instead of importing `t` directly everywhere.
 *
 * Not a React hook in the strict sense (no hooks are called internally) but
 * named/shaped like one for familiarity and to make a future real locale
 * switch (AR / EN / DE — see CLAUDE.md §12) a drop-in change here only.
 */
export function useTranslation(): { t: (key: string, replacements?: TranslationReplacements) => string } {
    return { t };
}
