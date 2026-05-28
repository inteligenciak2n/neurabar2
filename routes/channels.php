<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('venue.{id}.kitchen', function (User $user, string $id) {
    return $user->venues()->wherePivot('venue_id', $id)->exists();
});

Broadcast::channel('venue.{venueId}.station.{stationId}', function (User $user, string $venueId) {
    return $user->venues()->wherePivot('venue_id', $venueId)->exists();
});
