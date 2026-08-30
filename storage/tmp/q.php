<?php
$rows = \App\Models\Inscription::with('group:id,nom,date_debut_formation,date_fin_formation')
  ->whereIn('reference', ['INS-5960','INS-5959','INS-5967','INS-5966'])->get();
foreach ($rows as $i) {
  echo $i->reference,' | ins.debut=',$i->date_debut,' ins.fin=',$i->date_fin,
  ' || grp=',$i->group?->nom,' grp.debut=',$i->group?->date_debut_formation,
  ' grp.fin=',$i->group?->date_fin_formation, PHP_EOL;
}
$n = \App\Models\Inscription::whereNull('date_debut')->count();
echo "inscriptions sans date_debut: $n / ", \App\Models\Inscription::count(), PHP_EOL;
