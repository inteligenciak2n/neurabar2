<?php

namespace App\Actions\Corporation;

use App\Enums\UserRole;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\Venue;
use Illuminate\Support\Str;

class CreateVenueAction
{
    public function execute(Corporation $corporation, array $data): Venue
    {
        $slug = Str::slug($data['name']).'-'.Str::lower(Str::random(6));

        $venue = Venue::create([
            ...$data,
            'corporation_id' => $corporation->id,
            'call_waiter_slug' => $slug,
            'active' => true,
        ]);

        VenueSettings::create(['venue_id' => $venue->id]);

        KitchenStation::insert([
            ['id' => (string) Str::uuid(), 'venue_id' => $venue->id, 'name' => 'Kitchen', 'created_at' => now(), 'updated_at' => now()],
            ['id' => (string) Str::uuid(), 'venue_id' => $venue->id, 'name' => 'Bar', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $statuses = [
            ['name' => 'Pending', 'color' => '#94a3b8', 'sort_order' => 1, 'show_to_customer' => false, 'is_final' => false, 'is_initial' => true],
            ['name' => 'In Progress', 'color' => '#f59e0b', 'sort_order' => 2, 'show_to_customer' => true, 'is_final' => false, 'is_initial' => false],
            ['name' => 'Ready', 'color' => '#22c55e', 'sort_order' => 3, 'show_to_customer' => true, 'is_final' => true, 'is_initial' => false],
        ];

        foreach ($statuses as $status) {
            PreparationStatus::create(['venue_id' => $venue->id, ...$status]);
        }

        if ($corporation->owner_id) {
            $venue->users()->attach($corporation->owner_id, ['role' => UserRole::Owner->value]);
        }

        return $venue;
    }
}
