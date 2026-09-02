<?php

namespace App\Providers;

use App\Contracts\Sms\SmsProviderContract;
use App\Contracts\Subscription\PaymentGatewayContract;
use App\Enums\ProfileEnum;
use App\Enums\UserRole;
use App\Events\Kitchen\ItemStatusUpdated;
use App\Events\Orders\OrderPlaced;
use App\Events\Orders\ServiceRequestCreated;
use App\Listeners\Billing\RecordKdsUsage;
use App\Listeners\Billing\RecordOrderModuleUsage;
use App\Listeners\Billing\RecordServiceRequestUsage;
use App\Listeners\Kitchen\BroadcastNewOrderByStation;
use App\Models\User;
use App\Services\Sms\FakeSmsProvider;
use App\Services\Sms\TwilioSmsProvider;
use App\Services\Subscription\AsaasPaymentGateway;
use App\Services\Subscription\FakePaymentGateway;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use RuntimeException;
use Twilio\Rest\Client as TwilioClient;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGatewayContract::class, $this->resolvePaymentGateway());
        $this->app->bind(SmsProviderContract::class, $this->resolveSmsProvider());

        $this->app->singleton(TwilioClient::class, fn () => new TwilioClient(
            config('services.twilio.sid'),
            config('services.twilio.token'),
        ));
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
     * Resolve the configured SMS/OTP provider implementation.
     *
     * Falling back to the fake provider outside local/testing would let a
     * production deploy "send" SMS/OTP codes without ever reaching the
     * customer's phone, so the boot is aborted instead.
     *
     * @return class-string<SmsProviderContract>
     */
    private function resolveSmsProvider(): string
    {
        $isLocal = $this->app->environment(['local', 'testing']);

        if (! $isLocal) {
            if (! config('services.twilio.sid') || ! config('services.twilio.token')) {
                throw new RuntimeException('TWILIO_ACCOUNT_SID and TWILIO_AUTH_TOKEN must be configured in non-local environments.');
            }

            return TwilioSmsProvider::class;
        }

        return config('sms.provider') ?: FakeSmsProvider::class;
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Inertia props are plain arrays/objects everywhere in this app (no existing
        // Resource used the "data" envelope); keep new Resources consistent with that.
        JsonResource::withoutWrapping();

        Event::listen(OrderPlaced::class, BroadcastNewOrderByStation::class);
        Event::listen(OrderPlaced::class, RecordOrderModuleUsage::class);
        Event::listen(ItemStatusUpdated::class, RecordKdsUsage::class);
        Event::listen(ServiceRequestCreated::class, RecordServiceRequestUsage::class);
        Event::listen(Registered::class, SendEmailVerificationNotification::class);

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
