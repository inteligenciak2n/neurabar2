<?php

namespace App\Actions\Settings;

use App\Models\Settings\PreparationStatus;

class DeletePreparationStatusAction
{
    public function execute(PreparationStatus $status): void
    {
        $status->delete();
    }
}
