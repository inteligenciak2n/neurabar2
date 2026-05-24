<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('user.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('venue.{id}.kitchen', function ($user, string $id) {
    return $user->venue_id === $id
        || session('active_venue_id') === $id;
});

Broadcast::channel('venue.{venueId}.station.{stationId}', function ($user, string $venueId) {
    return $user->venue_id === $venueId
        || session('active_venue_id') === $venueId;
});
