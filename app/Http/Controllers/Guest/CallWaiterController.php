<?php

namespace App\Http\Controllers\Guest;

use App\Actions\Guest\SendServiceRequestAction;
use App\Http\Requests\Guest\StoreServiceRequestRequest;
use App\Models\Tenant\Venue;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class CallWaiterController
{
    public function show(string $slug): Response
    {
        $venue = Venue::where('call_waiter_slug', $slug)->first();

        if ($venue === null) {
            abort(404);
        }

        return Inertia::render('Guest/CallWaiter', [
            'venueName' => $venue->name,
            'headerUrl' => $venue->call_waiter_header_url,
            'passphraseRequired' => $venue->call_waiter_passphrase !== null && $venue->call_waiter_passphrase !== '',
        ]);
    }

    public function store(string $slug, StoreServiceRequestRequest $request, SendServiceRequestAction $action): JsonResponse
    {
        $order = $action->execute($slug, $request);

        return response()->json(['protocol' => $order->id], 201);
    }
}
