<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as RoutingController;

/*
 * Extends the framework routing controller (not the bare skeleton class) so
 * authorizeResource() can register its per-action `can:` middleware.
 */
abstract class Controller extends RoutingController
{
    use AuthorizesRequests;
}
