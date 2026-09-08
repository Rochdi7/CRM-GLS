<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Dossier des exports de l'ancien CRM
    |--------------------------------------------------------------------------
    |
    | Racine contenant « old data/<centre>/ » et « active data/<centre>/ »,
    | telle que la lisent `import:centre` et `paiements:reconcilier`.
    |
    | ⚠ C'est aussi la BORNE de sécurité de l'écran « Réconciliation des
    | paiements importés » : le champ « dossier » y est modifiable, mais
    | RunLegacyReconciliationRequest refuse tout chemin hors de cette racine.
    | L'élargir à « / » donnerait à l'écran la lecture de tout le disque.
    |
    */

    'legacy_import_path' => env('GLS_LEGACY_IMPORT_PATH', base_path('data')),

];
