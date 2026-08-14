interface SexeIconProps {
    sexe: string | null;
}

/** Gender icon, matching components/backoffice/ui/sexe-icon.blade.php. */
export default function SexeIcon({ sexe }: SexeIconProps) {
    if (sexe === 'Homme') {
        return <i className="fa fa-mars fs-16 text-primary" title="Homme" />;
    }
    if (sexe === 'Femme') {
        return <i className="fa fa-venus fs-16 text-pink" title="Femme" />;
    }
    return <>—</>;
}
