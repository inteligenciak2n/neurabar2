<?php

namespace App\Actions\Settings;

use App\Models\Settings\KitchenStation;
use Illuminate\Validation\ValidationException;

class DeleteKitchenStationAction
{
    public function execute(KitchenStation $station): void
    {
        if ($station->isWaiterCaller()) {
            throw ValidationException::withMessages([
                'station' => 'The waiter caller station cannot be deleted.',
            ]);
        }

        if ($station->products()->exists()) {
            throw ValidationException::withMessages([
                'station' => 'Cannot delete a kitchen station that has products assigned to it.',
            ]);
        }

        $station->delete();
    }
}
