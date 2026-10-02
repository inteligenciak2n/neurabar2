<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreModifierGroupRequest;
use App\Models\Menu\ModifierGroup;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;

class CreateModifierGroupAction
{
    public function execute(Venue $venue, StoreModifierGroupRequest $request): ModifierGroup
    {
        return DB::connection(OperationalConnection::current())->transaction(function () use ($venue, $request): ModifierGroup {
            $group = ModifierGroup::create([
                'venue_id' => $venue->id,
                'name' => $request->validated('name'),
                'required' => $request->boolean('required'),
                'multiple_selection' => $request->boolean('multiple_selection'),
            ]);

            foreach ($request->validated('options', []) as $option) {
                $name = trim((string) ($option['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                $group->options()->create([
                    'name' => $name,
                    'extra_price' => $option['extra_price'] ?? 0,
                    'active' => true,
                ]);
            }

            return $group;
        });
    }
}
