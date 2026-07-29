/*
 * GLS CRM — Backoffice entry (Vite module, loaded deferred from <head>).
 *
 * Classic theme scripts (jQuery, Bootstrap bundle, script.js, plugins) are
 * loaded as static <script> tags by <x-backoffice.layout.scripts /> and run
 * BEFORE this deferred module, so window.jQuery / window.bootstrap etc. are
 * available here.
 *
 * Plugin initialisation strategy (see CLAUDE.md § JavaScript plugins):
 *  - one central initializeBackofficePlugins() function,
 *  - re-run on `livewire:navigated` because Livewire replaces DOM,
 *  - every initialiser guards against double-initialisation.
 *
 * Alpine.js: provided by Livewire 4 — NEVER import or start Alpine here.
 */

import { restoreThemeAttributes, initTheme } from './theme';

// Apply persisted appearance as early as possible (limits dark-mode flash).
restoreThemeAttributes();

// Select2 search box: shown automatically from this many options up ("auto"),
// or forced with data-search="always" / "never" on the <select>.
function select2SearchThreshold(el) {
    if (el.dataset.search === 'always') return 0;
    if (el.dataset.search === 'never') return Infinity;
    return 6;
}

// Select2 ↔ Livewire bridge, used by <x-backoffice.forms.select2>.
// The select sits inside wire:ignore (Select2 owns that DOM — CLAUDE.md §7);
// this Alpine component keeps the widget and the entangled Livewire property
// in sync in BOTH directions (user picks → property, server sets → widget).
// Registered on alpine:init — Alpine is bundled by Livewire 4, never import it.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('glsSelect2', (value) => ({
        value,
        init() {
            const el = this.$refs.select;
            const $el = window.jQuery(el);
            const $modal = $el.closest('.modal');
            $el.select2({
                width: '100%',
                minimumResultsForSearch: select2SearchThreshold(el),
                // Our Alpine modals sit at z-index 1060; a body-attached
                // dropdown would stack underneath them.
                dropdownParent: $modal.length ? $modal : window.jQuery(document.body),
            });
            const current = () => (this.value === null || this.value === undefined ? '' : String(this.value));
            $el.val(current()).trigger('change.select2');
            // select2:* events fire only on user interaction — no feedback
            // loop with the $watch below.
            $el.on('select2:select select2:clear', () => {
                this.value = $el.val();
            });
            this.$watch('value', () => {
                if ($el.val() !== current()) {
                    $el.val(current()).trigger('change.select2');
                }
            });
        },
        destroy() {
            const $el = window.jQuery(this.$refs.select);
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
        },
    }));
});

// bootstrap-tagsinput ↔ Livewire bridge, used by <x-backoffice.forms.tags-input>.
// The input sits inside wire:ignore (the plugin owns that DOM — CLAUDE.md §7).
// The entangled property stays the stored comma-separated string (mots_cles is
// free text by design, no tags table) while the user works with chips: Enter
// or comma adds a tag, × removes it. Plugin assets are pushed by the page.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('glsTagsInput', (value) => ({
        value,
        // add/removeAll re-fire itemAdded/itemRemoved — muted during
        // programmatic sync so they don't loop back into `value`.
        muted: false,
        init() {
            const $el = window.jQuery(this.$refs.input);
            // Theme styling targets `.tag` (style.css), not the plugin's
            // Bootstrap-3 default label class.
            $el.tagsinput({ tagClass: 'tag', trimValue: true });
            const current = () => (this.value ? String(this.value) : '');
            this.applyTags($el, current());
            $el.on('itemAdded itemRemoved', () => {
                if (!this.muted) this.value = $el.val() || '';
            });
            this.$watch('value', () => {
                if (($el.val() || '') !== current()) {
                    this.applyTags($el, current());
                }
            });
        },
        applyTags($el, csv) {
            this.muted = true;
            $el.tagsinput('removeAll');
            csv.split(',').map((t) => t.trim()).filter(Boolean)
                .forEach((t) => $el.tagsinput('add', t));
            this.muted = false;
        },
        destroy() {
            const $el = window.jQuery(this.$refs.input);
            if ($el.data('tagsinput')) {
                $el.tagsinput('destroy');
            }
        },
    }));
});

