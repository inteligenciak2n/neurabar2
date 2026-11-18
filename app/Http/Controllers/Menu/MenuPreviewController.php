<?php

namespace App\Http\Controllers\Menu;

use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Menu\Category;
use App\Models\Menu\Combo;
use App\Models\Menu\Menu;
use App\Models\Orders\Attendance;
use App\Models\Orders\AttendanceDeliveryAddress;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\DeliveryFeeZone;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use App\Models\Settings\VenueSettings;
use App\Models\Tenant\Venue;
use App\Support\DeliveryAddressFormatter;
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
            'hasMenu' => true,
            'hasDirectWaiter' => true,
            'hasSelfOrder' => false,
            'pendingWaiterCall' => null,
            'waiterCallPhrases' => VenueSettings::phrasesForVenue($venue->id),
        ]);
    }

    public function customerDelivery(): Response
    {
        $venue = app('tenant');
        $settings = VenueSettings::withoutGlobalScopes()->where('venue_id', $venue->id)->first();

        return Inertia::render('Guest/Delivery/Menu', [
            'token' => null,
            'preview' => true,
            'venue' => $venue->only('id', 'name', 'description', 'logo_url', 'street', 'number', 'complement', 'neighborhood', 'city', 'state', 'zip_code', 'require_geolocation'),
            'categories' => $this->catalogCategories($venue, customerFacing: true),
            'deliveryEnabled' => $settings?->delivery_enabled ?? true,
            'pickupEnabled' => $settings?->pickup_enabled ?? true,
            'acceptedPaymentMethods' => $settings?->acceptedDeliveryPaymentMethods() ?? PaymentMethod::values(),
            'serviceFeePercent' => (float) ($settings?->service_fee_percent ?? 0),
        ]);
    }

    public function attendant(): Response
    {
        return $this->renderAttendant(preview: true);
    }

    public function live(?string $startMode = 'order', ?Attendance $attendance = null): Response
    {
        $mode = in_array($startMode, ['order', 'table', 'tab'], true) ? $startMode : 'order';

        return $this->renderAttendant(preview: false, startMode: $mode, attendance: $attendance);
    }

    private function renderAttendant(bool $preview, string $startMode = 'order', ?Attendance $attendance = null): Response
    {
        $venue = app('tenant');

        return Inertia::render('Menu/PreviewAttendant', [
            'preview' => $preview,
            'startMode' => $startMode,
            'attendance' => $this->attendantAttendancePayload($attendance),
            'venue' => $venue->only('id', 'name', 'description', 'logo_url', 'city', 'state'),
            'deliveryNeighborhoods' => $this->attendantNeighborhoods($venue),
            'channels' => AttendanceChannel::withoutGlobalScopes()
                ->where('venue_id', $venue->id)
                ->where('active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['id', 'name', 'color', 'requires_tab', 'requires_customer_identifier', 'uses_service_location']),
            'serviceLocations' => ServiceLocation::withoutGlobalScopes()
                ->where('venue_id', $venue->id)
                ->where('active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'default_attendance_channel_id']),
            'tabs' => Tab::query()
                ->where('venue_id', $venue->id)
                ->where('active', true)
                ->orderByRaw('length(code), code')
                ->get(['id', 'code']),
            'attendances' => Attendance::open()
                ->with([
                    'attendanceChannel:id,name,color',
                    'tab:id,code',
                    'serviceLocation:id,name',
                    'venueCustomer:id,name,phone,address,comment',
                    'deliveryAddress',
                    'orders' => fn ($q) => $q->latest(),
                    'orders.items.product:id,name',
                    'orders.items.combo:id,name',
                    'orders.items.preparationStatus:id,name,color,is_final',
                ])
                ->latest()
                ->get(),
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
     * @return array{id: string|null, name: string}
     */
    private function previewTable(Venue $venue): array
    {
        $table = ServiceLocation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->orderBy('name')
            ->first();

        if ($table) {
            return [
                'id' => $table->id,
                'name' => $table->name,
            ];
        }

        return [
            'id' => null,
            'name' => 'Mesa 1',
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    private function catalogCategories(Venue $venue, bool $customerFacing): Collection
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

        return $customerFacing
            ? $categories->filter(fn (Category $category) => $category->products->isNotEmpty())->values()
            : $categories;
    }

    /**
     * @return array{
     *     id: string,
     *     attendance_channel_id: string|null,
     *     tab_id: string|null,
     *     service_location_id: string|null,
     *     customer_identifier: string|null,
     *     customer: array{id: string, name: string, phone: string, address: string|null, comment: string|null}|null,
     *     tab: array{id: string, code: string}|null,
     *     service_location: array{id: string, name: string}|null
     * }|null
     */
    private function attendantAttendancePayload(?Attendance $attendance): ?array
    {
        if (! $attendance) {
            return null;
        }

        $attendance->loadMissing(['tab:id,code', 'serviceLocation:id,name', 'venueCustomer', 'deliveryAddress']);

        return [
            'id' => $attendance->id,
            'attendance_channel_id' => $attendance->attendance_channel_id,
            'tab_id' => $attendance->tab_id,
            'service_location_id' => $attendance->service_location_id,
            'customer_identifier' => $attendance->customer_identifier,
            'customer' => $attendance->venueCustomer
                ? $attendance->venueCustomer->only('id', 'name', 'phone', 'address', 'comment')
                : null,
            'delivery_address' => $this->deliveryAddressPayload($attendance->deliveryAddress),
            'tab' => $attendance->tab ? $attendance->tab->only('id', 'code') : null,
            'service_location' => $attendance->serviceLocation
                ? $attendance->serviceLocation->only('id', 'name')
                : null,
        ];
    }

    /**
     * @return Collection<int, array{id: string, name: string, fee: float, has_label: bool}>
     */
    private function attendantNeighborhoods(Venue $venue): Collection
    {
        return DeliveryFeeZone::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get(['id', 'label', 'fee', 'zip_code_start', 'zip_code_end'])
            ->map(fn (DeliveryFeeZone $zone): array => [
                'id' => $zone->id,
                'name' => filled($zone->label)
                    ? $zone->label
                    : DeliveryAddressFormatter::formatZip((string) $zone->zip_code_start)
                        .'–'
                        .DeliveryAddressFormatter::formatZip((string) $zone->zip_code_end),
                'fee' => (float) $zone->fee,
                'has_label' => filled($zone->label),
            ])
            ->values();
    }

    /**
     * @return array{
     *     zip_code: string,
     *     street: string,
     *     number: string,
     *     complement: string|null,
     *     delivery_fee_zone_id: string|null,
     *     neighborhood: string,
     *     city: string,
     *     state: string,
     *     reference_point: string|null,
     *     delivery_phone: string|null,
     *     delivery_fee: float,
     *     line: string,
     *     maps_url: string|null
     * }|null
     */
    private function deliveryAddressPayload(?AttendanceDeliveryAddress $address): ?array
    {
        if (! $address) {
            return null;
        }

        return [
            'zip_code' => DeliveryAddressFormatter::formatZip($address->zip_code),
            'street' => $address->street,
            'number' => $address->number,
            'complement' => $address->complement,
            'delivery_fee_zone_id' => $address->delivery_fee_zone_id,
            'neighborhood' => $address->neighborhood,
            'city' => $address->city,
            'state' => $address->state,
            'reference_point' => $address->reference_point,
            'delivery_phone' => $address->delivery_phone
                ? DeliveryAddressFormatter::formatPhone($address->delivery_phone)
                : null,
            'delivery_fee' => (float) $address->delivery_fee,
            'line' => $address->line,
            'maps_url' => $address->maps_url,
        ];
    }
}
