<?php

namespace App\Http\Controllers\DirectWaiter;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('DirectWaiter/Index');
    }
}
