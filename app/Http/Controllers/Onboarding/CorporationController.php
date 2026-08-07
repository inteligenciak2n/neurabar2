<?php

namespace App\Http\Controllers\Onboarding;

use App\Actions\Onboarding\FinalizeOnboardingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\StoreCorporationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class CorporationController extends Controller
{
    public function create(): Response|RedirectResponse
    {
        $user = auth()->user();

        if ($user->onboarding_completed_at) {
            return redirect()->route('dashboard');
        }

        $corporation = $user->ownedCorporation;

        if (! $corporation) {
            return redirect()->route('onboarding.subscription.create');
        }

        return Inertia::render('Onboarding/Corporation', [
            'userName' => $user->name,
            'corporationName' => $corporation->name,
            'venueCount' => (int) session('onboarding.venue_count', 1),
        ]);
    }

    public function store(StoreCorporationRequest $request, FinalizeOnboardingAction $action): RedirectResponse
    {
        $user = $request->user();
        $corporation = $user->ownedCorporation;

        abort_unless($corporation !== null, 403, 'Nenhuma corporation encontrada para este usuário.');

        try {
            $action->execute(
                $user,
                $corporation,
                $request->only(['name', 'tax_id', 'email', 'contact_phone']),
                $request->validated('venues'),
            );
        } catch (InvalidArgumentException $e) {
            // Business rules raised by the action are the user's problem to fix,
            // not a server error that discards the whole filled-in form.
            return back()->withInput()->withErrors(['venues' => $e->getMessage()]);
        }

        return redirect()->route('dashboard')
            ->with('success', 'Tudo pronto! Seu período de teste começou.');
    }
}
