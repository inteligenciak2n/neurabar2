<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
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
        $this->configurePermissions();

        Jetstream::deleteUsersUsing(DeleteUser::class);

        Vite::prefetch(concurrency: 3);

        Fortify::authenticateUsing(function ($request) {
            $credentials = $request->only(Fortify::username(), 'password');

            $user = Auth::getProvider()->retrieveByCredentials($credentials);

            if ($user && Auth::getProvider()->validateCredentials($user, $credentials)) {
                $user = User::find($user->id);

                if (! $user->active) {
                    throw ValidationException::withMessages([
                        Fortify::username() => ['This account is inactive.'],
                    ]);
                }

                $user->setSessionLanguage();

                return $user;
            }
        });
    }

    /**
     * Configure the permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
