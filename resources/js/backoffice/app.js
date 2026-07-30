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
        selectElement: null,
        $select: null,
        select2Instance: null,
        optionsObserver: null,

        modalOpenedHandler: null,

        init() {
            this.mountSelect2();

            // Every modal in the app is mounted once and toggled via
            // display:none (Alpine `show`), so this Select2 island first
            // initializes while its ancestor is hidden. Select2 reads
            // offsetWidth/computed styles at init time to size its selection
            // box; against a display:none ancestor that yields 0, leaving a
            // blank/broken selection render that showing the modal
            // afterwards does not repair on its own. Rebuilding once on the
            // paired gls-select2-modal-opened event (dispatched by the same
            // modal shell that already dispatches gls-select2-modal-closed)
            // fixes the geometry once the element is actually visible.
            this.modalOpenedHandler = (event) => {
                const currentEl = this.rawSelectElement();
                const currentJq = this.rawJQuerySelect();

                if (!currentEl || !this.ownsSelect2(currentEl, currentJq) || !event.detail?.modal?.contains(currentEl)) {
                    return;
                }

                this.mountSelect2();
            };

            window.addEventListener('gls-select2-modal-opened', this.modalOpenedHandler);
        },

        mountSelect2() {
            this.teardownSelect2();

            const el = this.$refs.select;

            if (!el || !el.isConnected || !window.jQuery?.fn?.select2) {
                return;
            }

            const $el = window.jQuery(el);

            // A previous Alpine instance may have been removed by a Livewire
            // morph before its destroy hook ran. Clean up only this element.
            this.destroySelect2($el);

            // Belt-and-braces: if an earlier Select2 instance on this exact
            // element lost track of its own container (e.g. two initialisers
            // raced on first mount before either had attached select2 data),
            // destroySelect2() above finds nothing to clean up via jQuery's
            // instance cache and a stray `.select2-container` is left behind
            // as a dead sibling — rendering as a second, empty dropdown next
            // to the real one. Sweep any leftovers by position instead of by
            // instance reference before creating the real widget.
            const parent = el.parentNode;

            if (parent) {
                Array.from(parent.querySelectorAll(':scope > .select2-container')).forEach((node) => node.remove());
            }

            const $modal = $el.closest('.modal');

            this.selectElement = el;
            this.$select = $el;

            $el.select2({
                width: '100%',
                minimumResultsForSearch: select2SearchThreshold(el),
                // Our Alpine modals sit at z-index 1060; a body-attached
                // dropdown would stack underneath them.
                dropdownParent: $modal.length ? $modal : window.jQuery(document.body),
            });

            this.select2Instance = $el.data('select2') ?? null;

            const current = () => (this.value === null || this.value === undefined ? '' : String(this.value));

            $el.val(current()).trigger('change.select2');
            this.watchOptionChanges(el, $el, current);

            // User picks can arrive as select2:* or as a native change,
            // depending on the Select2 path used by the theme/browser.
            // Programmatic sync uses change.select2 and will not hit this
            // .glsSelect2 handler, so it does not feed back into the watcher.
            $el
                .off('.glsSelect2')
                .on('change.glsSelect2 select2:select.glsSelect2 select2:clear.glsSelect2', () => {
                    if (!this.ownsSelect2(el, $el)) {
                        return;
                    }

                    this.value = $el.val();
                });

            // Livewire's bundled Alpine registers $watch cleanup for this
            // component, but its $watch magic does not return that callback.
            // This watcher is therefore created once per Alpine component and
            // verifies the exact element and instance before doing any work.
            this.$watch('value', () => {
                if (!this.ownsSelect2(el, $el)) {
                    return;
                }

                const expected = current();
                const actual = String($el.val() ?? '');

                if (actual !== expected) {
                    $el.val(expected).trigger('change.select2');
                }
            });
        },

        ownsSelect2(el, $el) {
            return this.rawSelectElement() === el
                && this.rawJQuerySelect() === $el
                && this.$refs.select === el
                && el.isConnected
                && this.select2Instance !== null
                && $el.data('select2') === this.rawSelect2Instance();
        },

        raw(value) {
            return typeof window.Alpine.raw === 'function'
                ? window.Alpine.raw(value)
                : value;
        },

        rawSelectElement() {
            return this.raw(this.selectElement);
        },

        rawJQuerySelect() {
            return this.raw(this.$select);
        },

        rawSelect2Instance() {
            return this.raw(this.select2Instance);
        },

        watchOptionChanges(el, $el, current) {
            this.disconnectOptionsObserver();

            this.optionsObserver = new MutationObserver(() => {
                if (!this.ownsSelect2(el, $el)) {
                    return;
                }

                const expected = current();

                if (expected !== '' && !el.querySelector(`option[value="${CSS.escape(expected)}"]`)) {
                    this.value = '';
                }

                $el.val(this.value === null || this.value === undefined ? '' : String(this.value))
                    .trigger('change.select2');
            });

            this.optionsObserver.observe(el, {
                childList: true,
                subtree: true,
                characterData: true,
            });
        },

        disconnectOptionsObserver() {
            const observer = this.raw(this.optionsObserver);

            if (observer) {
                observer.disconnect();
            }

            this.optionsObserver = null;
        },

        closeForModal(modal) {
            const el = this.rawSelectElement();

            if (!modal || !el || !modal.contains(el)) {
                return;
            }

            this.closeSelect2();
        },

        closeSelect2() {
            const $el = this.rawJQuerySelect();

            if (!$el || $el.data('select2') !== this.rawSelect2Instance()) {
                return;
            }

            try {
                $el.select2('close');
            } catch (error) {
                // The element may already have been removed by a morph.
            }
        },

        destroySelect2($el) {
            if (!$el || !($el.hasClass('select2-hidden-accessible') || $el.data('select2'))) {
                return;
            }

            const instance = $el.data('select2');
            const artifacts = {
                $container: instance?.$container,
                $dropdown: instance?.dropdown?.$dropdown,
                $dropdownContainer: instance?.dropdown?.$dropdownContainer,
            };
            let destroyed = false;

            try {
                $el.select2('close');
            } catch (error) {
                // Continue to destroy the exact element even if it is detached.
            }

            try {
                $el.select2('destroy');
                destroyed = true;
            } catch (error) {
                // The conservative fallback below removes only Select2 state.
            }

            // Select2 normally removes these itself. Retaining the exact
            // references also clears a dropdown left behind by an interrupted
            // destroy without touching any other Select2 on the page.
            artifacts.$container?.remove();
            artifacts.$dropdown?.remove();
            artifacts.$dropdownContainer?.remove();

            if (!destroyed) {
                $el
                    .off('.select2')
                    .removeClass('select2-hidden-accessible')
                    .removeData('select2')
                    .removeAttr('data-select2-id')
                    .attr('aria-hidden', 'false');
            }
        },

        teardownSelect2() {
            const $el = this.rawJQuerySelect();

            this.disconnectOptionsObserver();

            if ($el) {
                $el.off('.glsSelect2');
                this.destroySelect2($el);
            }

            this.selectElement = null;
            this.$select = null;
            this.select2Instance = null;
        },

        destroy() {
            this.teardownSelect2();

            if (this.modalOpenedHandler) {
                window.removeEventListener('gls-select2-modal-opened', this.modalOpenedHandler);
                this.modalOpenedHandler = null;
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
