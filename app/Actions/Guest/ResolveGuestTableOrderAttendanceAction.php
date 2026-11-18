<?php

namespace App\Actions\Guest;

use App\Enums\AttendanceStatus;
use App\Models\GuestSession;
use App\Models\Orders\Attendance;
use App\Services\GuestTokenService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ResolveGuestTableOrderAttendanceAction
{
    public function __construct(private readonly GuestTokenService $guestTokenService) {}

    /**
     * @param  array{attendance_id?: string|null, waiter_defines?: bool, tab_code?: string|null}  $choice
     * @return array{attendance: ?Attendance, needs_choice: bool, tabs: list<array{id: string, code: string}>}
     */
    public function execute(GuestSession $session, array $choice = []): array
    {
        if ($session->service_location_id === null) {
            $attendance = $this->guestTokenService->createAttendanceIfNeeded(
                $session,
                $choice['tab_code'] ?? null,
            );

            return [
                'attendance' => $attendance,
                'needs_choice' => false,
                'tabs' => [],
            ];
        }

        if ($session->attendance_id !== null) {
            return $this->resolveBoundAttendance($session);
        }

        $open = $this->openAttendancesAtTable($session);
        $comandas = $open->filter(fn (Attendance $attendance): bool => $attendance->hasTab())->values();

        if ($comandas->count() <= 1) {
            $attendance = $comandas->first()
                ?? $open->first(fn (Attendance $attendance): bool => $attendance->needsTabLink())
                ?? $this->openUnassigned($session);

            $this->bindSession($session, $attendance);

            return [
                'attendance' => $attendance,
                'needs_choice' => false,
                'tabs' => [],
            ];
        }

        $tabs = $comandas
            ->map(fn (Attendance $attendance): array => [
                'id' => $attendance->id,
                'code' => (string) $attendance->tabLabel(),
            ])
            ->values()
            ->all();

        if (($choice['waiter_defines'] ?? false) === true) {
            $attendance = $open->first(fn (Attendance $item): bool => $item->needsTabLink())
                ?? $this->openUnassigned($session);

            $this->bindSession($session, $attendance);

            return [
                'attendance' => $attendance,
                'needs_choice' => false,
                'tabs' => [],
            ];
        }

        $pickedId = $choice['attendance_id'] ?? null;

        if (is_string($pickedId) && $pickedId !== '') {
            $picked = $comandas->firstWhere('id', $pickedId);

            if ($picked === null) {
                throw ValidationException::withMessages([
                    'attendance_id' => 'Choose a tab from this table.',
                ]);
            }

            $this->bindSession($session, $picked);

            return [
                'attendance' => $picked,
                'needs_choice' => false,
                'tabs' => [],
            ];
        }

        return [
            'attendance' => null,
            'needs_choice' => true,
            'tabs' => $tabs,
        ];
    }

    /**
     * A session that already picked a tab keeps ordering against it while it stays
     * open. A closed (or missing) tab fails explicitly instead of silently moving
     * the guest to another open tab on the same table.
     *
     * @return array{attendance: Attendance, needs_choice: false, tabs: array{}}
     */
    private function resolveBoundAttendance(GuestSession $session): array
    {
        $attendance = $session->attendance()->withoutGlobalScopes()->first();

        if ($attendance === null || $attendance->status !== AttendanceStatus::Open) {
            throw ValidationException::withMessages([
                'attendance' => 'This tab is closed. Please ask the staff to reopen it.',
            ]);
        }

        return [
            'attendance' => $attendance,
            'needs_choice' => false,
            'tabs' => [],
        ];
    }

    /**
     * @return Collection<int, Attendance>
     */
    private function openAttendancesAtTable(GuestSession $session): Collection
    {
        return Attendance::withoutGlobalScopes()
            ->open()
            ->where('venue_id', $session->venue_id)
            ->where('service_location_id', $session->service_location_id)
            ->with('tab:id,code')
            ->orderBy('created_at')
            ->get();
    }

    private function openUnassigned(GuestSession $session): Attendance
    {
        return Attendance::withoutGlobalScopes()->create([
            'venue_id' => $session->venue_id,
            'service_location_id' => $session->service_location_id,
            'attendance_channel_id' => $session->attendance_channel_id,
            'status' => AttendanceStatus::Open,
        ]);
    }

    private function bindSession(GuestSession $session, Attendance $attendance): void
    {
        if ($session->attendance_id === $attendance->id) {
            return;
        }

        $session->update(['attendance_id' => $attendance->id]);
    }
}
