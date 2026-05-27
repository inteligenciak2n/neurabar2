<?php

namespace App\Actions\Fortify;

use App\Actions\Settings\CreateUserAction;
use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Menu\Product;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Corporation;
use App\Models\Tenant\PlanCatalog;
use App\Models\Tenant\Venue;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreateUserOwnerDefinitions
{
    public function handle(User $user): void
    {
        DB::beginTransaction();
            $venue = $this->setUserDefaults($user);
            $menu = $this->createMenu($venue);
            $this->createProductCategories($menu);
            $this->createServiceLocations($venue);
            $this->createUserAttendant($venue, $user);
        DB::commit();
    }

    private function setUserDefaults(User $user): Venue
    {
        $plan = PlanCatalog::where('code', 'pro')->firstOrFail();
            
        $corporation = Corporation::create(
            [
                'tax_id' => '00.000.000/0001-00',
                'name' => 'Test Corp',
                'email' => 'corp@test.com',
                'contact_phone' => '11999990000',
                'plan_catalog_id' => $plan->id,
                'plan_name' => $plan->name,
                'subscription_value' => $plan->monthly_price,
                'active' => true,
            ]
        );

        $venue = Venue::create(
            [
                'call_waiter_slug' => 'test-bar',
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

        VenueSettings::create(
            [
                'venue_id' => $venue->id,
                'cover_charge' => 10.00,
                'service_fee_percent' => 10.00,
                'table_count' => 30,
            ]
        );


        $user->venue_id = $venue->id;
        $user->corporation_id = $corporation->id;
        $user->save();

        foreach (['Cozinha', 'Bar'] as $i => $stationName) {
            KitchenStation::create(
                [
                    'venue_id' => $venue->id,
                    'name' => $stationName,
                    'sort_order' => $i + 1,
                    'active' => true,
                ]
            );
        }

        $statuses = [
            ['name' => 'Pendente',     'color' => '#94a3b8', 'sort_order' => 1, 'show_to_customer' => false],
            ['name' => 'Em Preparo',   'color' => '#f59e0b', 'sort_order' => 2, 'show_to_customer' => false],
            ['name' => 'Pronto',       'color' => '#22c55e', 'sort_order' => 3, 'show_to_customer' => true],
        ];

        foreach ($statuses as $status) {
            PreparationStatus::create(
                array_merge($status, ['venue_id' => $venue->id])
            );
        }
        
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

        foreach ($categories as $categoryData) {
            $products = $categoryData['products'];
            unset($categoryData['products']);

            /** @var Category $category */
            $category = Category::create([
                'menu_id' => $menu->id,
                'name' => $categoryData['name'],
                'sort_order' => $categoryData['sort_order'],
            ]);

            foreach ($products as $index => $productData) {
                Product::create([
                    'category_id' => $category->id,
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'price' => $productData['price'],
                    'active' => true,
                    'available_for_delivery' => true,
                    'kitchen_station_id' => $productData['station_id'],
                ]);
            }
        }
    }

    private function createServiceLocations(Venue $venue): void
    {
        $locations = [
            ...array_map(fn (int $n) => ['name' => "Mesa {$n}", 'type' => 'table'], range(1, 10)),
            ['name' => 'Balcão', 'type' => 'counter'],
            ['name' => 'Área Externa 1', 'type' => 'table'],
            ['name' => 'Área Externa 2', 'type' => 'table'],
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

    private function createUserAttendant(Venue $venue, User $user): void
    {            
        $data = [
            'name' => 'Atendente Padrão',
            'email' => explode('@', $user->email)[0] . '+attendant@' . explode('@', $user->email)[1],
            'role' => 'attendant',
            'pin' => null,
            'active' => true,
        ];

        User::create([
            'venue_id' => $venue->id,
            'corporation_id' => $venue->corporation_id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $user->password, // Hash::make($data['password']),
            'role' => $data['role'],
            'pin' => $data['pin'] ?? null,
            'active' => $data['active'] ?? true,
        ]);
    }
}
