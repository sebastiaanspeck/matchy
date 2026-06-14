<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\PreventRequestsDuringMaintenance as Middleware;

class CheckForMaintenanceMode extends Middleware
{
    protected $except = [];
}
