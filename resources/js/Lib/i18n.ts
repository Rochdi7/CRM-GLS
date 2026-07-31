/**
 * Shared translation helper — GLS CRM is French-only (CLAUDE.md §12: "French
 * is the default UI language ... All page content must display in French").
 *
 * The dictionary is `lang/fr.json` at the project root — the SAME file the
 * Blade/Livewire side uses via Laravel's `__('...')` convention (English
 * string as key, French string as value). Imported directly here (not
 * duplicated) so both sides always agree on translations — see the README
 * note below for why this is safe with Vite + TypeScript.
 *
 * Usage:
 *
 *   import { t } from '@/Lib/i18n';
 *
 *   t('Dashboard')                                  // 'Tableau de bord'
 *   t('Showing :from to :to of :total results', {   // 'Affichage de 1 à 10 sur 42 résultats'
 *       from: 1, to: 10, total: 42,
 *   })
 *   t('Some New English String')                    // 'Some New English String' (key returned as-is, never throws)
 */
import dictionary from '../../../lang/fr.json';

/** Values accepted for `:placeholder` replacement — matches how they are normally interpolated (numbers, strings). */
export type TranslationReplacements = Record<string, string | number>;

const translations: Record<string, string> = dictionary;

/**
 * Translates `key` (the English source string, matching Laravel's `__('...')`
 * convention) to French using `lang/fr.json`. Falls back to returning `key`
 * unchanged when no entry exists — never throws, so a missing translation
 * degrades to readable (if English) UI instead of a crash.
 *
 * `replacements` performs Laravel-style `:placeholder` substitution, e.g.
 * `{ count: 5 }` replaces `:count` in the resolved string.
 */
export function t(key: string, replacements?: TranslationReplacements): string {
    let value = Object.prototype.hasOwnProperty.call(translations, key) ? translations[key] : key;

    if (replacements) {
        for (const [placeholder, replacement] of Object.entries(replacements)) {
            // Word-boundary after the placeholder name so a shorter key (e.g. `:to`)
            // cannot match inside a longer one (e.g. `:total`) regardless of
            // iteration order — plain `replaceAll(':to', ...)` would corrupt
            // `:total` into `"<value>tal"`.
            value = value.replace(new RegExp(`:${placeholder}\\b`, 'g'), String(replacement));
        }
    }

    return value;
}
