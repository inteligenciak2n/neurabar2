<?php

namespace App\Actions\Menu;

use App\Models\Menu\Category;
use App\Models\Menu\Menu;
use App\Models\Tenant\Venue;
use Illuminate\Support\Facades\DB;

class ReorderCategoriesAction
{
    /**
     * Persist new sort order for a list of category IDs.
     *
     * @param  array<int, string>  $orderedIds
     */
    public function execute(Venue $venue, array $orderedIds): void
    {
        $menu = Menu::withoutGlobalScopes()
            ->where('venue_id', $venue->id)
            ->firstOrFail();

        $validIds = Category::where('menu_id', $menu->id)
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($orderedIds, $validIds) {
            foreach ($orderedIds as $position => $id) {
                if (in_array($id, $validIds)) {
                    Category::where('id', $id)->update(['sort_order' => $position + 1]);
                }
            }
        });
    }
}
