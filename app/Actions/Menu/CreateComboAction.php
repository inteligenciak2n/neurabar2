<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\StoreComboRequest;
use App\Models\Menu\Combo;
use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\DB;

class CreateComboAction
{
    public function execute(Venue $venue, StoreComboRequest $request): Combo
    {
        return DB::transaction(function () use ($venue, $request): Combo {
            $combo = Combo::create([
                'venue_id' => $venue->id,
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'price' => $request->validated('price'),
                'active' => $request->validated('active', true),
            ]);

            foreach ($request->validated('items', []) as $item) {
                $combo->items()->create([
                    'product_id' => $item['product_id'],
                    'variation_id' => $item['variation_id'] ?? null,
                    'quantity' => $item['quantity'],
                ]);
            }

            return $combo->load('items.product', 'items.variation');
        });
    }
}
