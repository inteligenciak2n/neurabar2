<?php

namespace App\Actions\Corporation;

use App\Enums\ServiceLocationType;
use App\Enums\UserRole;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Menu\ModifierGroup;
use App\Models\Menu\Product;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\KitchenStation;
use App\Models\Settings\PreparationStatus;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use App\Models\User;

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

        // adiciona dados padrões de cozinha, status de preparo, cardápio, categorias de produtos, canais de atendimento e localizações de serviço
        // para que o user possa começar a usar o sistema sem precisar configurar tudo manualmente

        $kitchenStations = $this->createKitchenStations($venue);

        $this->createPreparationStatuses($venue);

        $menu = $this->createMenu($venue);

        $this->createProductCategories($menu, $kitchenStations);

        $channels = $this->createChannels($venue);

        $this->createServiceLocations($venue, $channels);

        $this->createUserAttendant($venue, $venue->corporation->owner);
    }

    private function createPreparationStatuses(Venue $venue): void
    {
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
    }

    /**
     * @return array<string, KitchenStation>
     */
    private function createKitchenStations(Venue $venue): array
    {
        $stations = ['Cozinha', 'Bar'];
        $created = [];

        foreach ($stations as $i => $name) {
            $created[$name] = KitchenStation::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $name],
                ['sort_order' => $i + 1, 'active' => true]
            );
        }

        return $created;
    }

    /**
     * @return array<string, AttendanceChannel>
     */
    private function createChannels(Venue $venue): array
    {
        $channels = [
            ['name' => 'Mesa', 'sort_order' => 1],
            ['name' => 'Balcão', 'sort_order' => 2],
            ['name' => 'Delivery', 'sort_order' => 3],
            ['name' => 'Retirada', 'sort_order' => 4],
        ];

        $created = [];

        foreach ($channels as $channel) {
            $created[$channel['name']] = AttendanceChannel::firstOrCreate(
                ['venue_id' => $venue->id, 'name' => $channel['name']],
                [...$channel, 'active' => true]
            );
        }

        return $created;
    }

    private function createMenu(Venue $venue): Menu
    {
        return Menu::create([
            'venue_id' => $venue->id,
            'name' => 'Cardápio Principal',
            'active' => true,
        ]);
    }

    /**
     * @param  array<string, KitchenStation>  $stations
     */
    private function createProductCategories(Menu $menu, array $stations): void
    {
        $cozinha = $stations['Cozinha'];
        $bar = $stations['Bar'];

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

    /**
     * @param  array<string, AttendanceChannel>  $channels
     */
    private function createServiceLocations(Venue $venue, array $channels): void
    {
        $balcaoChannel = $channels['Balcão'] ?? null;
        $tableChannel = $channels['Mesa'] ?? null;

        $locations = [
            ...array_map(fn (int $n) => ['name' => "Mesa {$n}", 'type' => ServiceLocationType::Table, 'default_attendance_channel_id' => $tableChannel?->id], range(1, 3)),
            ['name' => 'Balcão', 'type' => ServiceLocationType::Bar, 'default_attendance_channel_id' => $balcaoChannel?->id],
            ['name' => 'Área Externa 1', 'type' => ServiceLocationType::Area, 'default_attendance_channel_id' => null],
        ];

        foreach ($locations as $location) {
            ServiceLocation::create([
                'venue_id' => $venue->id,
                'name' => $location['name'],
                'type' => $location['type'],
                'active' => true,
                'default_attendance_channel_id' => $location['default_attendance_channel_id'],
            ]);
        }
    }

    private function createUserAttendant(Venue $venue, User $owner): void
    {
        $emailParts = explode('@', $owner->email);
        $attendantEmail = $emailParts[0].'+attendant@'.$emailParts[1];

        $attendant = User::firstOrCreate(
            ['email' => $attendantEmail],
            [
                'name' => 'Atendente Padrão',
                'password' => $owner->password,
                'pin' => null,
                'active' => true,
            ]
        );

        $venue->users()->attach($attendant->id, ['role' => UserRole::Attendant->value]);

        $attendant->current_venue_id = $venue->id;
        $attendant->save();
    }
}
