<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Events\Orders\OrderPlaced;
use App\Listeners\Kitchen\BroadcastNewOrderByStation;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderPlaced::class, BroadcastNewOrderByStation::class);

        Gate::define('manage-menu', fn (User $user) => in_array($user->role, [
            UserRole::CorporationAdmin,
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('manage-settings', fn (User $user) => in_array($user->role, [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('manage-users', fn (User $user) => in_array($user->role, [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('access-corporation', fn (User $user) => in_array($user->role, [
            UserRole::CorporationAdmin,
            UserRole::Owner,
        ], true));

        Gate::define('register-payment', fn (User $user) => in_array($user->role, [
            UserRole::Owner,
            UserRole::GeneralManager,
            UserRole::SectionManager,
            UserRole::Attendant,
        ], true));
    }
}
