<?php

namespace App\Actions\Settings;

use App\Models\Settings\AttendanceChannel;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;

class ReorderAttendanceChannelsAction
{
    /**
     * Persist a new sort order for a list of attendance channel IDs.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function execute(Venue $venue, array $orderedIds): void
    {
        $validIds = AttendanceChannel::query()
            ->where('venue_id', $venue->id)
            ->pluck('id')
            ->all();

        DB::connection(OperationalConnection::current())->transaction(function () use ($orderedIds, $validIds): void {
            foreach ($orderedIds as $position => $id) {
                if (in_array($id, $validIds, true)) {
                    AttendanceChannel::where('id', $id)->update(['sort_order' => $position + 1]);
                }
            }
        });
    }
}
