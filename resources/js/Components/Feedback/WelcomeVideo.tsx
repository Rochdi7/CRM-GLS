import { useEffect, useRef, useState } from 'react';
import { t } from '@/Lib/i18n';

interface WelcomeVideoProps {
    /** `flash.bienvenue` — true only on the first render after a login (pulled server-side). */
    show: boolean;
    name: string | null;
}

/** Hard ceiling, in case `ended` never fires (decode error, tab in background). */
const SAFETY_MS = 7000;

/**
 * Welcome clip played once after a backoffice login. Muted (browsers refuse
 * unmuted autoplay) and silent anyway; closes by itself at the end of the
 * clip, or on click / Escape. No Bootstrap JS (§3).
 */
export default function WelcomeVideo({ show, name }: WelcomeVideoProps) {
    const [open, setOpen] = useState(show);
    const [leaving, setLeaving] = useState(false);
    const videoRef = useRef<HTMLVideoElement>(null);

    useEffect(() => {
        if (show) setOpen(true);
    }, [show]);

    useEffect(() => {
        if (!open) return;

        function onKey(event: KeyboardEvent) {
            if (event.key === 'Escape') close();
        }
        document.addEventListener('keydown', onKey);
        const timer = window.setTimeout(close, SAFETY_MS);
        // Autoplay can still be refused (data saver, policy): never leave a frozen overlay.
        videoRef.current?.play().catch(close);

        return () => {
            document.removeEventListener('keydown', onKey);
            window.clearTimeout(timer);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    function close() {
        setLeaving(true);
        window.setTimeout(() => {
            setOpen(false);
            setLeaving(false);
        }, 300);
    }

    if (!open) return null;

    return (
        <div className={`gls-welcome${leaving ? ' is-leaving' : ''}`} role="dialog" aria-label={t('Welcome')} onClick={close}>
            <div className="gls-welcome-box">
                <video
                    ref={videoRef}
                    className="gls-welcome-video"
                    src="/videos/bienvenue-gls.mp4"
                    autoPlay
                    muted
                    playsInline
                    preload="auto"
                    onEnded={close}
                />
                <p className="gls-welcome-text">
                    {name ? t('Welcome :name to GLS CRM', { name }) : t('Welcome to GLS CRM')}
                </p>
                <button type="button" className="gls-welcome-close" aria-label={t('Close')} onClick={close}>
                    <i className="ti ti-x" />
                </button>
            </div>
        </div>
    );
}
