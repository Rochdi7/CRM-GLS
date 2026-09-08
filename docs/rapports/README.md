# Rapports

Archive des rapports d'audit, d'analyse et de migration du projet GLS CRM.

Ces documents sont un **historique daté** : ils décrivent ce qui a été mesuré,
audité ou migré à un moment donné. Ils ne décrivent pas l'état courant du code.
La documentation vivante (architecture, règles, procédures) reste dans `docs/`
et dans `CLAUDE.md`.

## Catégories

### `audits/` — audits généraux et autorisations
| Fichier | Sujet |
|---|---|
| `AUDIT.md` | Audit général du projet |
| `audit-production-2026-08-27.md` | Audit de la production (27/08/2026) |
| `authorization-audit.md` | Audit des rôles et permissions |
| `dashboard-authorization-audit.md` | Audit des autorisations du tableau de bord |

### `finance/` — audits financiers et caisse
| Fichier | Sujet |
|---|---|
| `financial-audit-2026-08-24.md` | Audit des invariants monétaires (24/08/2026) |
| `caisse-solde-especes-analysis.md` | Analyse du solde espèces des caisses |
| `phase-10-finance-audit.md` | Audit du module Finance (phase 10) |
| `phase-10-finance-mapping.md` | Cartographie Livewire → Inertia du module Finance |

### `performance/` — mesures et optimisations
| Fichier | Sujet |
|---|---|
| `PERFORMANCE_AUDIT.md` | Audit de performance (ère Livewire) |
| `PERFORMANCE_OPTIMIZATION_REPORT.md` | Rapport d'optimisation (ère Livewire) |
| `phase-11-performance-baseline.md` | Référence de performance post-migration |
| `phase12-performance-audit.md` | Audit de performance (phase 12) |
| `phase12-performance-report.md` | Rapport de performance (phase 12) |

### `postgres/` — migration vers PostgreSQL
| Fichier | Sujet |
|---|---|
| `POSTGRES_AUDIT.md` | Audit de compatibilité PostgreSQL |
| `POSTGRES_MIGRATION_REPORT.md` | Rapport de migration vers PostgreSQL |
| `POSTGRES_DOCUMENTATION_UPDATE_REPORT.md` | Mise à jour de la documentation |

### `migration-inertia/` — migration Livewire → Inertia + React
| Fichier | Sujet |
|---|---|
| `inertia-react-migration-audit.md` | Audit de la migration |
| `dashboard-livewire-to-inertia-map.md` | Cartographie du tableau de bord |
| `phase-5-read-pages-inventory.md` | Inventaire des pages en lecture seule |
| `phase-6-simple-crud-inventory.md` | Inventaire des CRUD simples |
| `phase-8-students-groups-inventory.md` | Inventaire Étudiants / Groupes |
| `phase-9-inscriptions-audit.md` | Audit du module Inscriptions |
| `phase-9-inscriptions-mapping.md` | Cartographie du module Inscriptions |
| `phase-11-active-routes-map.md` | Carte des routes actives |
| `phase-11-dependency-graph.md` | Graphe des dépendances |
| `phase-11-livewire-cleanup-audit.md` | Audit du retrait de Livewire |
| `phase-11-livewire-cleanup-report.md` | Rapport du retrait de Livewire |
| `phase-11-test-coverage-mapping.md` | Couverture de tests |
| `phase-11-manual-browser-checklist.md` | Vérifications manuelles navigateur |
| `phase-11-final-verification.md` | Vérification finale de la migration |

### `ui/` — thème PreSkool et interface
| Fichier | Sujet |
|---|---|
| `phase13-preskool-ui-audit.md` | Audit de l'interface PreSkool |
| `phase13-preskool-ui-mapping.md` | Cartographie de l'interface |
| `phase13-preskool-ui-report.md` | Rapport de mise en conformité UI |
| `preskool-react-reference-inventory.md` | Inventaire de la référence React |

## Convention

Un nouveau rapport se range dans la catégorie correspondante et s'ajoute au
tableau ci-dessus. Les rapports ne sont jamais supprimés : ce sont des traces
datées.
