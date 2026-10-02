<?php

namespace App\Actions\Menu;

use App\Models\Menu\Product;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use Illuminate\Support\Facades\DB;

class ReorderProductsAction
{
    /**
     * Persist a new sort order for a list of product IDs.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function execute(Venue $venue, array $orderedIds): void
    {
        $validIds = Product::query()
            ->whereHas(
                'category.menu',
                fn ($query) => $query->withoutGlobalScopes()->where('venue_id', $venue->id),
            )
            ->pluck('id')
            ->all();

        DB::connection(OperationalConnection::current())->transaction(function () use ($orderedIds, $validIds): void {
            foreach ($orderedIds as $position => $id) {
                if (in_array($id, $validIds, true)) {
                    Product::where('id', $id)->update(['sort_order' => $position + 1]);
                }
            }
        });
    }
}
