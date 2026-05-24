<?php

use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('venue.{id}.kitchen', function ($user, string $id) {
    if ($user->venue_id === $id) {
        return true;
    }

    if ($user->corporation_id) {
        return Venue::where('id', $id)
            ->where('corporation_id', $user->corporation_id)
            ->where('active', true)
            ->exists();
    }

    return false;
});

Broadcast::channel('venue.{venueId}.station.{stationId}', function ($user, string $venueId) {
    if ($user->venue_id === $venueId) {
        return true;
    }

    if ($user->corporation_id) {
        return Venue::where('id', $venueId)
            ->where('corporation_id', $user->corporation_id)
            ->where('active', true)
            ->exists();
    }

    return false;
});
