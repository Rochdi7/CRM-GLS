interface MobileSidebarOverlayProps {
    visible: boolean;
    onClose: () => void;
}

export default function MobileSidebarOverlay({ visible, onClose }: MobileSidebarOverlayProps) {
    if (!visible) {
        return null;
    }

    return (
        <div
            className="sidebar-overlay opened"
            onClick={onClose}
            role="presentation"
        />
    );
}
