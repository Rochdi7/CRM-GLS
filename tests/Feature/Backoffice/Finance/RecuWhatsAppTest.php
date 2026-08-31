<?php

declare(strict_types=1);

namespace Tests\Feature\Backoffice\Finance;

use App\Domain\Payments\Support\RecuWhatsAppLink;
use App\Domain\Payments\Support\WhatsAppNumber;
use App\Models\AnneeScolaire;
use App\Models\Employee;
use App\Models\Encaissement;
use App\Models\Etablissement;
use App\Models\Group;
use App\Models\Inscription;
use App\Models\InscriptionFee;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Envoi du reçu par WhatsApp (31/08/2026).
 *
 * ⚠ Le lien click-to-chat ne peut PAS joindre de fichier : le PDF voyage
 * comme URL SIGNÉE dans le texte. Ces tests verrouillent donc surtout la
 * sécurité de cette URL publique — c'est elle qui expose un document, pas le
 * lien WhatsApp lui-même.
 */
final class RecuWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    private AnneeScolaire $annee;

    private Etablissement $centre;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->annee = AnneeScolaire::create([
            'nom' => '2025/2026', 'date_debut' => '2025-09-01', 'date_fin' => '2026-08-31',
            'par_defaut' => true, 'inscription_ouverte' => true,
        ]);
        $this->centre = Etablissement::factory()->create();

        // L'env de test herite d'APP_URL=127.0.0.1 : le garde
        // pdfUrlIsPubliclyReachable() refuserait alors tout envoi. On simule
        // la production, ou APP_URL est le vrai domaine.
        config(['app.url' => 'https://crm.gls-sprachzentrum.ma']);
        URL::forceRootUrl('https://crm.gls-sprachzentrum.ma');
    }

    private function userWith(string ...$permissions): User
    {
        $user = User::factory()->create();
        foreach ([...$permissions, 'centers.access-all'] as $p) {
            $user->givePermissionTo($p);
        }
        Employee::factory()->create(['user_id' => $user->id, 'etablissement_id' => $this->centre->id]);

        return $user->fresh();
    }

    private function paymentFor(User $user, array $studentAttributes = []): Encaissement
    {
        $student = Student::factory()->create($studentAttributes + [
            'etablissement_id' => $this->centre->id,
        ]);

        $group = Group::factory()->create(['etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id]);
        $inscription = Inscription::create([
            'reference' => 'INS-'.fake()->unique()->numerify('#####'),
            'student_id' => $student->id, 'group_id' => $group->id,
            'etablissement_id' => $this->centre->id, 'annee_scolaire_id' => $this->annee->id,
            'statut' => Inscription::STATUT_ACTIVE, 'date_inscription' => '2025-09-15',
            'montant_total' => 1000,
        ]);
        $fee = InscriptionFee::create([
            'inscription_id' => $inscription->id, 'nom' => 'Frais de Juillet',
            'montant_initial' => 1000, 'montant' => 1000,
            'date_echeance' => '2025-07-31', 'statut' => InscriptionFee::STATUT_NON_PAYE,
        ]);

        $this->actingAs($user)->post(route('backoffice.encaissements.store'), [
            'student_id' => $student->id,
            'inscription_id' => $inscription->id,
            'caisse_id' => $user->employee->caisses()->first()->id,
            'date_paiement' => '2025-09-20',
            'payment_lines' => [
                ['fee_id' => $fee->id, 'montant' => '1000', 'methode' => 'Espèces', 'date_paiement' => '2025-09-20'],
            ],
        ])->assertSessionDoesntHaveErrors();

        return Encaissement::where('inscription_fee_id', $fee->id)->firstOrFail();
    }

    // --- Normalisation du numéro -------------------------------------------

    public function test_phone_numbers_are_normalised_to_digits_with_a_country_code(): void
    {
        $this->assertSame('212648430612', WhatsAppNumber::normalize('+212648430612'));
        $this->assertSame('212648430612', WhatsAppNumber::normalize('0648430612'));
        $this->assertSame('212648430612', WhatsAppNumber::normalize('00212648430612'));
        $this->assertSame('212648430612', WhatsAppNumber::normalize('+212 648 43 06 12'));
        // Numéro étranger : l'indicatif présent est conservé tel quel.
        $this->assertSame('33651222252', WhatsAppNumber::normalize('33651222252'));
        // Valeurs non exploitables de l'ancien CRM.
        $this->assertNull(WhatsAppNumber::normalize('-'));
        $this->assertNull(WhatsAppNumber::normalize(''));
        $this->assertNull(WhatsAppNumber::normalize(null));
        $this->assertNull(WhatsAppNumber::normalize('12345'));
    }

    public function test_the_whatsapp_column_wins_but_telephone_is_the_fallback(): void
    {
        $withBoth = Student::factory()->make(['whatsapp' => '+212600000001', 'telephone' => '+212600000002']);
        $this->assertSame('212600000001', WhatsAppNumber::forStudent($withBoth));

        // Le cas de TOUTE la production au 31/08/2026 : colonne whatsapp vide.
        $telOnly = Student::factory()->make(['whatsapp' => null, 'telephone' => '+212600000002']);
        $this->assertSame('212600000002', WhatsAppNumber::forStudent($telOnly));

        $neither = Student::factory()->make(['whatsapp' => null, 'telephone' => null]);
        $this->assertNull(WhatsAppNumber::forStudent($neither));
    }

    // --- L'endpoint backoffice ---------------------------------------------

    public function test_it_returns_a_click_to_chat_url_carrying_the_signed_pdf_link(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $encaissement = $this->paymentFor($user, ['telephone' => '+212648430612']);

        $body = $this->actingAs($user)
            ->getJson(route('backoffice.encaissements.recu.whatsapp', $encaissement))
            ->assertOk()
            ->json();

        $this->assertSame('212648430612', $body['phone']);
        $this->assertStringStartsWith('https://api.whatsapp.com/send/?', $body['url']);

        parse_str(parse_url($body['url'], PHP_URL_QUERY) ?: '', $query);
        $this->assertSame('212648430612', $query['phone']);

        // Le PDF est dans le TEXTE (l'API ne sait pas joindre de fichier),
        // et c'est une URL signée — donc ouvrable par l'étudiant.
        $this->assertStringContainsString($encaissement->reference, $query['text']);
        $this->assertStringContainsString('/recu/'.$encaissement->id, $query['text']);
        $this->assertStringContainsString('signature=', $query['text']);
    }

    public function test_a_student_without_any_phone_number_is_reported_not_silently_linked(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $encaissement = $this->paymentFor($user, ['telephone' => null, 'whatsapp' => null]);

        $this->actingAs($user)
            ->getJson(route('backoffice.encaissements.recu.whatsapp', $encaissement))
            ->assertStatus(422);
    }

    public function test_building_the_link_requires_payments_view(): void
    {
        $owner = $this->userWith('payments.view', 'payments.create');
        $encaissement = $this->paymentFor($owner, ['telephone' => '+212648430612']);

        $this->actingAs($this->userWith('dashboard.view'))
            ->getJson(route('backoffice.encaissements.recu.whatsapp', $encaissement))
            ->assertForbidden();
    }

    // --- L'URL publique du PDF (la surface exposée) ------------------------

    public function test_the_signed_link_serves_the_pdf_without_any_login(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $encaissement = $this->paymentFor($user, ['telephone' => '+212648430612']);

        $url = (new RecuWhatsAppLink())->pdfUrl($encaissement);

        // Explicitement déconnecté : l'étudiant n'a pas de compte.
        auth()->logout();
        $response = $this->get($url);

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
        // `attachment` : le clic doit TELECHARGER le recu, pas l'afficher dans
        // la webview de WhatsApp (demande explicite du 31/08/2026).
        $this->assertStringContainsString(
            'attachment; filename="recu-'.$encaissement->reference.'.pdf"',
            (string) $response->headers->get('content-disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_an_unsigned_or_tampered_link_is_refused(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $mine = $this->paymentFor($user, ['telephone' => '+212648430612']);
        $someoneElses = $this->paymentFor($user, ['telephone' => '+212648430613']);
        auth()->logout();

        // Sans signature : l'énumération /recu/1, /recu/2… est fermée.
        $this->get('/recu/'.$mine->id)->assertForbidden();

        // Signature valide, mais l'id est réécrit vers un AUTRE reçu.
        $tampered = str_replace(
            '/recu/'.$mine->id.'?',
            '/recu/'.$someoneElses->id.'?',
            (new RecuWhatsAppLink())->pdfUrl($mine),
        );
        $this->get($tampered)->assertForbidden();
    }

    /**
     * Le bug constate en situation reelle le 31/08/2026 : APP_URL local, donc
     * un lien http://127.0.0.1:8000/recu/... envoye a un etudiant. Sur SON
     * telephone, 127.0.0.1 est SON propre appareil - le lien est mort. On
     * refuse l'envoi plutot que de laisser le caissier croire que c'est parti.
     */
    public function test_it_refuses_to_send_a_link_pointing_at_localhost(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $encaissement = $this->paymentFor($user, ['telephone' => '+212648430612']);

        config(['app.url' => 'http://127.0.0.1:8000']);

        $this->actingAs($user)
            ->getJson(route('backoffice.encaissements.recu.whatsapp', $encaissement))
            ->assertStatus(422);
    }

    public function test_the_link_stops_working_once_it_has_expired(): void
    {
        $user = $this->userWith('payments.view', 'payments.create');
        $encaissement = $this->paymentFor($user, ['telephone' => '+212648430612']);
        auth()->logout();

        $expired = URL::temporarySignedRoute(
            'frontoffice.recu',
            now()->subMinute(),
            ['encaissement' => $encaissement->id],
        );

        $this->get($expired)->assertForbidden();
    }
}
