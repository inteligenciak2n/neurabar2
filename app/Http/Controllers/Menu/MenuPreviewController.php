<?php

namespace App\Http\Controllers\Menu;

use App\Enums\PaymentMethod;
use App\Enums\ServiceLocationType;
use App\Http\Controllers\Controller;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class MenuPreviewController extends Controller
{
    public function customer(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Guest/Menu', [
            'token' => null,
            'preview' => true,
            'venue' => $venue->only('id', 'name', 'description', 'logo_url', 'require_geolocation'),
            'serviceLocation' => $this->previewTable($venue),
            'categories' => $this->catalogCategories($venue, customerFacing: true),
        ]);
    }

    public function customerDelivery(): Response
    {
        $venue = app('tenant');
        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        return Inertia::render('Guest/Delivery/Menu', [
            'token' => null,
            'preview' => true,
            'venue' => $venue->only('id', 'name', 'description', 'logo_url', 'require_geolocation'),
            'categories' => $this->catalogCategories($venue, customerFacing: true, deliveryOnly: true),
            'deliveryEnabled' => $settings?->delivery_enabled ?? true,
            'pickupEnabled' => $settings?->pickup_enabled ?? true,
            'acceptedPaymentMethods' => $settings?->acceptedDeliveryPaymentMethods() ?? PaymentMethod::values(),
            'serviceFeePercent' => (float) ($settings?->service_fee_percent ?? 0),
        ]);
    }

    public function attendant(): Response
    {
        $venue = app('tenant');

        return Inertia::render('Menu/PreviewAttendant', [
            'venue' => $venue->only('id', 'name', 'logo_url'),
            'categories' => $this->catalogCategories($venue, customerFacing: false),
            'combos' => Combo::withoutGlobalScopes()
                ->where('venue_id', $venue->id)
                ->where('active', true)
                ->with(['items.product', 'items.variation'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    /**
     * @return array{id: string|null, name: string, type: string}
     */
    private function previewTable(Venue $venue): array
    {
        $table = ServiceLocation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('type', ServiceLocationType::Table)
            ->where('active', true)
            ->orderBy('name')
            ->first();

        if ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
                'type' => $table->type instanceof ServiceLocationType
                    ? $table->type->value
                    : (string) $table->type,
            ];
        }

        return [
            'id' => null,
            'name' => 'Mesa 1',
            'type' => ServiceLocationType::Table->value,
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    private function catalogCategories(Venue $venue, bool $customerFacing, bool $deliveryOnly = false): Collection
    {
        $menuQuery = Menu::withoutGlobalScopes()->where('venue_id', $venue->id);

        if ($customerFacing) {
            $menuQuery->where('active', true);
        }

        $menu = $menuQuery->first();

        if (! $menu) {
            return collect();
        }

        $categories = Category::withoutGlobalScopes()
            ->where('menu_id', $menu->id)
            ->orderBy('sort_order')
            ->with([
                'products' => fn ($q) => $q
                    ->where('active', true)
                    ->when($deliveryOnly, fn ($q) => $q->where('available_for_delivery', true))
                    ->orderBy('sort_order')
                    ->orderBy('name')
                    ->with([
                        'variations' => fn ($q) => $q->where('active', true),
                        'modifierGroups' => fn ($q) => $q->with([
                            'options' => fn ($q) => $q->where('active', true),
                        ]),
                    ]),
            ])
            ->get();

        if ($deliveryOnly) {
            return $categories->filter(fn (Category $category) => $category->products->isNotEmpty())->values();
        }

        return $categories;
    }
}
