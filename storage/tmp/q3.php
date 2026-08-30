<?php
foreach (\App\Models\Group::where('nom','ilike','%16H SEPTEMBRE%')
  ->orWhere('nom','ilike','%10H SEPTEMBRE%')
  ->orWhere('nom','ilike','%19H SEPTEMBRE%')->get() as $g) {
  echo $g->id,' | ',$g->nom,' | debut=',var_export($g->date_debut_formation?->toDateString(),true),
    ' fin=',var_export($g->date_fin_formation?->toDateString(),true),
    ' | created=',$g->created_at, PHP_EOL;
}
