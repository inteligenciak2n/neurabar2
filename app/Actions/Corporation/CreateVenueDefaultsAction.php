<?php

namespace App\Actions\Corporation;

use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;

class CreateVenueDefaultsAction
{
    /**
     * @param  array<string, mixed>  $venueSettings
     */
    public function execute(Venue $venue, array $venueSettings = []): void
    {
        VenueSettings::firstOrCreate(
            ['venue_id' => $venue->id],
            [
                'cover_charge' => $venueSettings['cover_charge'] ?? 0.00,
                'service_fee_percent' => $venueSettings['service_fee_percent'] ?? 0.00,
                'table_count' => $venueSettings['table_count'] ?? 0,
            ]
        );

        $stations = ['Cozinha', 'Bar'];
        foreach ($stations as $i => $name) {
            KitchenStation::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $name],
                ['sort_order' => $i + 1, 'active' => true]
            );
        }

        $statuses = [
            ['name' => 'Pendente', 'color' => '#94a3b8', 'sort_order' => 1, 'show_to_customer' => false, 'is_final' => false, 'is_initial' => true],
            ['name' => 'Em Preparo', 'color' => '#f59e0b', 'sort_order' => 2, 'show_to_customer' => true, 'is_final' => false, 'is_initial' => false],
            ['name' => 'Pronto', 'color' => '#22c55e', 'sort_order' => 3, 'show_to_customer' => true, 'is_final' => true, 'is_initial' => false],
        ];

        foreach ($statuses as $status) {
            PreparationStatus::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $status['name']],
                $status
            );
        }

        $channels = [
            ['name' => 'Mesa', 'sort_order' => 1],
            ['name' => 'Balcão', 'sort_order' => 2],
            ['name' => 'Delivery', 'sort_order' => 3],
            ['name' => 'Retirada', 'sort_order' => 4],
        ];

        foreach ($channels as $channel) {
            AttendanceChannel::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $channel['name']],
                [...$channel, 'active' => true]
            );
        }
    }
}
