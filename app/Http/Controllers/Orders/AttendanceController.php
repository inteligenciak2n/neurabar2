<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\LinkAttendanceTabAction;
use App\Actions\Orders\OpenAttendanceAction;
use App\Actions\Orders\TakeAttendantOrderAction;
use App\Enums\ServiceRequestType;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Menu\MenuPreviewController;
use App\Http\Requests\Orders\LinkAttendanceTabRequest;
use App\Http\Requests\Orders\StoreAttendanceRequest;
use App\Http\Requests\Orders\StoreAttendantOrderRequest;
use App\Http\Requests\Orders\UpdateAttendanceRequest;
use App\Models\Orders\Attendance;
use App\Models\Orders\ServiceRequest;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\ServiceLocation;
use App\Models\Settings\Tab;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(): Response
    {
        return $this->renderBoard();
    }

    public function create(): Response
    {
        $mode = request()->query('mode', 'order');

        return app(MenuPreviewController::class)->live(is_string($mode) ? $mode : 'order');
    }

    public function takeOrder(StoreAttendantOrderRequest $request, TakeAttendantOrderAction $action): RedirectResponse
    {
        $action->execute(app('tenant'), $request);

        return back();
    }

    public function store(StoreAttendanceRequest $request, OpenAttendanceAction $action)
    {
        $venue = app('tenant');

        $attendance = $action->execute($venue, $request);

        if ($request->boolean('stay')) {
            return back();
        }

        return redirect()->route('orders.take', $attendance);
    }

    public function show(Attendance $attendance): Response
    {
        $venue = app('tenant');

        $attendance->load([
            'orders' => fn ($q) => $q->latest(),
            'orders.items.product.category:id,name,color,sort_order',
            'orders.items.combo:id,name',
            'orders.items.preparationStatus:id,name,color,is_final',
            'attendanceChannel:id,name,requires_tab',
            'tab:id,code',
            'serviceLocation',
            'createdBy',
            'deliveryAddress',
        ]);

        $tableAttendances = collect();

        if ($attendance->service_location_id) {
            $tableAttendances = Attendance::open()
                ->where('service_location_id', $attendance->service_location_id)
                ->with([
                    'tab:id,code',
                    'createdBy',
                    'orders' => fn ($q) => $q->latest(),
                    'orders.items.product.category:id,name,color,sort_order',
                    'orders.items.combo:id,name',
                    'orders.items.preparationStatus:id,name,color,is_final',
                ])
                ->get()
                ->sortBy(fn (Attendance $item): string => (string) ($item->tab?->code ?? $item->code ?? ''), SORT_NATURAL)
                ->values();
        }

        $occupiedTabIds = Attendance::open()->whereNotNull('tab_id')->pluck('tab_id');

        $availableTabs = Tab::query()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->whereNotIn('id', $occupiedTabIds)
            ->orderByRaw('length(code), code')
            ->get(['id', 'code']);

        return Inertia::render('Attendances/Show', [
            'attendance' => $attendance,
            'venueId' => $venue->id,
            'tableAttendances' => $tableAttendances,
            'availableTabs' => $availableTabs,
        ]);
    }

    public function orders(Attendance $attendance): JsonResponse
    {
        $attendance->load(['orders' => fn ($q) => $q->latest(), 'orders.items.product', 'orders.items.preparationStatus']);

        return response()->json($attendance->orders);
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance)
    {
        try {
            $attendance->update($request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'This tab card is already in use.']);
        }

        return redirect()->route('attendances.show', $attendance->id);
    }

    public function linkTab(LinkAttendanceTabRequest $request, Attendance $attendance, LinkAttendanceTabAction $action): RedirectResponse
    {
        $originalId = $attendance->id;
        $linked = $action->execute($attendance, $request->validated('tab_id'));
        $previous = (string) url()->previous();

        if ($linked->id !== $originalId && str_contains($previous, $originalId)) {
            return redirect()->route('attendances.show', $linked->id)
                ->with('success', 'Tab linked.');
        }

        return back()->with('success', 'Tab linked.');
    }

    private function renderBoard(bool $startWithForm = false): Response
    {
        $venue = app('tenant');

        $attendances = Attendance::open()
            ->with([
                'attendanceChannel:id,name,requires_tab',
                'tab:id,code',
                'serviceLocation',
                'createdBy',
                'deliveryAddress',
                'orders' => fn ($q) => $q->latest(),
                'orders.items.product:id,name',
                'orders.items.combo:id,name',
            ])
            ->latest()
            ->get();

        $serviceLocations = ServiceLocation::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'default_attendance_channel_id']);

        $occupiedTabIds = Attendance::open()->whereNotNull('tab_id')->pluck('tab_id');

        $channels = AttendanceChannel::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'requires_tab', 'requires_customer_identifier', 'uses_service_location', 'color']);

        $tabs = Tab::query()
            ->where('venue_id', $venue->id)
            ->where('active', true)
            ->whereNotIn('id', $occupiedTabIds)
            ->orderByRaw('length(code), code')
            ->get(['id', 'code', 'tab_series_id']);

        // Chamados de "chamar garçom p/ anotar pedido" e "fechar conta" — as
        // mensagens de texto do Direct Garçom têm painel próprio (/direct-waiter).
        $serviceCallRequests = ServiceRequest::open()
            ->where('type', '!=', ServiceRequestType::Message)
            ->latest()
            ->limit(50)
            ->get(['id', 'service_location_id', 'attendance_id', 'type', 'status', 'created_at']);

        return Inertia::render('Attendances/Index', [
            'attendances' => $attendances,
            'serviceLocations' => $serviceLocations,
            'channels' => $channels,
            'tabs' => $tabs,
            'tabSeries' => $venue->tabSeries()->orderBy('prefix')->get(['id', 'prefix', 'notes']),
            'venueId' => $venue->id,
            'serviceCallRequests' => $serviceCallRequests,
            'startWithForm' => $startWithForm,
        ]);
    }
}
