<?php

namespace App\Actions\Orders;

use App\Enums\AttendanceStatus;
use App\Http\Requests\Orders\StoreAttendanceRequest;
use App\Models\Orders\Attendance;
use App\Models\Settings\AttendanceChannel;
use App\Models\Settings\Tab;
use App\Models\Tenant\Venue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class OpenAttendanceAction
{
    public function execute(Venue $venue, StoreAttendanceRequest $request): Attendance
    {
        $data = $request->validated();

        $channel = AttendanceChannel::find($data['attendance_channel_id']);

        if ($channel?->requires_customer_identifier && empty($data['customer_identifier'])) {
            throw ValidationException::withMessages([
                'customer_identifier' => 'Customer identifier is required for this channel.',
            ]);
        }

        $tabId = $this->resolvedTabId($channel, $data['tab_id'] ?? null);

        try {
            return Attendance::create([
                'venue_id' => $venue->id,
                'attendance_channel_id' => $data['attendance_channel_id'],
                'tab_id' => $tabId,
                'customer_identifier' => $data['customer_identifier'] ?? null,
                'code' => $data['code'] ?? null,
                'service_location_id' => $data['service_location_id'] ?? null,
                'party_size' => $data['party_size'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => AttendanceStatus::Open,
                'created_by' => $request->user()->id,
            ]);
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['code' => 'This tab card is already in use.']);
        }
    }

    private function resolvedTabId(?AttendanceChannel $channel, ?string $tabId): ?string
    {
        if (! $channel?->requires_tab) {
            return null;
        }

        if ($tabId === null || $tabId === '') {
            throw ValidationException::withMessages([
                'tab_id' => 'A tab is required for this channel.',
            ]);
        }

        $tab = Tab::query()
            ->whereKey($tabId)
            ->where('active', true)
            ->first();

        if ($tab === null) {
            throw ValidationException::withMessages([
                'tab_id' => 'This tab is not available.',
            ]);
        }

        $inUse = Attendance::query()
            ->open()
            ->where('tab_id', $tab->id)
            ->exists();

        if ($inUse) {
            throw ValidationException::withMessages([
                'tab_id' => 'This tab is already in use.',
            ]);
        }

        return $tab->id;
    }
}