// Global toast notifications (<x-backoffice.layout.toasts />). Every
// create/edit/delete operation reports through here — either a Livewire
// `toast` event or a session flash rendered as [data-gls-flash-toast].
export function showBackofficeToast(message, type = 'success') {
    const container = document.getElementById('gls-toasts');
    if (!container || !window.bootstrap || !message) return;

    const el = document.createElement('div');
    el.className = 'toast border-0 shadow-sm';
    el.setAttribute('role', 'alert');
    el.setAttribute('aria-live', type === 'danger' ? 'assertive' : 'polite');
    el.setAttribute('aria-atomic', 'true');
    // The logo is sized with inline styles: theme CSS overrides the height
    // attribute (img { height: auto }) and blows the image up to full size.
    el.innerHTML = `
        <div class="toast-header border-0 pb-1">
            <img src="" alt="" class="me-2" style="height: 20px; width: auto;">
            <strong class="me-auto"></strong>
            <small class="text-muted ms-2"></small>
            <button type="button" class="btn-close ms-2" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body d-flex align-items-start pt-1">
            <i class="ti ${type === 'danger' ? 'ti-alert-circle-filled text-danger' : 'ti-circle-check-filled text-success'} me-2" style="font-size: 18px; line-height: 1.3;"></i>
            <span></span>
        </div>`;
    el.querySelector('img').src = container.dataset.logo;
    el.querySelector('strong').textContent = container.dataset.appName;
    el.querySelector('small').textContent = container.dataset.labelJustNow;
    el.querySelector('.btn-close').setAttribute('aria-label', container.dataset.labelClose);
    el.querySelector('.toast-body span').textContent = message;

    container.appendChild(el);
    el.addEventListener('hidden.bs.toast', () => el.remove());
    new window.bootstrap.Toast(el, { delay: 5000 }).show();
}

// Session-flashed statuses (controller redirects, full page loads) — each
// hidden node is consumed exactly once, so re-runs are safe.
function showFlashedToasts() {
    document.querySelectorAll('[data-gls-flash-toast]').forEach((el) => {
        showBackofficeToast(el.dataset.message, el.dataset.type);
        el.remove();
    });
}

// Livewire actions (modal create/edit/delete without redirect).
document.addEventListener('livewire:init', () => {
    window.Livewire.on('toast', (payload) => {
        const data = Array.isArray(payload) ? payload[0] : payload;
        showBackofficeToast(data?.message, data?.type ?? 'success');
    });
});

export function initializeBackofficePlugins() {
    // Select2 — activates <select class="select"> (theme convention) on
    // non-Livewire markup. Livewire-bound selects use <x-backoffice.forms.select2>.
    if (window.jQuery && window.jQuery.fn.select2) {
        window.jQuery('select.select').each(function initSelect2() {
            const $el = window.jQuery(this);
            if (!$el.hasClass('select2-hidden-accessible')) {
                $el.select2({
                    minimumResultsForSearch: select2SearchThreshold(this),
                    width: '100%',
                });
            }
        });
    }

    // Bootstrap tooltips / popovers.
    if (window.bootstrap) {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
            if (!window.bootstrap.Tooltip.getInstance(el)) {
                new window.bootstrap.Tooltip(el);
            }
        });
        document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => {
            if (!window.bootstrap.Popover.getInstance(el)) {
                new window.bootstrap.Popover(el);
            }
        });
    }

    // Feather icons (used by the theme via data-feather attributes).
    if (window.feather) {
        window.feather.replace();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initTheme();
    initializeBackofficePlugins();
    showFlashedToasts();
});

// Livewire swaps DOM on navigation — re-initialise plugins on the new markup.
document.addEventListener('livewire:navigated', () => {
    initTheme();
    initializeBackofficePlugins();
    showFlashedToasts();
});
