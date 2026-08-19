<?php
use App\Models\{Inscription, User};
$ins = Inscription::whereNotNull('legacy_source')->firstOrFail();
$u = User::where('email','admin@gls.test')->firstOrFail();
// Same route the "Enregistrer un paiement" modal fetches.
$res = app('router')->getRoutes(); // ensure routes are loaded
$request = Illuminate\Http\Request::create("/backoffice/inscriptions/{$ins->id}/unpaid-fees", 'GET');
auth()->login($u);
$response = app()->handle($request);
$json = json_decode($response->getContent(), true);
echo "HTTP ".$response->getStatusCode()."\n";
echo "fees returned: ".count($json['fees'] ?? [])."\n";
foreach (array_slice($json['fees'] ?? [], 0, 4) as $f)
  printf("   %-28s reste=%-9s echeance=%s\n", $f['nom'], $f['reste'], $f['dateEcheance'] ?? '-');
