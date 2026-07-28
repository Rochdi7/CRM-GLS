/*
 * GLS CRM — Backoffice theme behaviour.
 *
 * Adapted from PreSkool `theme-script.js` (kept as reference at
 * public/assets/preskool/js/theme-script.js — NOT loaded).
 * Differences from the original:
 *  - The theme-customizer offcanvas markup is rendered server-side by the
 *    Blade component <x-backoffice.layout.theme-settings /> (the original
 *    injected it via JS with broken relative image paths).
 *  - Every DOM lookup is guarded, so this file is safe on guest pages too.
 *
 * Persists to localStorage: theme, sidebarTheme, color, layout, topbar.
 */

const HTML = document.documentElement;

/** Restore saved appearance immediately (before first paint if possible). */
export function restoreThemeAttributes() {
    HTML.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
    HTML.setAttribute('data-sidebar', localStorage.getItem('sidebarTheme') || 'light');
    HTML.setAttribute('data-color', localStorage.getItem('color') || 'primary');
    HTML.setAttribute('data-topbar', localStorage.getItem('topbar') || 'white');
    HTML.setAttribute('data-layout', localStorage.getItem('layout') || 'default');
}

function applyLayoutBodyClasses(layout) {
    if (layout === 'mini') {
        document.body.classList.add('mini-sidebar');
        document.body.classList.remove('layout-box-mode');
    } else if (layout === 'box') {
        document.body.classList.add('layout-box-mode');
        document.body.classList.remove('mini-sidebar');
    } else {
        document.body.classList.remove('layout-box-mode', 'mini-sidebar');
    }
}

function setThemeSettings(theme, sidebarTheme, color, layout, topbar) {
    HTML.setAttribute('data-theme', theme);
    HTML.setAttribute('data-sidebar', sidebarTheme);
    HTML.setAttribute('data-color', color);
    HTML.setAttribute('data-layout', layout);
    HTML.setAttribute('data-topbar', topbar);

    applyLayoutBodyClasses(layout);

    localStorage.setItem('theme', theme);
    localStorage.setItem('sidebarTheme', sidebarTheme);
    localStorage.setItem('color', color);
    localStorage.setItem('layout', layout);
    localStorage.setItem('topbar', topbar);
}

function checkedValue(name, fallback) {
    const input = document.querySelector(`input[name="${name}"]:checked`);
    return input ? input.value : fallback;
}

function syncRadios() {
    const map = {
        [`${localStorage.getItem('theme') || 'light'}Theme`]: true,
        [`${localStorage.getItem('sidebarTheme') || 'light'}Sidebar`]: true,
        [`${localStorage.getItem('color') || 'primary'}Color`]: true,
        [`${localStorage.getItem('layout') || 'default'}Layout`]: true,
        [`${localStorage.getItem('topbar') || 'white'}Topbar`]: true,
    };
    Object.keys(map).forEach((id) => {
        const el = document.getElementById(id);
        if (el) {
            el.checked = true;
        }
    });
}

function initDarkModeToggles() {
    const darkToggle = document.getElementById('dark-mode-toggle');
    const lightToggle = document.getElementById('light-mode-toggle');
    if (!darkToggle || !lightToggle) {
        return;
    }

    const activate = (dark) => {
        HTML.setAttribute('data-theme', dark ? 'dark' : 'light');
        localStorage.setItem('theme', dark ? 'dark' : 'light');
        darkToggle.classList.toggle('activate', !dark);
        lightToggle.classList.toggle('activate', dark);
        const radio = document.getElementById(dark ? 'darkTheme' : 'lightTheme');
        if (radio) {
            radio.checked = true;
        }
    };

    activate((localStorage.getItem('theme') || 'light') === 'dark');
    darkToggle.addEventListener('click', (e) => { e.preventDefault(); activate(true); });
    lightToggle.addEventListener('click', (e) => { e.preventDefault(); activate(false); });
}

function initThemeSettingsPanel() {
    const names = ['theme', 'sidebar', 'color', 'LayoutTheme', 'topbar'];
    const onChange = () => setThemeSettings(
        checkedValue('theme', 'light'),
        checkedValue('sidebar', 'light'),
        checkedValue('color', 'primary'),
        checkedValue('LayoutTheme', 'default'),
        checkedValue('topbar', 'white'),
    );

    names.forEach((name) => {
        document.querySelectorAll(`input[name="${name}"]`).forEach((radio) => {
            radio.addEventListener('change', onChange);
        });
    });

    const resetButton = document.getElementById('resetbutton');
    if (resetButton) {
        resetButton.addEventListener('click', (e) => {
            e.preventDefault();
            setThemeSettings('light', 'light', 'primary', 'default', 'white');
            syncRadios();
        });
    }
}

export function initTheme() {
    restoreThemeAttributes();
    applyLayoutBodyClasses(localStorage.getItem('layout') || 'default');
    syncRadios();
    initDarkModeToggles();
    initThemeSettingsPanel();
}
