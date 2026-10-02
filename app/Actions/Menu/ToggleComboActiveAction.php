<?php

namespace App\Actions\Menu;

use App\Models\Menu\Combo;

class ToggleComboActiveAction
{
    public function execute(Combo $combo): Combo
    {
        $combo->update(['active' => ! $combo->active]);

        return $combo->fresh();
    }
}
