<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Web Routes — entry point
|--------------------------------------------------------------------------
|
| Do NOT declare routes here. This file only loads the separated
| route files for each application area:
|
|   routes/frontoffice.php  → public area   (names: frontoffice.*)
|   routes/backoffice.php   → admin area    (/backoffice, names: backoffice.*)
|
*/

require __DIR__.'/frontoffice.php';
require __DIR__.'/backoffice.php';
