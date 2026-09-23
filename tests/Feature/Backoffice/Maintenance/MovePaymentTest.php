<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Maintenance;

use App\Models\AnneeScolaire;
use App\Models\Caisse;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Presence;
use App\Models\Seance;
use App\Models\Student;
use App\Models\User;
use App\Support\Access\HiddenAccount;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * « Déplacer un paiement » — /backoffice/move-payment (21–23/09/2026).
 *
 * L'écran de ce qui s'est fait trois fois en PuTTY en une semaine : un
 * paiement encaissé au nom du MAUVAIS étudiant est réaffecté au frais de
 * la bonne personne. Ce que ces tests figent :
 *
 *   - l'accès est une IDENTITÉ (le compte de maintenance seul, le CEO
 *     super-admin reçoit 403) ;
 *   - AUCUN argent ne bouge : montant, date, agent, caisse et
 *     `caisses.solde` sont relus à l'identique après chaque geste ;
 *   - la purge n'efface QUE des « Absent » — une seule ligne « Présent »
 *     refuse le lot entier ;
 *   - sans purge demandée, la garde « zéro présence » refuse comme pour
 *     n'importe qui ;
 *   - une AVANCE va sur le frais DÉSIGNÉ, jamais au-delà de son reste dû ;
 *   - pour une ligne à frais, la cible est DÉTECTÉE — un frais fourni
 *     est refusé, jamais ignoré en silence.
 */
final class MovePaymentTest extends TestCase
{
    use RefreshDatabase;

    private const URL = '/backoffice/move-payment';

    private Etablissement $centre;

    private AnneeScolaire $annee;

    private Group $group;

    private Employee $agent;

    private Caisse $caisse;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->centre = Etablissement::factory()->create();
        $this->annee = AnneeScolaire::create([
            'nom' => '2026/2027', 'date_debut' => '2026-09-01', 'date_fin' => '2027-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->group = Group::factory()->create([
            'statut' => Group::STATUT_EN_FORMATION,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
        ]);

