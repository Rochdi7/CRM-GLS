/*
 * GLS CRM — Frontoffice scroll animations (vanilla, no library).
 *
 * Markup contract (styles live in resources/scss/frontoffice/_motion.scss):
 *   data-reveal            → fade-up when the element scrolls into view
 *   data-reveal="left|right|zoom|image"
 *   data-reveal-delay="200" → stagger, in ms (cards in a row: 200 / 400 / 600)
 *   data-parallax="0.15"   → element drifts at that fraction of the scroll
 *
 * Put data-reveal on a WRAPPER (the grid column), never on the element that
 * also carries a hover transform (.fo-card-hover): both animate `transform`,
 * and the revealed state would win over the hover lift.
 *
 * The hidden start state only exists under <html class="has-reveal">, which
 * the layout's inline script sets — so with JS off, or if this bundle never
 * loads, the content simply stays visible.
 */

const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function initReveal() {
    const targets = document.querySelectorAll('[data-reveal]');

    if (reduced || !('IntersectionObserver' in window)) {
        targets.forEach((el) => el.classList.add('is-revealed'));
        return;
    }

    const observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                const el = entry.target;
                const delay = parseInt(el.dataset.revealDelay ?? '0', 10);
                if (delay > 0) {
                    el.style.transitionDelay = `${delay}ms`;
                    // Drop the stagger once played, or it would delay every later transition.
                    el.addEventListener('transitionend', () => (el.style.transitionDelay = ''), { once: true });
                }
                el.classList.add('is-revealed');
                // Once only: an element that re-hides on scroll-up reads as a glitch.
                observer.unobserve(el);
            });
        },
        { threshold: 0.15, rootMargin: '0px 0px -8% 0px' },
    );

    targets.forEach((el) => observer.observe(el));
}

function initParallax() {
    const items = [...document.querySelectorAll('[data-parallax]')];
    if (reduced || items.length === 0) return;

    let ticking = false;

    const update = () => {
        ticking = false;
        const viewport = window.innerHeight;
        items.forEach((el) => {
            const rect = el.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > viewport) return;
            const speed = parseFloat(el.dataset.parallax) || 0.15;
            // Distance of the element's centre from the viewport's centre.
            const offset = rect.top + rect.height / 2 - viewport / 2;
            el.style.setProperty('--fo-parallax', `${(-offset * speed).toFixed(1)}px`);
        });
    };

    window.addEventListener(
        'scroll',
        () => {
            if (ticking) return;
            ticking = true;
            requestAnimationFrame(update);
        },
        { passive: true },
    );
    update();
}

export function initMotion() {
    window.__glsMotionReady = true;
    initReveal();
    initParallax();
}
