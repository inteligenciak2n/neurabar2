<?php

namespace App\Http\Controllers\Orders;

use App\Actions\Orders\RegisterAttendantServiceLocationAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\StoreAttendantServiceLocationRequest;
use App\Models\Settings\ServiceLocation;
use Illuminate\Http\JsonResponse;

class AttendantServiceLocationController extends Controller
{
    public function store(StoreAttendantServiceLocationRequest $request, RegisterAttendantServiceLocationAction $action): JsonResponse
    {
        $location = $action->execute(app('tenant'), $request->validated());

        return response()->json([
            'location' => $this->payload($location),
        ]);
    }

    /**
     * @return array{id: string, name: string, default_attendance_channel_id: string|null, active: bool}
     */
    private function payload(ServiceLocation $location): array
    {
        return [
            'id' => $location->id,
            'name' => $location->name,
            'default_attendance_channel_id' => $location->default_attendance_channel_id,
            'active' => (bool) $location->active,
        ];
    }
}
