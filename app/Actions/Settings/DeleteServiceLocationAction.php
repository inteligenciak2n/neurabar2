<?php

namespace App\Actions\Settings;

use App\Models\Settings\ServiceLocation;

class DeleteServiceLocationAction
{
    public function execute(ServiceLocation $location): void
    {
        $location->delete();
    }
}
