<?php
$rows = \App\Models\Inscription::with('group:id,nom,date_debut_formation,date_fin_formation')
  ->whereNull('date_debut')->latest('id')->take(8)->get();
foreach ($rows as $i) {
  echo $i->reference,' || grp=',$i->group?->nom,
  ' grp.debut=',var_export($i->group?->date_debut_formation?->toDateString(),true),
  ' grp.fin=',var_export($i->group?->date_fin_formation?->toDateString(),true), PHP_EOL;
}
$g = \App\Models\Group::whereNull('date_debut_formation')->count();
echo "groupes sans date_debut_formation: $g / ", \App\Models\Group::count(), PHP_EOL;
