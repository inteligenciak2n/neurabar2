<?php

namespace App\Http\Controllers\Support;

use App\Http\Controllers\Controller;
use App\Models\Support\Ticket;
use App\Models\Support\TutorialCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $openTickets = Ticket::on('support')
            ->forUser($user->id)
            ->open()
            ->with('category')
            ->latest()
            ->limit(5)
            ->get();

        $recentlyResolved = Ticket::on('support')
            ->forUser($user->id)
            ->where('status', 'resolved')
            ->with(['category', 'rating'])
            ->latest('closed_at')
            ->limit(3)
            ->get();

        $tutorialCategories = TutorialCategory::on('support')
            ->where('active', true)
            ->with(['publishedTutorials' => fn ($q) => $q->select('id', 'category_id', 'title', 'slug', 'summary')->orderBy('position')->limit(4)])
            ->orderBy('position')
            ->get();

        return Inertia::render('Support/Dashboard', [
            'openTickets' => $openTickets,
            'recentlyResolved' => $recentlyResolved,
            'tutorialCategories' => $tutorialCategories,
        ]);
    }
}
