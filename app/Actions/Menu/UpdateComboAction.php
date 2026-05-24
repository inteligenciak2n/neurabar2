<?php

namespace App\Actions\Menu;

use App\Http\Requests\Menu\UpdateComboRequest;
use App\Models\Menu\Combo;
use Illuminate\Support\Facades\DB;

class UpdateComboAction
{
    public function execute(Combo $combo, UpdateComboRequest $request): Combo
    {
        return DB::transaction(function () use ($combo, $request): Combo {
            $combo->update([
                'name' => $request->validated('name'),
                'description' => $request->validated('description'),
                'price' => $request->validated('price'),
                'active' => $request->validated('active', $combo->active),
            ]);

            $combo->items()->delete();

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
