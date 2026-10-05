import { useEffect, useRef, useState } from 'react';
import { t } from '@/Lib/i18n';

interface WelcomeVideoProps {
    /** One-time flash flag (pulled server-side): true only on the render that should play the clip. */
    show: boolean;
    /** Clip under `public/videos/`. */
    src: string;
    /** Caption under the clip. */
    text: string;
}

/** Fallback ceiling until the clip's duration is known (decode error, tab in background). */
const SAFETY_MS = 7000;

/**
 * One-shot clip overlay — the welcome clip after a login, the cancellation
 * clip after an inscription is cancelled. Muted (browsers refuse unmuted
 * autoplay); closes by itself at the end of the clip, or on click / Escape.
 * No Bootstrap JS (§3).
 */
export default function WelcomeVideo({ show, src, text }: WelcomeVideoProps) {
    const [open, setOpen] = useState(show);
    const [leaving, setLeaving] = useState(false);
    const videoRef = useRef<HTMLVideoElement>(null);
    const timerRef = useRef<number | undefined>(undefined);

    useEffect(() => {
        if (show) setOpen(true);
    }, [show]);

    useEffect(() => {
        if (!open) return;

        function onKey(event: KeyboardEvent) {
            if (event.key === 'Escape') close();
        }
        document.addEventListener('keydown', onKey);
        timerRef.current = window.setTimeout(close, SAFETY_MS);
        // Autoplay can still be refused (data saver, policy): never leave a frozen overlay.
        videoRef.current?.play().catch(close);

        return () => {
            document.removeEventListener('keydown', onKey);
            window.clearTimeout(timerRef.current);
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    /** A clip longer than the fallback ceiling must not be cut: re-arm on its real length. */
    function onLoadedMetadata() {
        const duration = videoRef.current?.duration;
        if (!duration || !Number.isFinite(duration)) return;
        window.clearTimeout(timerRef.current);
        timerRef.current = window.setTimeout(close, duration * 1000 + 1500);
    }

    function close() {
        setLeaving(true);
        window.setTimeout(() => {
            setOpen(false);
            setLeaving(false);
        }, 300);
    }

    if (!open) return null;

    return (
        <div className={`gls-welcome${leaving ? ' is-leaving' : ''}`} role="dialog" aria-label={text} onClick={close}>
            <div className="gls-welcome-box">
                <video
                    ref={videoRef}
                    className="gls-welcome-video"
                    src={src}
                    autoPlay
                    muted
                    playsInline
                    preload="auto"
                    onLoadedMetadata={onLoadedMetadata}
                    onEnded={close}
                />
                <p className="gls-welcome-text">{text}</p>
                <button type="button" className="gls-welcome-close" aria-label={t('Close')} onClick={close}>
                    <i className="ti ti-x" />
                </button>
            </div>
        </div>
    );
}
