<?php

namespace App\Providers;

use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Events\Kitchen\ItemStatusUpdated;
use App\Events\Orders\GuestSignaled;
use App\Events\Orders\OrderPlaced;
use App\Listeners\Billing\RecordKdsUsage;
use App\Listeners\Billing\RecordOrderModuleUsage;
use App\Listeners\Billing\RecordSignalUsage;
use App\Listeners\Kitchen\BroadcastNewOrderByStation;
use App\Models\User;
use App\Services\Subscription\AsaasPaymentGateway;
use App\Services\Subscription\FakePaymentGateway;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayContract::class, $this->resolvePaymentGateway());
    }

    /**
     * Resolve the configured payment gateway implementation.
     *
     * Falling back to the fake gateway outside local/testing would let a
     * production deploy "charge" customers without ever moving money, so the
     * boot is aborted instead.
     *
     * @return class-string<PaymentGatewayContract>
     */
    private function resolvePaymentGateway(): string
    {
        $gateway = config('subscription.payment.gateway');
        $isLocal = $this->app->environment(['local', 'testing']);

        if (! $gateway) {
            if (! $isLocal) {
                throw new RuntimeException('SUBSCRIPTION_PAYMENT_GATEWAY must be configured in non-local environments.');
            }

            return FakePaymentGateway::class;
        }

        if (! is_subclass_of($gateway, PaymentGatewayContract::class)) {
            throw new RuntimeException("SUBSCRIPTION_PAYMENT_GATEWAY [{$gateway}] must implement ".PaymentGatewayContract::class.'.');
        }

        if (! $isLocal && $gateway === FakePaymentGateway::class) {
            throw new RuntimeException('FakePaymentGateway cannot be used outside local/testing environments.');
        }

        if (! $isLocal && $gateway === AsaasPaymentGateway::class && ! config('services.asaas.access_token')) {
            throw new RuntimeException('ASAAS_ACCESS_TOKEN must be configured to use the Asaas gateway.');
        }

        return $gateway;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(OrderPlaced::class, BroadcastNewOrderByStation::class);
        Event::listen(OrderPlaced::class, RecordOrderModuleUsage::class);
        Event::listen(ItemStatusUpdated::class, RecordKdsUsage::class);
        Event::listen(GuestSignaled::class, RecordSignalUsage::class);
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

        RateLimiter::for('call-waiter', function (Request $request) {
            $slug = $request->route('slug', '');
            $identifier = $request->input('customer_identifier', '');

            return Limit::perMinute(1)
                ->by($request->ip().'|'.$slug.'|'.$identifier)
                ->response(fn () => response()->json([
                    'message' => 'Too many requests. Please wait before sending another request.',
                ], 429));
        });

        // The operational role lives in the `user_venue` pivot, never on the user
        // row: gates defined over `$user->role` read a non-existent attribute and
        // always denied.
        Gate::define('manage-menu', fn (User $user) => in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('manage-settings', fn (User $user) => in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('manage-users', fn (User $user) => in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('access-corporation', fn (User $user) => in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));

        Gate::define('register-payment', fn (User $user) => in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
            UserRole::SectionManager,
            UserRole::Attendant,
        ], true));

        Gate::define('view-invoice', fn (User $user) => in_array($user->profile, [
            ProfileEnum::SuperAdmin,
            ProfileEnum::Finance,
            ProfileEnum::ReadOnly,
        ], true));

        Gate::define('manage-subscription', fn (User $user) => in_array($user->currentVenueRole(), [
            UserRole::Owner,
            UserRole::GeneralManager,
        ], true));
    }
}
