<?php

namespace App\Policies\Support;

use App\Models\Support\Tutorial;
use App\Models\User;

class TutorialPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Tutorial $tutorial): bool
    {
        return $tutorial->published;
    }
}
