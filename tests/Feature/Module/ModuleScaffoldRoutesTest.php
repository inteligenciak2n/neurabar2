<?php

namespace Tests\Feature\Module;

use App\Enums\ModuleCode;
use App\Enums\ModuleStatus;
use App\Enums\UserRole;
use App\Models\Tenant\CorporationModule;
use App\Models\Tenant\VenueModule;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\RefreshAllDatabases;
use Tests\TestCase;

class ModuleScaffoldRoutesTest extends TestCase
{
    use RefreshAllDatabases;

    #[DataProvider('moduleRoutesProvider')]
    public function test_module_scaffold_route_returns_ok_when_module_is_active(string $routeName, ModuleCode $moduleCode): void
    {
        $user = $this->loginAs(UserRole::Owner);
        $venue = $user->currentVenue ?? $user->venues()->first();
        CorporationModule::factory()->create([
            'corporation_id' => $venue->corporation_id,
            'module_code' => $moduleCode->value,
            'status' => ModuleStatus::Active,
        ]);
        VenueModule::factory()->create([
            'venue_id' => $venue->id,
            'module_code' => $moduleCode->value,
            'status' => ModuleStatus::Active,
        ]);

        $response = $this->actingAs($user)->get(route($routeName));

        $response->assertOk();
    }

    public static function moduleRoutesProvider(): array
    {
        return [
            'delivery' => ['delivery.index', ModuleCode::Delivery],
            'fiscal-note' => ['fiscal-note.index', ModuleCode::FiscalNote],
            'voice-command' => ['voice-command.index', ModuleCode::VoiceCommand],
            'production' => ['production.index', ModuleCode::ProductionDashboard],
            'finance' => ['finance.index', ModuleCode::FinancialDashboard],
            'direct-waiter' => ['direct-waiter.index', ModuleCode::DirectWaiter],
            'direct-print' => ['direct-print.index', ModuleCode::DirectPrint],
        ];
    }
}
