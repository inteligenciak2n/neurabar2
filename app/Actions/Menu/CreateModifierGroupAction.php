<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreModifierGroupRequest;
use App\Models\Menu\ModifierGroup;
use App\Models\Tenant\Venue;

class CreateModifierGroupAction
{
    public function execute(Venue $venue, StoreModifierGroupRequest $request): ModifierGroup
    {
        return ModifierGroup::create([
            ...$request->validated(),
            'venue_id' => $venue->id,
        ]);
    }
}
