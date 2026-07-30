export interface Country {
    iso: string;
    nom: string;
    dial: string;
}

/**
 * Mirrors App\Support\Phone\Countries::all() exactly — same 190 entries,
 * same French names, same dial codes, same sort order (French name,
 * accent-insensitive). Generated once via `artisan tinker` from the PHP
 * source of truth; if that catalog ever changes, regenerate this file the
 * same way rather than hand-editing it out of sync.
 */
export const COUNTRIES: Country[] = [
    { iso: 'EG', nom: 'Égypte', dial: '+20' },
    { iso: 'AE', nom: 'Émirats arabes unis', dial: '+971' },
    { iso: 'EC', nom: 'Équateur', dial: '+593' },
    { iso: 'ER', nom: 'Érythrée', dial: '+291' },
    { iso: 'US', nom: 'États-Unis', dial: '+1' },
    { iso: 'ET', nom: 'Éthiopie', dial: '+251' },
    { iso: 'AF', nom: 'Afghanistan', dial: '+93' },
    { iso: 'ZA', nom: 'Afrique du Sud', dial: '+27' },
    { iso: 'AL', nom: 'Albanie', dial: '+355' },
    { iso: 'DZ', nom: 'Algérie', dial: '+213' },
    { iso: 'DE', nom: 'Allemagne', dial: '+49' },
    { iso: 'AD', nom: 'Andorre', dial: '+376' },
    { iso: 'AO', nom: 'Angola', dial: '+244' },
    { iso: 'AG', nom: 'Antigua-et-Barbuda', dial: '+1268' },
    { iso: 'SA', nom: 'Arabie saoudite', dial: '+966' },
    { iso: 'AR', nom: 'Argentine', dial: '+54' },
    { iso: 'AM', nom: 'Arménie', dial: '+374' },
    { iso: 'AW', nom: 'Aruba', dial: '+297' },
    { iso: 'AU', nom: 'Australie', dial: '+61' },
    { iso: 'AT', nom: 'Autriche', dial: '+43' },
    { iso: 'AZ', nom: 'Azerbaïdjan', dial: '+994' },
    { iso: 'BJ', nom: 'Bénin', dial: '+229' },
    { iso: 'BS', nom: 'Bahamas', dial: '+1242' },
    { iso: 'BH', nom: 'Bahreïn', dial: '+973' },
    { iso: 'BD', nom: 'Bangladesh', dial: '+880' },
    { iso: 'BB', nom: 'Barbade', dial: '+1246' },
    { iso: 'BE', nom: 'Belgique', dial: '+32' },
    { iso: 'BZ', nom: 'Belize', dial: '+501' },
    { iso: 'BM', nom: 'Bermudes', dial: '+1441' },
    { iso: 'BT', nom: 'Bhoutan', dial: '+975' },
    { iso: 'BY', nom: 'Biélorussie', dial: '+375' },
    { iso: 'BO', nom: 'Bolivie', dial: '+591' },
    { iso: 'BA', nom: 'Bosnie-Herzégovine', dial: '+387' },
    { iso: 'BW', nom: 'Botswana', dial: '+267' },
    { iso: 'BR', nom: 'Brésil', dial: '+55' },
    { iso: 'BN', nom: 'Brunei', dial: '+673' },
    { iso: 'BG', nom: 'Bulgarie', dial: '+359' },
    { iso: 'BF', nom: 'Burkina Faso', dial: '+226' },
    { iso: 'BI', nom: 'Burundi', dial: '+257' },
    { iso: 'CI', nom: "Côte d'Ivoire", dial: '+225' },
    { iso: 'KH', nom: 'Cambodge', dial: '+855' },
    { iso: 'CM', nom: 'Cameroun', dial: '+237' },
    { iso: 'CA', nom: 'Canada', dial: '+1' },
    { iso: 'CV', nom: 'Cap-Vert', dial: '+238' },
    { iso: 'CL', nom: 'Chili', dial: '+56' },
    { iso: 'CN', nom: 'Chine', dial: '+86' },
    { iso: 'CY', nom: 'Chypre', dial: '+357' },
    { iso: 'CO', nom: 'Colombie', dial: '+57' },
    { iso: 'KM', nom: 'Comores', dial: '+269' },
    { iso: 'CG', nom: 'Congo-Brazzaville', dial: '+242' },
    { iso: 'CD', nom: 'Congo-Kinshasa (RDC)', dial: '+243' },
    { iso: 'KP', nom: 'Corée du Nord', dial: '+850' },
    { iso: 'KR', nom: 'Corée du Sud', dial: '+82' },
    { iso: 'CR', nom: 'Costa Rica', dial: '+506' },
    { iso: 'HR', nom: 'Croatie', dial: '+385' },
    { iso: 'CU', nom: 'Cuba', dial: '+53' },
    { iso: 'CW', nom: 'Curaçao', dial: '+599' },
    { iso: 'DK', nom: 'Danemark', dial: '+45' },
    { iso: 'DJ', nom: 'Djibouti', dial: '+253' },
    { iso: 'DM', nom: 'Dominique', dial: '+1767' },
    { iso: 'ES', nom: 'Espagne', dial: '+34' },
    { iso: 'EE', nom: 'Estonie', dial: '+372' },
    { iso: 'SZ', nom: 'Eswatini', dial: '+268' },
    { iso: 'FJ', nom: 'Fidji', dial: '+679' },
    { iso: 'FI', nom: 'Finlande', dial: '+358' },
    { iso: 'FR', nom: 'France', dial: '+33' },
    { iso: 'GE', nom: 'Géorgie', dial: '+995' },
    { iso: 'GA', nom: 'Gabon', dial: '+241' },
    { iso: 'GM', nom: 'Gambie', dial: '+220' },
    { iso: 'GH', nom: 'Ghana', dial: '+233' },
    { iso: 'GI', nom: 'Gibraltar', dial: '+350' },
    { iso: 'GR', nom: 'Grèce', dial: '+30' },
    { iso: 'GD', nom: 'Grenade', dial: '+1473' },
    { iso: 'GL', nom: 'Groenland', dial: '+299' },
    { iso: 'GP', nom: 'Guadeloupe', dial: '+590' },
    { iso: 'GT', nom: 'Guatemala', dial: '+502' },
    { iso: 'GN', nom: 'Guinée', dial: '+224' },
    { iso: 'GQ', nom: 'Guinée équatoriale', dial: '+240' },
    { iso: 'GW', nom: 'Guinée-Bissau', dial: '+245' },
    { iso: 'GY', nom: 'Guyana', dial: '+592' },
    { iso: 'GF', nom: 'Guyane française', dial: '+594' },
    { iso: 'HT', nom: 'Haïti', dial: '+509' },
    { iso: 'HN', nom: 'Honduras', dial: '+504' },
    { iso: 'HK', nom: 'Hong Kong', dial: '+852' },
    { iso: 'HU', nom: 'Hongrie', dial: '+36' },
    { iso: 'IN', nom: 'Inde', dial: '+91' },
    { iso: 'ID', nom: 'Indonésie', dial: '+62' },
    { iso: 'IQ', nom: 'Irak', dial: '+964' },
    { iso: 'IR', nom: 'Iran', dial: '+98' },
    { iso: 'IE', nom: 'Irlande', dial: '+353' },
    { iso: 'IS', nom: 'Islande', dial: '+354' },
    { iso: 'IL', nom: 'Israël', dial: '+972' },
    { iso: 'IT', nom: 'Italie', dial: '+39' },
    { iso: 'JM', nom: 'Jamaïque', dial: '+1876' },
    { iso: 'JP', nom: 'Japon', dial: '+81' },
    { iso: 'JO', nom: 'Jordanie', dial: '+962' },
    { iso: 'KZ', nom: 'Kazakhstan', dial: '+7' },
    { iso: 'KE', nom: 'Kenya', dial: '+254' },
    { iso: 'KG', nom: 'Kirghizistan', dial: '+996' },
    { iso: 'KI', nom: 'Kiribati', dial: '+686' },
    { iso: 'XK', nom: 'Kosovo', dial: '+383' },
    { iso: 'KW', nom: 'Koweït', dial: '+965' },
    { iso: 'RE', nom: 'La Réunion', dial: '+262' },
    { iso: 'LA', nom: 'Laos', dial: '+856' },
    { iso: 'LS', nom: 'Lesotho', dial: '+266' },
    { iso: 'LV', nom: 'Lettonie', dial: '+371' },
    { iso: 'LB', nom: 'Liban', dial: '+961' },
    { iso: 'LR', nom: 'Liberia', dial: '+231' },
    { iso: 'LY', nom: 'Libye', dial: '+218' },
    { iso: 'LI', nom: 'Liechtenstein', dial: '+423' },
    { iso: 'LT', nom: 'Lituanie', dial: '+370' },
    { iso: 'LU', nom: 'Luxembourg', dial: '+352' },
    { iso: 'MK', nom: 'Macédoine du Nord', dial: '+389' },
    { iso: 'MO', nom: 'Macao', dial: '+853' },
    { iso: 'MG', nom: 'Madagascar', dial: '+261' },
    { iso: 'MY', nom: 'Malaisie', dial: '+60' },
    { iso: 'MW', nom: 'Malawi', dial: '+265' },
    { iso: 'MV', nom: 'Maldives', dial: '+960' },
    { iso: 'ML', nom: 'Mali', dial: '+223' },
    { iso: 'MT', nom: 'Malte', dial: '+356' },
    { iso: 'MA', nom: 'Maroc', dial: '+212' },
    { iso: 'MH', nom: 'Marshall', dial: '+692' },
    { iso: 'MQ', nom: 'Martinique', dial: '+596' },
    { iso: 'MU', nom: 'Maurice', dial: '+230' },
    { iso: 'MR', nom: 'Mauritanie', dial: '+222' },
    { iso: 'YT', nom: 'Mayotte', dial: '+262' },
    { iso: 'MX', nom: 'Mexique', dial: '+52' },
    { iso: 'FM', nom: 'Micronésie', dial: '+691' },
    { iso: 'MD', nom: 'Moldavie', dial: '+373' },
    { iso: 'MC', nom: 'Monaco', dial: '+377' },
    { iso: 'MN', nom: 'Mongolie', dial: '+976' },
    { iso: 'ME', nom: 'Monténégro', dial: '+382' },
    { iso: 'MZ', nom: 'Mozambique', dial: '+258' },
    { iso: 'MM', nom: 'Myanmar (Birmanie)', dial: '+95' },
    { iso: 'NP', nom: 'Népal', dial: '+977' },
    { iso: 'NA', nom: 'Namibie', dial: '+264' },
    { iso: 'NR', nom: 'Nauru', dial: '+674' },
    { iso: 'NI', nom: 'Nicaragua', dial: '+505' },
    { iso: 'NE', nom: 'Niger', dial: '+227' },
    { iso: 'NG', nom: 'Nigeria', dial: '+234' },
    { iso: 'NO', nom: 'Norvège', dial: '+47' },
    { iso: 'NC', nom: 'Nouvelle-Calédonie', dial: '+687' },
    { iso: 'NZ', nom: 'Nouvelle-Zélande', dial: '+64' },
    { iso: 'OM', nom: 'Oman', dial: '+968' },
    { iso: 'UG', nom: 'Ouganda', dial: '+256' },
    { iso: 'UZ', nom: 'Ouzbékistan', dial: '+998' },
    { iso: 'PE', nom: 'Pérou', dial: '+51' },
    { iso: 'PK', nom: 'Pakistan', dial: '+92' },
    { iso: 'PW', nom: 'Palaos', dial: '+680' },
    { iso: 'PS', nom: 'Palestine', dial: '+970' },
    { iso: 'PA', nom: 'Panama', dial: '+507' },
    { iso: 'PG', nom: 'Papouasie-Nouvelle-Guinée', dial: '+675' },
    { iso: 'PY', nom: 'Paraguay', dial: '+595' },
    { iso: 'NL', nom: 'Pays-Bas', dial: '+31' },
    { iso: 'PH', nom: 'Philippines', dial: '+63' },
    { iso: 'PL', nom: 'Pologne', dial: '+48' },
    { iso: 'PF', nom: 'Polynésie française', dial: '+689' },
    { iso: 'PR', nom: 'Porto Rico', dial: '+1787' },
    { iso: 'PT', nom: 'Portugal', dial: '+351' },
    { iso: 'QA', nom: 'Qatar', dial: '+974' },
    { iso: 'CF', nom: 'République centrafricaine', dial: '+236' },
    { iso: 'DO', nom: 'République dominicaine', dial: '+1809' },
    { iso: 'CZ', nom: 'République tchèque', dial: '+420' },
    { iso: 'RO', nom: 'Roumanie', dial: '+40' },
    { iso: 'GB', nom: 'Royaume-Uni', dial: '+44' },
    { iso: 'RU', nom: 'Russie', dial: '+7' },
    { iso: 'RW', nom: 'Rwanda', dial: '+250' },
    { iso: 'SN', nom: 'Sénégal', dial: '+221' },
    { iso: 'KN', nom: 'Saint-Christophe-et-Niévès', dial: '+1869' },
    { iso: 'SM', nom: 'Saint-Marin', dial: '+378' },
    { iso: 'VC', nom: 'Saint-Vincent-et-les-Grenadines', dial: '+1784' },
    { iso: 'LC', nom: 'Sainte-Lucie', dial: '+1758' },
    { iso: 'SB', nom: 'Salomon', dial: '+677' },
    { iso: 'SV', nom: 'Salvador', dial: '+503' },
    { iso: 'WS', nom: 'Samoa', dial: '+685' },
    { iso: 'ST', nom: 'Sao Tomé-et-Principe', dial: '+239' },
    { iso: 'RS', nom: 'Serbie', dial: '+381' },
    { iso: 'SC', nom: 'Seychelles', dial: '+248' },
    { iso: 'SL', nom: 'Sierra Leone', dial: '+232' },
    { iso: 'SG', nom: 'Singapour', dial: '+65' },
    { iso: 'SI', nom: 'Slovénie', dial: '+386' },
    { iso: 'SK', nom: 'Slovaquie', dial: '+421' },
    { iso: 'SO', nom: 'Somalie', dial: '+252' },
    { iso: 'SD', nom: 'Soudan', dial: '+249' },
    { iso: 'SS', nom: 'Soudan du Sud', dial: '+211' },
    { iso: 'LK', nom: 'Sri Lanka', dial: '+94' },
    { iso: 'SE', nom: 'Suède', dial: '+46' },
    { iso: 'CH', nom: 'Suisse', dial: '+41' },
    { iso: 'SR', nom: 'Suriname', dial: '+597' },
    { iso: 'SY', nom: 'Syrie', dial: '+963' },
    { iso: 'TW', nom: 'Taïwan', dial: '+886' },
    { iso: 'TJ', nom: 'Tadjikistan', dial: '+992' },
    { iso: 'TZ', nom: 'Tanzanie', dial: '+255' },
    { iso: 'TD', nom: 'Tchad', dial: '+235' },
    { iso: 'TH', nom: 'Thaïlande', dial: '+66' },
    { iso: 'TL', nom: 'Timor oriental', dial: '+670' },
    { iso: 'TG', nom: 'Togo', dial: '+228' },
    { iso: 'TO', nom: 'Tonga', dial: '+676' },
    { iso: 'TT', nom: 'Trinité-et-Tobago', dial: '+1868' },
    { iso: 'TN', nom: 'Tunisie', dial: '+216' },
    { iso: 'TM', nom: 'Turkménistan', dial: '+993' },
    { iso: 'TR', nom: 'Turquie', dial: '+90' },
    { iso: 'TV', nom: 'Tuvalu', dial: '+688' },
    { iso: 'UA', nom: 'Ukraine', dial: '+380' },
    { iso: 'UY', nom: 'Uruguay', dial: '+598' },
    { iso: 'VU', nom: 'Vanuatu', dial: '+678' },
    { iso: 'VA', nom: 'Vatican', dial: '+379' },
    { iso: 'VE', nom: 'Venezuela', dial: '+58' },
    { iso: 'VN', nom: 'Vietnam', dial: '+84' },
    { iso: 'YE', nom: 'Yémen', dial: '+967' },
    { iso: 'ZM', nom: 'Zambie', dial: '+260' },
    { iso: 'ZW', nom: 'Zimbabwe', dial: '+263' },
];

