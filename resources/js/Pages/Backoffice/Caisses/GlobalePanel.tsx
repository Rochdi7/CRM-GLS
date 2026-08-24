import { useState } from 'react';
import Card from '@/Components/Shared/Card';
import EmptyState from '@/Components/Shared/EmptyState';
import DataTable from '@/Components/Tables/DataTable';
import type { CaisseGlobaleData } from '@/Types';

/**
 * « Caisse globale » — where the money of the active centre is: one card
 * per kind of account (physical tills, TPE, bank/virement, cheques — the
 * « Externe » kind is hidden for now, see GetCaisseGlobale::LABELS) and, under the selected card, every account of that kind with its
 * stored solde. Mirrors GetCaisseGlobale; every figure is an account's own
 * CaisseLedger balance — nothing is derived, nothing is counted twice.
 */
const CARD_BG: Record<string, string> = {
    'Caissière': 'bg-primary',
    TPE: 'bg-info',
    Virement: 'bg-purple',
    'Chèque': 'bg-pink',
    Externe: 'bg-warning',
};

export default function GlobalePanel({ data }: { data: CaisseGlobaleData }) {
    const [active, setActive] = useState<string>(data.cards[0]?.type ?? '');
    const rows = data.comptes[active] ?? [];
    const activeCard = data.cards.find((c) => c.type === active);

    return (
        <>
            <div className="row">
                {data.cards.map((card) => (
                    <div className="col-md-6 col-xl mb-3" key={card.type}>
                        <button
                            type="button"
                            onClick={() => setActive(card.type)}
                            className={`card w-100 text-white border-0 ${CARD_BG[card.type] ?? 'bg-secondary'}${active === card.type ? ' shadow' : ''}`}
                            style={{ opacity: active === card.type ? 1 : 0.85 }}
                        >
                            <div className="card-body text-center py-3">
                                <p className="mb-1 fw-semibold text-white">{card.label}</p>
                                <h5 className="mb-0 text-white">{Number(card.total).toFixed(2)} DH</h5>
                            </div>
                        </button>
                    </div>
                ))}
            </div>

            <Card bodyClassName="p-0 py-3">
                <ul className="nav nav-tabs nav-tabs-bottom px-3 mb-0">
                    {data.cards.map((card) => (
                        <li className="nav-item" key={card.type}>
                            <button
                                type="button"
                                className={`nav-link${active === card.type ? ' active' : ''}`}
                                onClick={() => setActive(card.type)}
                            >
                                {card.label}
                            </button>
                        </li>
                    ))}
                </ul>

                {rows.length === 0 ? (
                    <EmptyState title="Aucun compte" message={`Aucun compte « ${activeCard?.label ?? ''} » dans ce périmètre.`} icon="ti ti-cash" />
                ) : (
                    <DataTable
                        head={
                            <tr>
                                <th>Désignation</th>
                                <th>Centre</th>
                                <th>Responsable</th>
                                <th className="text-end">Montant</th>
                            </tr>
                        }
                    >
                        {rows.map((row) => (
                            <tr key={row.id}>
                                <td>
                                    <a href={row.showUrl}>{row.nom}</a>
                                </td>
                                <td>{row.centre ?? '—'}</td>
                                <td>{row.responsable ?? '—'}</td>
                                <td className="text-end fw-medium">{Number(row.solde).toFixed(2)} DH</td>
                            </tr>
                        ))}
                        <tr className="table-light">
                            <td colSpan={3} className="text-end text-muted">Total {activeCard?.label}</td>
                            <td className="text-end fw-semibold">{Number(activeCard?.total ?? 0).toFixed(2)} DH</td>
                        </tr>
                    </DataTable>
                )}
            </Card>
        </>
    );
}
