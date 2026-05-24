<?php

namespace App\Http\Controllers\Platform;

use App\Actions\Platform\CreateCorporationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\StoreCorporationRequest;
use App\Http\Requests\Platform\UpdateCorporationRequest;
use App\Models\Tenant\Corporation;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CorporationController extends Controller
{
    public function index(): Response
    {
        $corporations = Corporation::query()
            ->when(request('search'), fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%"))
            ->with('planCatalog:id,name')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Platform/Corporations/Index', [
            'corporations' => $corporations,
            'filters' => request()->only('search'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Platform/Corporations/Create');
    }

    public function store(StoreCorporationRequest $request, CreateCorporationAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return redirect()->route('platform.corporations.index')
            ->with('success', 'Corporation created successfully.');
    }

    public function edit(Corporation $corporation): Response
    {
        $corporation->load('planCatalog:id,name', 'venues:id,name,active,corporation_id');

        return Inertia::render('Platform/Corporations/Edit', [
            'corporation' => $corporation,
        ]);
    }

    public function update(UpdateCorporationRequest $request, Corporation $corporation): RedirectResponse
    {
        $corporation->update($request->validated());

        return redirect()->route('platform.corporations.index')
            ->with('success', 'Corporation updated successfully.');
    }
}
