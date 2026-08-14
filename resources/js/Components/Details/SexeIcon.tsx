interface SexeIconProps {
    sexe: string | null;
}

/** Gender icon, matching components/backoffice/ui/sexe-icon.blade.php. */
export default function SexeIcon({ sexe }: SexeIconProps) {
    if (sexe === 'Homme') {
        return <i className="ti ti-man fs-16 text-primary" title="Homme" />;
    }
    if (sexe === 'Femme') {
        return <i className="ti ti-woman fs-16 text-pink" title="Femme" />;
    }
    return <>—</>;
}
