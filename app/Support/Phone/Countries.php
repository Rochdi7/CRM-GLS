<?php

declare(strict_types=1);

namespace App\Support\Phone;

/**
 * Country catalog for phone inputs: ISO2 => [French name, dial code].
 * Phone numbers are STORED combined ("+212661954125"); split()/join()
 * convert between the stored value and the {country, national} UI pair.
 */
final class Countries
{
    /** GLS is Moroccan — Morocco is the default country everywhere. */
    public const DEFAULT = 'MA';

    /**
     * When several countries share a dial code, split() resolves to these.
     *
     * @var array<string, string>
     */
    private const PREFERRED_BY_DIAL = [
        '+1' => 'US',
        '+7' => 'RU',
        '+44' => 'GB',
        '+212' => 'MA',
        '+262' => 'RE',
        '+590' => 'GP',
        '+599' => 'CW',
    ];

    /**
     * @var array<string, array{0: string, 1: string}> ISO2 => [nom français, indicatif]
     */
    public const LIST = [
        'AF' => ['Afghanistan', '+93'],
        'ZA' => ['Afrique du Sud', '+27'],
        'AL' => ['Albanie', '+355'],
        'DZ' => ['Algérie', '+213'],
        'DE' => ['Allemagne', '+49'],
        'AD' => ['Andorre', '+376'],
        'AO' => ['Angola', '+244'],
        'AG' => ['Antigua-et-Barbuda', '+1268'],
        'SA' => ['Arabie saoudite', '+966'],
        'AR' => ['Argentine', '+54'],
        'AM' => ['Arménie', '+374'],
        'AW' => ['Aruba', '+297'],
        'AU' => ['Australie', '+61'],
        'AT' => ['Autriche', '+43'],
        'AZ' => ['Azerbaïdjan', '+994'],
        'BS' => ['Bahamas', '+1242'],
        'BH' => ['Bahreïn', '+973'],
        'BD' => ['Bangladesh', '+880'],
        'BB' => ['Barbade', '+1246'],
        'BE' => ['Belgique', '+32'],
        'BZ' => ['Belize', '+501'],
        'BJ' => ['Bénin', '+229'],
        'BM' => ['Bermudes', '+1441'],
        'BT' => ['Bhoutan', '+975'],
        'BY' => ['Biélorussie', '+375'],
        'BO' => ['Bolivie', '+591'],
        'BA' => ['Bosnie-Herzégovine', '+387'],
        'BW' => ['Botswana', '+267'],
        'BR' => ['Brésil', '+55'],
        'BN' => ['Brunei', '+673'],
        'BG' => ['Bulgarie', '+359'],
        'BF' => ['Burkina Faso', '+226'],
        'BI' => ['Burundi', '+257'],
        'KH' => ['Cambodge', '+855'],
        'CM' => ['Cameroun', '+237'],
        'CA' => ['Canada', '+1'],
        'CV' => ['Cap-Vert', '+238'],
        'CL' => ['Chili', '+56'],
        'CN' => ['Chine', '+86'],
        'CY' => ['Chypre', '+357'],
        'CO' => ['Colombie', '+57'],
        'KM' => ['Comores', '+269'],
        'CG' => ['Congo-Brazzaville', '+242'],
        'CD' => ['Congo-Kinshasa (RDC)', '+243'],
        'KR' => ['Corée du Sud', '+82'],
        'KP' => ['Corée du Nord', '+850'],
        'CR' => ['Costa Rica', '+506'],
        'CI' => ["Côte d'Ivoire", '+225'],
        'HR' => ['Croatie', '+385'],
        'CU' => ['Cuba', '+53'],
        'CW' => ['Curaçao', '+599'],
        'DK' => ['Danemark', '+45'],
        'DJ' => ['Djibouti', '+253'],
        'DM' => ['Dominique', '+1767'],
        'EG' => ['Égypte', '+20'],
        'AE' => ['Émirats arabes unis', '+971'],
        'EC' => ['Équateur', '+593'],
        'ER' => ['Érythrée', '+291'],
        'ES' => ['Espagne', '+34'],
        'EE' => ['Estonie', '+372'],
        'SZ' => ['Eswatini', '+268'],
        'US' => ['États-Unis', '+1'],
        'ET' => ['Éthiopie', '+251'],
        'FJ' => ['Fidji', '+679'],
        'FI' => ['Finlande', '+358'],
        'FR' => ['France', '+33'],
        'GA' => ['Gabon', '+241'],
        'GM' => ['Gambie', '+220'],
        'GE' => ['Géorgie', '+995'],
        'GH' => ['Ghana', '+233'],
        'GI' => ['Gibraltar', '+350'],
        'GR' => ['Grèce', '+30'],
        'GD' => ['Grenade', '+1473'],
        'GL' => ['Groenland', '+299'],
        'GP' => ['Guadeloupe', '+590'],
        'GT' => ['Guatemala', '+502'],
        'GN' => ['Guinée', '+224'],
        'GQ' => ['Guinée équatoriale', '+240'],
        'GW' => ['Guinée-Bissau', '+245'],
        'GY' => ['Guyana', '+592'],
        'GF' => ['Guyane française', '+594'],
        'HT' => ['Haïti', '+509'],
        'HN' => ['Honduras', '+504'],
        'HK' => ['Hong Kong', '+852'],
        'HU' => ['Hongrie', '+36'],
        'IN' => ['Inde', '+91'],
        'ID' => ['Indonésie', '+62'],
        'IQ' => ['Irak', '+964'],
        'IR' => ['Iran', '+98'],
        'IE' => ['Irlande', '+353'],
        'IS' => ['Islande', '+354'],
        'IL' => ['Israël', '+972'],
        'IT' => ['Italie', '+39'],
        'JM' => ['Jamaïque', '+1876'],
        'JP' => ['Japon', '+81'],
        'JO' => ['Jordanie', '+962'],
        'KZ' => ['Kazakhstan', '+7'],
        'KE' => ['Kenya', '+254'],
        'KG' => ['Kirghizistan', '+996'],
        'KI' => ['Kiribati', '+686'],
        'XK' => ['Kosovo', '+383'],
        'KW' => ['Koweït', '+965'],
        'LA' => ['Laos', '+856'],
        'LS' => ['Lesotho', '+266'],
        'LV' => ['Lettonie', '+371'],
        'LB' => ['Liban', '+961'],
        'LR' => ['Liberia', '+231'],
        'LY' => ['Libye', '+218'],
        'LI' => ['Liechtenstein', '+423'],
        'LT' => ['Lituanie', '+370'],
        'LU' => ['Luxembourg', '+352'],
        'MO' => ['Macao', '+853'],
        'MK' => ['Macédoine du Nord', '+389'],
        'MG' => ['Madagascar', '+261'],
        'MY' => ['Malaisie', '+60'],
        'MW' => ['Malawi', '+265'],
        'MV' => ['Maldives', '+960'],
        'ML' => ['Mali', '+223'],
        'MT' => ['Malte', '+356'],
        'MA' => ['Maroc', '+212'],
        'MH' => ['Marshall', '+692'],
        'MQ' => ['Martinique', '+596'],
        'MU' => ['Maurice', '+230'],
        'MR' => ['Mauritanie', '+222'],
        'YT' => ['Mayotte', '+262'],
        'MX' => ['Mexique', '+52'],
        'FM' => ['Micronésie', '+691'],
        'MD' => ['Moldavie', '+373'],
        'MC' => ['Monaco', '+377'],
        'MN' => ['Mongolie', '+976'],
        'ME' => ['Monténégro', '+382'],
        'MZ' => ['Mozambique', '+258'],
        'MM' => ['Myanmar (Birmanie)', '+95'],
        'NA' => ['Namibie', '+264'],
        'NR' => ['Nauru', '+674'],
        'NP' => ['Népal', '+977'],
        'NI' => ['Nicaragua', '+505'],
        'NE' => ['Niger', '+227'],
        'NG' => ['Nigeria', '+234'],
        'NO' => ['Norvège', '+47'],
        'NC' => ['Nouvelle-Calédonie', '+687'],
        'NZ' => ['Nouvelle-Zélande', '+64'],
        'OM' => ['Oman', '+968'],
        'UG' => ['Ouganda', '+256'],
        'UZ' => ['Ouzbékistan', '+998'],
        'PK' => ['Pakistan', '+92'],
        'PW' => ['Palaos', '+680'],
        'PS' => ['Palestine', '+970'],
        'PA' => ['Panama', '+507'],
        'PG' => ['Papouasie-Nouvelle-Guinée', '+675'],
        'PY' => ['Paraguay', '+595'],
        'NL' => ['Pays-Bas', '+31'],
        'PE' => ['Pérou', '+51'],
        'PH' => ['Philippines', '+63'],
        'PL' => ['Pologne', '+48'],
        'PF' => ['Polynésie française', '+689'],
        'PR' => ['Porto Rico', '+1787'],
        'PT' => ['Portugal', '+351'],
        'QA' => ['Qatar', '+974'],
        'DO' => ['République dominicaine', '+1809'],
        'CF' => ['République centrafricaine', '+236'],
        'CZ' => ['République tchèque', '+420'],
        'RE' => ['La Réunion', '+262'],
        'RO' => ['Roumanie', '+40'],
        'GB' => ['Royaume-Uni', '+44'],
        'RU' => ['Russie', '+7'],
        'RW' => ['Rwanda', '+250'],
        'KN' => ['Saint-Christophe-et-Niévès', '+1869'],
        'LC' => ['Sainte-Lucie', '+1758'],
        'SM' => ['Saint-Marin', '+378'],
        'VC' => ['Saint-Vincent-et-les-Grenadines', '+1784'],
        'SB' => ['Salomon', '+677'],
        'SV' => ['Salvador', '+503'],
        'WS' => ['Samoa', '+685'],
        'ST' => ['Sao Tomé-et-Principe', '+239'],
        'SN' => ['Sénégal', '+221'],
        'RS' => ['Serbie', '+381'],
        'SC' => ['Seychelles', '+248'],
        'SL' => ['Sierra Leone', '+232'],
        'SG' => ['Singapour', '+65'],
        'SK' => ['Slovaquie', '+421'],
        'SI' => ['Slovénie', '+386'],
        'SO' => ['Somalie', '+252'],
        'SD' => ['Soudan', '+249'],
        'SS' => ['Soudan du Sud', '+211'],
        'LK' => ['Sri Lanka', '+94'],
        'SE' => ['Suède', '+46'],
        'CH' => ['Suisse', '+41'],
        'SR' => ['Suriname', '+597'],
        'SY' => ['Syrie', '+963'],
        'TJ' => ['Tadjikistan', '+992'],
        'TW' => ['Taïwan', '+886'],
        'TZ' => ['Tanzanie', '+255'],
        'TD' => ['Tchad', '+235'],
        'TH' => ['Thaïlande', '+66'],
        'TL' => ['Timor oriental', '+670'],
        'TG' => ['Togo', '+228'],
        'TO' => ['Tonga', '+676'],
        'TT' => ['Trinité-et-Tobago', '+1868'],
        'TN' => ['Tunisie', '+216'],
        'TM' => ['Turkménistan', '+993'],
        'TR' => ['Turquie', '+90'],
        'TV' => ['Tuvalu', '+688'],
        'UA' => ['Ukraine', '+380'],
        'UY' => ['Uruguay', '+598'],
        'VU' => ['Vanuatu', '+678'],
        'VA' => ['Vatican', '+379'],
        'VE' => ['Venezuela', '+58'],
        'VN' => ['Vietnam', '+84'],
        'YE' => ['Yémen', '+967'],
        'ZM' => ['Zambie', '+260'],
        'ZW' => ['Zimbabwe', '+263'],
    ];

