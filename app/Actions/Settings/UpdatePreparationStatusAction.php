<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\UpdatePreparationStatusRequest;
use App\Models\Settings\PreparationStatus;

class UpdatePreparationStatusAction
{
    public function execute(PreparationStatus $status, UpdatePreparationStatusRequest $request): PreparationStatus
    {
        $status->update($request->validated());

        return $status->fresh();
    }
}