        $this->agent = Employee::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->caisse = $this->agent->till()->first() ?? Caisse::create([
            'nom' => 'Caisse test',
            'type' => Caisse::TYPE_CAISSIERE,
            'responsable_employee_id' => $this->agent->id,
            'solde' => 0,
            'statut' => 'Active',
        ]);
        // Un solde non nul, pour prouver qu'il ne bouge pas.
        Caisse::query()->whereKey($this->caisse->id)->update(['solde' => 19600]);
    }

    private function utilisateur(string $email): User
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole('super-admin');
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function mainteneur(): User
    {
        return $this->utilisateur(HiddenAccount::EMAIL);
    }

    /**
     * @param  array<string, float>  $frais  nom => montant
     * @return array{0: Inscription, 1: array<string, InscriptionFee>}
     */
    private function inscription(Student $student, array $frais, string $ref): array
    {
        $ins = Inscription::create([
            'reference' => $ref,
            'student_id' => $student->id,
            'group_id' => $this->group->id,
            'etablissement_id' => $this->centre->id,
            'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE,
            'date_inscription' => '2026-09-02',
        ]);

        $lignes = [];
        foreach ($frais as $nom => $montant) {
            $lignes[$nom] = InscriptionFee::create([
                'inscription_id' => $ins->id,
                'nom' => $nom,
                'montant_initial' => $montant,
                'montant' => $montant,
                'date_echeance' => '2026-09-07',
                'statut' => InscriptionFee::STATUT_NON_PAYE,
            ]);
        }

        return [$ins, $lignes];
    }

    private function paiement(Student $student, ?InscriptionFee $fee, float $montant, string $ref): Encaissement
    {
        $enc = Encaissement::create([
            'reference' => $ref,
            'etablissement_id' => $this->centre->id,
            'student_id' => $student->id,
            'inscription_fee_id' => $fee?->id,
            'montant' => $montant,
            'methode' => Encaissement::METHODE_ESPECES,
            'date_paiement' => '2026-09-07',
            'caisse_id' => $this->caisse->id,
            'agent_id' => $this->agent->id,
        ]);

        if ($fee !== null) {
            $fee->update(['statut' => $montant >= (float) $fee->montant ? InscriptionFee::STATUT_PAYE : InscriptionFee::STATUT_PAYE_PARTIELLEMENT]);
        }

        return $enc;
    }

    /** @param list<string> $statuts un appel par séance, dans l'ordre des dates */
    private function appels(Student $student, array $statuts): void
    {
        foreach ($statuts as $i => $statut) {
            $seance = Seance::firstOrCreate(
                ['group_id' => $this->group->id, 'date_seance' => sprintf('2026-09-%02d', 7 + $i)],
                ['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id, 'statut' => Seance::STATUT_EFFECTUEE],
            );
            Presence::create(['seance_id' => $seance->id, 'student_id' => $student->id, 'statut' => $statut]);
        }
    }

    /**
     * Tout ce qu'un déplacement ne doit JAMAIS toucher : la ligne (montant,
     * date, agent, caisse, méthode), le solde de la caisse ET son journal —
     * un mouvement de caisse passe par CaisseLedger (`solde_movement`), et
     * ce geste n'en écrit aucun.
     *
     * @return array{montant: string, date: string, agent: int, caisse: int, methode: string, solde: string, mouvements: int}
     */
    private function invariants(Encaissement $enc): array
    {
        $enc->refresh();

        return [
            'montant' => (string) $enc->montant,
            'date' => (string) $enc->date_paiement,
            'agent' => $enc->agent_id,
            'caisse' => $enc->caisse_id,
            'methode' => $enc->methode,
            'solde' => (string) Caisse::query()->whereKey($this->caisse->id)->value('solde'),
            'mouvements' => \Illuminate\Support\Facades\DB::table('activity_log')
                ->where('log_name', 'caisse')->where('event', 'solde_movement')->count(),
        ];
    }

    // ── Accès ──────────────────────────────────────────────────────────

    public function test_le_compte_de_maintenance_ouvre_la_page(): void
    {
        $this->actingAs($this->mainteneur())
            ->get(self::URL)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/MovePayment/Index')
                ->where('diagnostic', null));
    }

    /** Le cœur de la règle : super-admin ne suffit PAS. */
    public function test_un_super_admin_ordinaire_est_refuse(): void
    {
        $ceo = $this->utilisateur('rafik@glszentrum.com');
        $this->assertTrue($ceo->hasRole('super-admin'));

        $this->actingAs($ceo)->get(self::URL)->assertForbidden();
        $this->actingAs($ceo)->post(self::URL, [
            'encaissement' => 'ENC-X', 'inscription' => 'INS-X', 'motif' => 'un motif suffisant',
        ])->assertForbidden();
    }

    public function test_le_compte_staff_du_mainteneur_est_refuse(): void
    {
        $this->actingAs($this->utilisateur(HiddenAccount::STAFF_EMAIL))->get(self::URL)->assertForbidden();
    }

    // ── Ligne à frais ──────────────────────────────────────────────────

    public function test_une_ligne_a_frais_est_transferee_apres_purge_des_absents(): void
    {
        $fantome = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisSource] = $this->inscription($fantome, ['Frais de Septembre' => 1300], 'INS-SRC');
        [$cible, $fraisCible] = $this->inscription($reel, ['Frais de Septembre' => 1300], 'INS-CIB');
        $enc = $this->paiement($fantome, $fraisSource['Frais de Septembre'], 1300, 'ENC-MV-1');
        $this->appels($fantome, [Presence::STATUT_ABSENT, Presence::STATUT_ABSENT, Presence::STATUT_ABSENT]);
        $this->appels($reel, [Presence::STATUT_PRESENT, Presence::STATUT_PRESENT]);

        $avant = $this->invariants($enc);

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-1',
                'inscription' => 'INS-CIB',
                'purger_presences' => true,
                'motif' => 'Dossier saisi sur le mauvais nom au guichet.',
            ])
            ->assertRedirect(self::URL.'?encaissement=ENC-MV-1&inscription=INS-CIB')
            ->assertSessionHas('success');

        $enc->refresh();
        $this->assertSame($reel->id, $enc->student_id);
        $this->assertSame($fraisCible['Frais de Septembre']->id, $enc->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $fraisCible['Frais de Septembre']->fresh()->statut);
        $this->assertSame(InscriptionFee::STATUT_NON_PAYE, $fraisSource['Frais de Septembre']->fresh()->statut);

        // Les fantômes sont partis, les vrais appels de la cible restent.
        $this->assertSame(0, Presence::query()->where('student_id', $fantome->id)->count());
        $this->assertSame(2, Presence::query()->where('student_id', $reel->id)->count());

        // ⚠ AUCUN argent n'a bougé.
        $this->assertSame($avant, $this->invariants($enc));

        $this->assertDatabaseHas('activity_log', ['log_name' => 'presence', 'event' => 'presences_fantomes_supprimees']);
        $this->assertDatabaseHas('activity_log', ['log_name' => 'encaissement', 'event' => 'fee_transferred_between_students', 'subject_id' => $enc->id]);
    }

    public function test_sans_purge_les_appels_bloquent_comme_pour_tout_le_monde(): void
    {
        $fantome = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisSource] = $this->inscription($fantome, ['Frais de Septembre' => 1300], 'INS-SRC');
        $this->inscription($reel, ['Frais de Septembre' => 1300], 'INS-CIB');
        $enc = $this->paiement($fantome, $fraisSource['Frais de Septembre'], 1300, 'ENC-MV-2');
        $this->appels($fantome, [Presence::STATUT_ABSENT]);

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-2', 'inscription' => 'INS-CIB',
                'motif' => 'Dossier saisi sur le mauvais nom au guichet.',
            ])
            ->assertSessionHasErrors('encaissement_id');

        $this->assertSame($fantome->id, $enc->fresh()->student_id);
        $this->assertSame(1, Presence::query()->where('student_id', $fantome->id)->count());
    }

    /** ⚠ LA borne de la purge : une vraie présence prouve que la personne EST venue. */
    public function test_une_presence_reelle_refuse_la_purge_et_rien_ne_bouge(): void
    {
        $eleve = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $autre = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisSource] = $this->inscription($eleve, ['Frais de Septembre' => 1300], 'INS-SRC');
        $this->inscription($autre, ['Frais de Septembre' => 1300], 'INS-CIB');
        $enc = $this->paiement($eleve, $fraisSource['Frais de Septembre'], 1300, 'ENC-MV-3');
        $this->appels($eleve, [Presence::STATUT_ABSENT, Presence::STATUT_PRESENT, Presence::STATUT_ABSENT]);

        $avant = $this->invariants($enc);

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-3', 'inscription' => 'INS-CIB',
                'purger_presences' => true,
                'motif' => 'Tentative de purge sur un vrai étudiant.',
            ])
            ->assertSessionHasErrors('purger_presences');

        // Refus ENTIER : ni transfert, ni ligne effacée (les deux « Absent » compris).
        $this->assertSame($eleve->id, $enc->fresh()->student_id);
        $this->assertSame(3, Presence::query()->where('student_id', $eleve->id)->count());
        $this->assertSame($avant, $this->invariants($enc));
    }

    public function test_le_frais_cible_est_detecte_jamais_choisi(): void
    {
        $fantome = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisSource] = $this->inscription($fantome, ['Frais de Septembre' => 1300], 'INS-SRC');
        [, $fraisCible] = $this->inscription($reel, ['Frais de Septembre' => 1300, "Frais d'Octobre" => 1300], 'INS-CIB');
        $enc = $this->paiement($fantome, $fraisSource['Frais de Septembre'], 1300, 'ENC-MV-4');

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-4', 'inscription' => 'INS-CIB',
                'inscription_fee_id' => $fraisCible["Frais d'Octobre"]->id,
                'motif' => 'On tente de viser Octobre à la main.',
            ])
            ->assertSessionHasErrors('inscription_fee_id');

        $this->assertSame($fantome->id, $enc->fresh()->student_id);
    }

    // ── Avance ─────────────────────────────────────────────────────────

    public function test_une_avance_est_affectee_au_frais_designe_sans_bouger_la_caisse(): void
    {
        $soeur = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisCible] = $this->inscription($reel, ['Frais de Septembre' => 1200], 'INS-CIB');
        $septembre = $fraisCible['Frais de Septembre'];
        // 700 déjà payés par la vraie personne, 500 restent dus.
        $this->paiement($reel, $septembre, 700, 'ENC-MV-5A');
        $avance = $this->paiement($soeur, null, 500, 'ENC-MV-5B');

        $avant = $this->invariants($avance);

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-5B', 'inscription' => 'INS-CIB',
                'inscription_fee_id' => $septembre->id,
                'motif' => 'Avance remise pour la sœur, encaissée au mauvais nom.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $avance->refresh();
        $this->assertSame($reel->id, $avance->student_id);
        $this->assertSame($septembre->id, $avance->inscription_fee_id);
        $this->assertSame(InscriptionFee::STATUT_PAYE, $septembre->fresh()->statut);
        $this->assertSame(1200.0, $septembre->fresh()->montantPaye());
        $this->assertSame($avant, $this->invariants($avance));

        $this->assertDatabaseHas('activity_log', ['log_name' => 'encaissement', 'event' => 'avance_affectee_autre_etudiant', 'subject_id' => $avance->id]);
    }

    public function test_une_avance_au_dela_du_reste_du_est_refusee(): void
    {
        $soeur = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisCible] = $this->inscription($reel, ['Frais de Septembre' => 1200], 'INS-CIB');
        $this->paiement($reel, $fraisCible['Frais de Septembre'], 700, 'ENC-MV-6A');
        $avance = $this->paiement($soeur, null, 600, 'ENC-MV-6B');

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-6B', 'inscription' => 'INS-CIB',
                'inscription_fee_id' => $fraisCible['Frais de Septembre']->id,
                'motif' => 'Six cents sur un reste de cinq cents.',
            ])
            ->assertSessionHasErrors('inscription_fee_id');

        $this->assertSame($soeur->id, $avance->fresh()->student_id);
        $this->assertNull($avance->fresh()->inscription_fee_id);
    }

    public function test_une_avance_sans_frais_designe_est_refusee(): void
    {
        $soeur = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $this->inscription($reel, ['Frais de Septembre' => 1200], 'INS-CIB');
        $avance = $this->paiement($soeur, null, 500, 'ENC-MV-7');

        $this->actingAs($this->mainteneur())
            ->post(self::URL, [
                'encaissement' => 'ENC-MV-7', 'inscription' => 'INS-CIB',
                'motif' => 'Aucun frais désigné pour cette avance.',
            ])
            ->assertSessionHasErrors('inscription_fee_id');

        $this->assertNull($avance->fresh()->inscription_fee_id);
    }

    // ── Diagnostic ─────────────────────────────────────────────────────

    public function test_le_diagnostic_nomme_les_appels_le_frais_detecte_et_ce_qui_ne_bougera_pas(): void
    {
        $fantome = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisSource] = $this->inscription($fantome, ['Frais de Septembre' => 1300], 'INS-SRC');
        [, $fraisCible] = $this->inscription($reel, ['Frais de Septembre' => 1300], 'INS-CIB');
        $this->paiement($fantome, $fraisSource['Frais de Septembre'], 1300, 'ENC-MV-8');
        $this->appels($fantome, [Presence::STATUT_ABSENT, Presence::STATUT_ABSENT]);

        $this->actingAs($this->mainteneur())
            ->get(self::URL.'?encaissement=enc-mv-8&inscription=ins-cib')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Backoffice/MovePayment/Index')
                ->where('diagnostic.erreur', null)
                ->where('diagnostic.mode', 'frais')
                ->where('diagnostic.source.montant', '1300.00')
                ->where('diagnostic.source.date', '2026-09-07')
                ->where('diagnostic.source.frais', 'Frais de Septembre')
                ->has('diagnostic.presences', 2)
                ->where('diagnostic.purgeable', true)
                ->where('diagnostic.fraisDetecte.id', $fraisCible['Frais de Septembre']->id)
                ->where('diagnostic.blocages', []));
    }

    /** Les frais de la cible : à payer d'abord, puis soldés, puis masqués — l'opérateur cherche une cible. */
    public function test_le_diagnostic_classe_les_frais_cible_a_payer_puis_payes_puis_masques(): void
    {
        $fantome = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        $reel = Student::factory()->create(['etablissement_id' => $this->centre->id]);
        [, $fraisSource] = $this->inscription($fantome, ['Frais de Septembre' => 1300], 'INS-SRC');
        [, $fraisCible] = $this->inscription($reel, [
            'Frais de Janvier' => 1300,   // masqué
            'Frais de Septembre' => 1300, // payé
            "Frais d'Octobre" => 1300,    // à payer
        ], 'INS-CIB');
        $fraisCible['Frais de Janvier']->update(['masque_le' => now()]);
        $this->paiement($reel, $fraisCible['Frais de Septembre'], 1300, 'ENC-MV-9A');
        $this->paiement($fantome, $fraisSource['Frais de Septembre'], 1300, 'ENC-MV-9B');

        $this->actingAs($this->mainteneur())
            ->get(self::URL.'?encaissement=ENC-MV-9B&inscription=INS-CIB')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('diagnostic.cible.frais.0.nom', "Frais d'Octobre")
                ->where('diagnostic.cible.frais.1.nom', 'Frais de Septembre')
                ->where('diagnostic.cible.frais.2.nom', 'Frais de Janvier')
                ->where('diagnostic.cible.frais.2.masque', true));
    }

    public function test_le_diagnostic_dit_quand_une_reference_est_introuvable(): void
    {
        $this->actingAs($this->mainteneur())
            ->get(self::URL.'?encaissement=ENC-NOPE&inscription=INS-NOPE')
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('diagnostic.erreur', 'Aucun paiement ne porte la référence « ENC-NOPE ».'));
    }
}