    /**
     * All countries sorted by French name (accent-insensitive), for selects.
     *
     * @return array<string, array{nom: string, dial: string}>
     */
    public static function all(): array
    {
        static $sorted = null;

        if ($sorted === null) {
            $sorted = collect(self::LIST)
                ->map(fn (array $c) => ['nom' => $c[0], 'dial' => $c[1]])
                ->sortBy(fn (array $c) => iconv('UTF-8', 'ASCII//TRANSLIT', $c['nom']))
                ->all();
        }

        return $sorted;
    }

    public static function isValid(?string $iso): bool
    {
        return $iso !== null && isset(self::LIST[$iso]);
    }

    /** Dial code ("+212") for an ISO2 country, falling back to the default. */
    public static function dial(?string $iso): string
    {
        return self::LIST[self::isValid($iso) ? $iso : self::DEFAULT][1];
    }

    /**
     * Combine a country + national number into the stored "+212661954125"
     * form. Returns null when the national part is empty.
     */
    public static function join(?string $iso, ?string $national): ?string
    {
        $national = preg_replace('/[\s.\-()]/', '', (string) $national);

        if ($national === '') {
            return null;
        }

        // Already fully qualified (legacy value left untouched by the user).
        if (str_starts_with($national, '+')) {
            return $national;
        }

        return self::dial($iso).$national;
    }

    /**
     * Split a stored value into [ISO2 country, national part] for the UI.
     * Unknown or local-format values fall back to the default country.
     *
     * @return array{0: string, 1: string}
     */
    public static function split(?string $stored): array
    {
        $stored = trim((string) $stored);

        if ($stored === '' || ! str_starts_with($stored, '+')) {
            return [self::DEFAULT, $stored];
        }

        foreach (self::dialsByLength() as $dial => $iso) {
            if (str_starts_with($stored, $dial)) {
                return [$iso, substr($stored, strlen($dial))];
            }
        }

        return [self::DEFAULT, $stored];
    }

    /**
     * dial => ISO2 map, longest dial first, shared dials resolved via
     * PREFERRED_BY_DIAL (first listed country otherwise).
     *
     * @return array<string, string>
     */
    private static function dialsByLength(): array
    {
        static $map = null;

        if ($map === null) {
            $map = [];
            foreach (self::LIST as $iso => [$nom, $dial]) {
                $map[$dial] ??= $iso;
            }
            foreach (self::PREFERRED_BY_DIAL as $dial => $iso) {
                $map[$dial] = $iso;
            }
            uksort($map, fn (string $a, string $b) => strlen($b) <=> strlen($a));
        }

        return $map;
    }
}