export const DEFAULT_COUNTRY = 'MA';

const DIAL_TO_ISO: Record<string, string> = (() => {
    const map: Record<string, string> = {};
    const preferred: Record<string, string> = {
        '+1': 'US',
        '+7': 'RU',
        '+44': 'GB',
        '+212': 'MA',
        '+262': 'RE',
        '+590': 'GP',
        '+599': 'CW',
    };
    for (const c of COUNTRIES) {
        if (!(c.dial in map)) {
            map[c.dial] = c.iso;
        }
    }
    Object.assign(map, preferred);

    return map;
})();

const DIALS_LONGEST_FIRST = Object.keys(DIAL_TO_ISO).sort((a, b) => b.length - a.length);

export function dialFor(iso: string): string {
    return COUNTRIES.find((c) => c.iso === iso)?.dial ?? COUNTRIES.find((c) => c.iso === DEFAULT_COUNTRY)!.dial;
}

/** Combine a country + national number into "+212661954125"; null when the national part is empty. */
export function joinPhone(iso: string, national: string): string | null {
    const cleaned = national.replace(/[\s.\-()]/g, '');

    if (cleaned === '') {
        return null;
    }

    if (cleaned.startsWith('+')) {
        return cleaned;
    }

    return dialFor(iso) + cleaned;
}

/** Split a stored value into [ISO2, national part] for the UI; unknown/local values fall back to the default country. */
export function splitPhone(stored: string | null): [string, string] {
    const value = (stored ?? '').trim();

    if (value === '' || !value.startsWith('+')) {
        return [DEFAULT_COUNTRY, value];
    }

    for (const dial of DIALS_LONGEST_FIRST) {
        if (value.startsWith(dial)) {
            return [DIAL_TO_ISO[dial], value.slice(dial.length)];
        }
    }

    return [DEFAULT_COUNTRY, value];
}
