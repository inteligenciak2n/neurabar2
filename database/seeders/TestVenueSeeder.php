<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestVenueSeeder extends Seeder
{
    public function run(): void
    {
        $plan = PlanCatalog::where('code', 'pro')->firstOrFail();

        $corporation = Corporation::firstOrCreate(
            ['tax_id' => '00.000.000/0001-00'],
            [
                'name' => 'Test Corp',
                'email' => 'corp@test.com',
                'contact_phone' => '11999990000',
                'plan_catalog_id' => $plan->id,
                'plan_name' => $plan->name,
                'subscription_value' => $plan->monthly_price,
                'active' => true,
            ]
        );

        $venue = Venue::firstOrCreate(
            ['call_waiter_slug' => 'test-bar'],
            [
                'corporation_id' => $corporation->id,
                'name' => 'Test Bar',
                'tax_id' => '00.000.000/0001-00',
                'phone' => '11999990000',
                'city' => 'São Paulo',
                'state' => 'SP',
                'timezone' => 'America/Sao_Paulo',
                'active' => true,
            ]
        );

        VenueSettings::firstOrCreate(
            ['venue_id' => $venue->id],
            [
                'cover_charge' => 10.00,
                'service_fee_percent' => 10.00,
                'table_count' => 30,
            ]
        );

        User::firstOrCreate(
            ['email' => 'owner@test.com'],
            [
                'name' => 'Owner Test',
                'password' => Hash::make('password'),
                'role' => UserRole::Owner,
                'venue_id' => $venue->id,
                'active' => true,
            ]
        );

        foreach (['Kitchen', 'Bar'] as $i => $stationName) {
            KitchenStation::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $stationName],
                ['sort_order' => $i + 1, 'active' => true]
            );
        }

        $statuses = [
            ['name' => 'Pendente',     'color' => '#94a3b8', 'sort_order' => 1, 'show_to_customer' => false],
            ['name' => 'Em Preparo',   'color' => '#f59e0b', 'sort_order' => 2, 'show_to_customer' => false],
            ['name' => 'Pronto',       'color' => '#22c55e', 'sort_order' => 3, 'show_to_customer' => true],
        ];

        foreach ($statuses as $status) {
            PreparationStatus::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $status['name']],
                array_merge($status, ['venue_id' => $venue->id])
            );
        }
    }
}
