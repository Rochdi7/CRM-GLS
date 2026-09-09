import { useEffect, useRef } from 'react';

/**
 * Ouvre le modal de création d'une page de liste quand l'URL porte
 * `?nouveau=1` — le raccourci « Actions rapides » du tableau de bord amène
 * l'utilisateur sur la page ET lui ouvre le formulaire d'un seul clic.
 *
 * ⚠ Ce n'est qu'un confort d'interface (§5) : le paramètre ne contourne
 * rien. `enabled` doit reprendre exactement la garde qui dessine déjà le
 * bouton « Ajouter » (ex. `permissions.create`), sinon on ouvre un modal
 * dont le serveur refusera l'envoi.
 *
 * Le paramètre est retiré de l'URL par `history.replaceState` dès qu'il est
 * consommé — sans cela un rechargement partiel Inertia (recherche, filtre,
 * pagination) le relirait et rouvrirait le modal en pleine saisie. Le
 * `useRef` couvre le même risque à l'intérieur d'un même montage.
 */
export function useAutoOpenCreate(open: () => void, enabled: boolean = true): void {
    const consumed = useRef(false);

    useEffect(() => {
        if (consumed.current || ! enabled) {
            return;
        }

        const url = new URL(window.location.href);

        if (url.searchParams.get('nouveau') !== '1') {
            return;
        }

        consumed.current = true;
        url.searchParams.delete('nouveau');
        window.history.replaceState({}, '', url.pathname + url.search + url.hash);
        open();
        // `open` change à chaque rendu (fonction déclarée dans le composant) :
        // ne dépendre que de `enabled`, la garde `consumed` fait le reste.
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [enabled]);
}

export default useAutoOpenCreate;
