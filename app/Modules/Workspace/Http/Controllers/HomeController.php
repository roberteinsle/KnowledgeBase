<?php

namespace App\Modules\Workspace\Http\Controllers;

use App\Core\Dashboard\DashboardRegistry;
use App\Modules\Workspace\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController
{
    public function __invoke(Request $request, DashboardRegistry $dashboard): View
    {
        return view('home', [
            'workspace' => Workspace::current(),
            'widgets' => $dashboard->renderFor($request->user()),
        ]);
    }
}
