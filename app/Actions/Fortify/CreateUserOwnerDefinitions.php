<?php

namespace App\Actions\Fortify;

use App\Actions\Corporation\CreateVenueDefaultsAction;
use App\Actions\Corporation\ProvisionPlanModulesAction;
use App\Enums\BillingMode;
use App\Enums\ServiceLocationType;
use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\ServiceLocation;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\CorporationSubscription;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\Tenant\VenueSubscription;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateUserOwnerDefinitions
{
    public function handle(User $user, ?PlanCatalog $plan = null): void
    {
        $operationalConnection = 'operation_default_1';

        DB::beginTransaction();
        DB::connection($operationalConnection)->beginTransaction();

        try {
            $venue = $this->createCorporationAndVenue($user, $operationalConnection, $plan);
            $menu = $this->createMenu($venue);
            $this->createProductCategories($menu);
            $this->createServiceLocations($venue);
            $this->createUserAttendant($venue, $user);

            DB::commit();
            DB::connection($operationalConnection)->commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            DB::connection($operationalConnection)->rollBack();
            throw $e;
        }
    }

    private function createCorporationAndVenue(User $user, string $operationalConnection, ?PlanCatalog $plan = null): Venue
    {
        $plan ??= PlanCatalog::firstOrCreate(
            ['code' => config('billing.default_plan_code', 'pro')],
            [
                'name' => 'Pro',
                'monthly_price' => 99.90,
                'active' => true,
                'plan_type' => 'shared',
            ]
        );

        $corporation = Corporation::create([
            'owner_id' => $user->id,
            'tax_id' => '00.000.000/0001-00',
            'name' => 'Test Corp',
            'email' => 'corp@test.com',
            'contact_phone' => '11999990000',
            'active' => true,
            'self_connection' => $operationalConnection,
            'is_dedicated' => false,
        ]);

        $corporationSubscription = CorporationSubscription::create([
            'corporation_id' => $corporation->id,
            'plan_catalog_id' => $plan->id,
            'billing_mode' => BillingMode::PerVenue,
            'status' => SubscriptionStatus::Trial,
            'billing_day' => config('billing.default_billing_day', 1),
            'grace_period_days' => config('billing.grace_period_days', 3),
            'started_at' => now(),
            'trial_ends_at' => now()->addDays(config('billing.trial_days', 14)),
            'currency' => config('billing.currency', 'BRL'),
        ]);

        $venue = Venue::create([
            'call_waiter_slug' => strtolower(str_replace(' ', '-', $user->name)).'-call-attendant',
            'corporation_id' => $corporation->id,
            'name' => $user->name.' ponto de venda',
            'tax_id' => '00.000.000/0001-00',
            'phone' => '11999990000',
            'city' => 'São Paulo',
            'state' => 'SP',
            'timezone' => 'America/Sao_Paulo',
            'active' => true,
        ]);

        VenueSubscription::create([
            'venue_id' => $venue->id,
            'corporation_subscription_id' => $corporationSubscription->id,
            'plan_catalog_id' => $plan->id,
            'status' => SubscriptionStatus::Trial,
            'base_value' => $plan->monthly_price,
            'total_value' => $plan->monthly_price,
            'started_at' => now(),
            'trial_ends_at' => $corporationSubscription->trial_ends_at,
        ]);

        // Registra o contexto operacional para que HasOperationalConnection use a conexão correta
        app()->instance('operational_connection', $operationalConnection);
        app()->instance('tenant', $venue);

        (new CreateVenueDefaultsAction)->execute($venue, [
            'cover_charge' => 10.00,
            'service_fee_percent' => 10.00,
            'table_count' => 30,
        ]);

        app(ProvisionPlanModulesAction::class)->execute($corporation, $venue, $plan);

        $venue->users()->attach($user->id, ['role' => UserRole::Owner->value]);

        $user->current_venue_id = $venue->id;
        $user->save();

        return $venue;
    }

    private function createMenu(Venue $venue): Menu
    {
        return Menu::create([
            'venue_id' => $venue->id,
            'name' => 'Cardápio Principal',
            'active' => true,
        ]);
    }

    private function createProductCategories(Menu $menu): void
    {
        $cozinha = KitchenStation::where('venue_id', $menu->venue_id)->where('name', 'Cozinha')->first();
        $bar = KitchenStation::where('venue_id', $menu->venue_id)->where('name', 'Bar')->first();

        $categories = [
            [
                'name' => 'Bebidas',
                'sort_order' => 1,
                'products' => [
                    ['name' => 'Cerveja Long Neck', 'description' => 'Cerveja gelada 355ml', 'price' => 9.90, 'station_id' => $bar->id],
                    ['name' => 'Cerveja Lata', 'description' => 'Cerveja gelada 350ml', 'price' => 6.90, 'station_id' => $bar->id],
                    ['name' => 'Refrigerante Lata', 'description' => 'Coca-Cola, Guaraná, Sprite 350ml', 'price' => 5.90, 'station_id' => $bar->id],
                    ['name' => 'Água Mineral', 'description' => 'Garrafa 500ml', 'price' => 3.50, 'station_id' => $bar->id],
                    ['name' => 'Suco Natural', 'description' => 'Laranja, limão ou maracujá 300ml', 'price' => 8.00, 'station_id' => $bar->id],
                    ['name' => 'Caipirinha', 'description' => 'Cachaça, limão e açúcar', 'price' => 14.00, 'station_id' => $bar->id],
                ],
            ],
            [
                'name' => 'Porções',
                'sort_order' => 2,
                'products' => [
                    ['name' => 'Frango à Passarinho', 'description' => 'Porção de frango frito temperado (500g)', 'price' => 39.90, 'station_id' => $cozinha->id],
                    ['name' => 'Batata Frita', 'description' => 'Porção de batata frita crocante (400g)', 'price' => 24.90, 'station_id' => $cozinha->id],
                    ['name' => 'Calabresa Acebolada', 'description' => 'Calabresa grelhada com cebola (400g)', 'price' => 34.90, 'station_id' => $cozinha->id],
                    ['name' => 'Isca de Peixe', 'description' => 'Tilápia frita com molho tártaro (400g)', 'price' => 42.90, 'station_id' => $cozinha->id],
                    ['name' => 'Mandioca Frita', 'description' => 'Mandioca cozida e frita com manteiga (400g)', 'price' => 22.90, 'station_id' => $cozinha->id],
                ],
            ],
            [
                'name' => 'Lanches',
                'sort_order' => 3,
                'products' => [
                    ['name' => 'X-Burguer', 'description' => 'Hambúrguer, queijo, alface e tomate', 'price' => 22.90, 'station_id' => $cozinha->id],
                    ['name' => 'X-Bacon', 'description' => 'Hambúrguer, bacon, queijo e molho especial', 'price' => 27.90, 'station_id' => $cozinha->id],
                    ['name' => 'Misto Quente', 'description' => 'Pão de forma, presunto e queijo', 'price' => 12.90, 'station_id' => $cozinha->id],
                    ['name' => 'Cachorro-Quente', 'description' => 'Salsicha, molho de tomate, purê e batata palha', 'price' => 15.90, 'station_id' => $cozinha->id],
                ],
            ],
            [
                'name' => 'Sobremesas',
                'sort_order' => 4,
                'products' => [
                    ['name' => 'Pudim de Leite', 'description' => 'Pudim caseiro com calda de caramelo', 'price' => 12.00, 'station_id' => $cozinha->id],
                    ['name' => 'Sorvete', 'description' => 'Duas bolas, sabores variados', 'price' => 10.00, 'station_id' => $cozinha->id],
                    ['name' => 'Brownie', 'description' => 'Brownie de chocolate com sorvete', 'price' => 16.00, 'station_id' => $cozinha->id],
                ],
            ],
        ];

        $combo = [
            'X-Bacon',
            'Batata Frita',
            'Refrigerante Lata',
        ];

        $productsCombo = [];

        foreach ($categories as $categoryData) {
            $products = $categoryData['products'];
            unset($categoryData['products']);

            /** @var Category $category */
            $category = Category::create([
                'menu_id' => $menu->id,
                'name' => $categoryData['name'],
                'sort_order' => $categoryData['sort_order'],
            ]);

            foreach ($products as $productData) {
                $product = Product::create([
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'active' => true,
                    'available_for_delivery' => true,
                    'kitchen_station_id' => $productData['station_id'],
                ]);
                if (in_array($productData['name'], $combo)) {
                    $productsCombo[$productData['name']] = $product;
                }
            }
        }

        $modifier = ModifierGroup::create([
            'venue_id' => $menu->venue_id,
            'name' => 'Queijo Extra',
            'required' => false,
            'multiple_selection' => true,
        ]);

        $modifier->options()->createMany([
            ['name' => 'Cheddar', 'extra_price' => 2.00],
            ['name' => 'Mussarela', 'extra_price' => 1.50],
            ['name' => 'Gorgonzola', 'extra_price' => 3.00],
        ]);

        $combo = Combo::create([
            'venue_id' => $menu->venue_id,
            'name' => 'Combo Feliz X-Bacon',
            'description' => 'X-Bacon + Batata Frita + Refrigerante Lata',
            'price' => 49.90,
            'active' => true,
        ]);

        $combo->items()->createMany([
            ['product_id' => $productsCombo['X-Bacon']->id, 'quantity' => 1],
            ['product_id' => $productsCombo['Batata Frita']->id, 'quantity' => 1],
            ['product_id' => $productsCombo['Refrigerante Lata']->id, 'quantity' => 1],
        ]);

    }

    private function createServiceLocations(Venue $venue): void
    {
        $locations = [
            ...array_map(fn (int $n) => ['name' => "Mesa {$n}", 'type' => ServiceLocationType::Table], range(1, 10)),
            ['name' => 'Balcão', 'type' => ServiceLocationType::Bar],
            ['name' => 'Área Externa 1', 'type' => ServiceLocationType::Area],
            ['name' => 'Área Externa 2', 'type' => ServiceLocationType::Area],
        ];

        foreach ($locations as $location) {
            ServiceLocation::create([
                'venue_id' => $venue->id,
                'name' => $location['name'],
                'type' => $location['type'],
                'active' => true,
            ]);
        }
    }

    private function createUserAttendant(Venue $venue, User $owner): void
    {
        $emailParts = explode('@', $owner->email);
        $attendantEmail = $emailParts[0].'+attendant@'.$emailParts[1];

        $attendant = User::create([
            'name' => 'Atendente Padrão',
            'email' => $attendantEmail,
            'password' => $owner->password,
            'pin' => null,
            'active' => true,
        ]);

        $venue->users()->attach($attendant->id, ['role' => UserRole::Attendant->value]);

        $attendant->current_venue_id = $venue->id;
        $attendant->save();
    }
}
