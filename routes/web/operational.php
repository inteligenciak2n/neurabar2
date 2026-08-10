<?php

use App\Http\Controllers\Auth\VenueSelectorController;
use App\Http\Controllers\Delivery\DashboardController as DeliveryDashboardController;
use App\Http\Controllers\DirectPrint\DashboardController as DirectPrintDashboardController;
use App\Http\Controllers\DirectWaiter\DashboardController as DirectWaiterDashboardController;
use App\Http\Controllers\Finance\DashboardController as FinanceDashboardController;
use App\Http\Controllers\FiscalNote\DashboardController as FiscalNoteDashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\Kitchen\KdsController;
use App\Http\Controllers\Menu\CategoryController;
use App\Http\Controllers\Menu\ComboController;
use App\Http\Controllers\Menu\ModifierGroupController;
use App\Http\Controllers\Menu\ModifierOptionController;
use App\Http\Controllers\Menu\ProductController;
use App\Http\Controllers\Menu\ProductVariationController;
use App\Http\Controllers\NoVenueController;
use App\Http\Controllers\Orders\AttendanceController;
use App\Http\Controllers\Orders\OrderController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Production\DashboardController as ProductionDashboardController;
use App\Http\Controllers\Settings\AttendanceChannelController;
use App\Http\Controllers\Settings\DashboardController;
use App\Http\Controllers\Settings\KitchenStationController;
use App\Http\Controllers\Settings\PreparationStatusController;
use App\Http\Controllers\Settings\ServiceLocationController;
use App\Http\Controllers\Settings\SubscriptionBillingAddressController;
use App\Http\Controllers\Settings\SubscriptionController;
use App\Http\Controllers\Settings\SubscriptionInvoiceController;
use App\Http\Controllers\Settings\SubscriptionPaymentMethodController;
use App\Http\Controllers\Settings\SubscriptionUsageController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\VenueController;
use App\Http\Controllers\Settings\VenuePlanChangeRequestController;
use App\Http\Controllers\Settings\VenueSettingsController;
use App\Http\Controllers\Support\SupportDashboardController;
use App\Http\Controllers\Support\TicketController;
use App\Http\Controllers\Support\TicketMessageController;
use App\Http\Controllers\Support\TicketRatingController;
use App\Http\Controllers\Support\TutorialController;
use App\Http\Controllers\VoiceCommand\DashboardController as VoiceCommandDashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Venue selector — auth required, no tenant context yet
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->post('/account/venue/{id}', [VenueSelectorController::class, 'store'])
    ->name('venue.select');

// No-venue fallback — auth required, no tenant context
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->prefix('no-venue')->name('no-venue.')->group(function () {
        Route::get('/', [NoVenueController::class, 'index'])->name('index');
        Route::post('/', [NoVenueController::class, 'store'])->name('store');
    });

// Invitations — public show (redirects to login if unauthenticated), accept requires auth
Route::get('/invitations/{token}', [InvitationController::class, 'show'])->name('invitations.show');
Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])
    ->post('/invitations/{token}/accept', [InvitationController::class, 'accept'])
    ->name('invitations.accept');

// Operational routes — auth + tenant context required
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'tenant',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('module:menu');

    // Menu — edit routes restricted to managers; products page accessible by all
    Route::prefix('menu')->name('menu.')->middleware(['module:menu', 'role:owner,general_manager'])->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/categories/reorder', [CategoryController::class, 'reorder'])->name('categories.reorder');

        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::post('/products', [ProductController::class, 'store'])->name('products.store');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
        Route::post('/products/{product}/toggle', [ProductController::class, 'toggleActive'])->name('products.toggle');
        Route::put('/products/{product}/modifier-groups', [ProductController::class, 'syncModifierGroups'])->name('products.modifier-groups.sync');

        // Product variations (nested under products)
        Route::post('/products/{product}/variations', [ProductVariationController::class, 'store'])->name('products.variations.store');
        Route::put('/products/{product}/variations/{variation}', [ProductVariationController::class, 'update'])->name('products.variations.update');
        Route::delete('/products/{product}/variations/{variation}', [ProductVariationController::class, 'destroy'])->name('products.variations.destroy');

        // Modifier groups and options
        Route::get('/modifier-groups', [ModifierGroupController::class, 'index'])->name('modifier-groups.index');
        Route::post('/modifier-groups', [ModifierGroupController::class, 'store'])->name('modifier-groups.store');
        Route::put('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'update'])->name('modifier-groups.update');
        Route::delete('/modifier-groups/{modifierGroup}', [ModifierGroupController::class, 'destroy'])->name('modifier-groups.destroy');
        Route::post('/modifier-groups/{modifierGroup}/options', [ModifierOptionController::class, 'store'])->name('modifier-groups.options.store');
        Route::put('/modifier-groups/{modifierGroup}/options/{option}', [ModifierOptionController::class, 'update'])->name('modifier-groups.options.update');
        Route::delete('/modifier-groups/{modifierGroup}/options/{option}', [ModifierOptionController::class, 'destroy'])->name('modifier-groups.options.destroy');

        // Combos
        Route::get('/combos', [ComboController::class, 'index'])->name('combos.index');
        Route::post('/combos', [ComboController::class, 'store'])->name('combos.store');
        Route::put('/combos/{combo}', [ComboController::class, 'update'])->name('combos.update');
        Route::delete('/combos/{combo}', [ComboController::class, 'destroy'])->name('combos.destroy');
    });

    // Attendances
    Route::prefix('attendances')->name('attendances.')->middleware('module:menu')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
        Route::get('/{attendance}/orders', [AttendanceController::class, 'orders'])->name('orders');
        Route::post('/{attendance}/orders', [OrderController::class, 'store'])->name('orders.store');
    });

    // Order Taker
    Route::get('/orders/take/{attendance}', [OrderController::class, 'create'])->name('orders.take')->middleware('module:taker');

    // Kitchen KDS
    Route::prefix('kitchen')->name('kitchen.')->middleware('module:kds')->group(function () {
        Route::get('/kds', [KdsController::class, 'index'])->name('kds');
        Route::put('/items/{item}/status', [KdsController::class, 'updateItemStatus'])->name('items.status');
    });

    // Module scaffolds
    Route::get('/delivery', [DeliveryDashboardController::class, 'index'])->name('delivery.index')->middleware('module:delivery');
    Route::get('/fiscal-note', [FiscalNoteDashboardController::class, 'index'])->name('fiscal-note.index')->middleware('module:fiscal_note');
    Route::get('/voice-command', [VoiceCommandDashboardController::class, 'index'])->name('voice-command.index')->middleware('module:voice_command');
    Route::get('/production', [ProductionDashboardController::class, 'index'])->name('production.index')->middleware('module:production_dashboard');
    Route::get('/finance', [FinanceDashboardController::class, 'index'])->name('finance.index')->middleware('module:financial_dashboard');
    Route::get('/direct-waiter', [DirectWaiterDashboardController::class, 'index'])->name('direct-waiter.index')->middleware('module:direct_waiter');
    Route::get('/direct-print', [DirectPrintDashboardController::class, 'index'])->name('direct-print.index')->middleware('module:direct_print');

    // Payment
    Route::prefix('payment')->name('payment.')->middleware('module:menu')->group(function () {
        Route::get('/{attendance}', [PaymentController::class, 'show'])->name('show');
        Route::post('/{attendance}', [PaymentController::class, 'store'])->name('store');
    });

    // Support — available to all authenticated users with a venue context
    Route::prefix('support')->name('support.')->middleware('module:menu')->group(function () {
        Route::get('/', [SupportDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/', [TicketController::class, 'index'])->name('index');
            Route::get('/create', [TicketController::class, 'create'])->name('create');
            Route::post('/', [TicketController::class, 'store'])->name('store');
            Route::get('/{ticketId}', [TicketController::class, 'show'])->name('show');
            Route::post('/{ticketId}/messages', [TicketMessageController::class, 'store'])->name('messages.store');
            Route::post('/{ticketId}/close', [TicketController::class, 'close'])->name('close');
            Route::post('/{ticketId}/rate', [TicketRatingController::class, 'store'])->name('rate');
        });

        Route::prefix('tutorials')->name('tutorials.')->group(function () {
            Route::get('/', [TutorialController::class, 'index'])->name('index');
            Route::get('/{slug}', [TutorialController::class, 'show'])->name('show');
        });

        Route::get('/attachments/{attachmentId}', [TutorialController::class, 'attachment'])->name('attachments.show');
    });

    // Subscription self-management — owner or general manager only.
    // Deliberately outside `module:menu`: a tenant whose menu module lapsed
    // must still be able to reach billing to pay and restore access.
    Route::prefix('settings/subscription')->name('settings.subscription.')->middleware(['role:owner,general_manager'])->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])->name('index');
        Route::get('/usage', SubscriptionUsageController::class)->name('usage');
        Route::post('/plan-change-requests', [VenuePlanChangeRequestController::class, 'store'])->name('plan-change-requests.store');
        Route::delete('/plan-change-requests/{changeRequest}', [VenuePlanChangeRequestController::class, 'destroy'])->name('plan-change-requests.destroy');
        Route::post('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::post('/gateway/activate', [SubscriptionController::class, 'activateGateway'])->name('gateway.activate');
        Route::post('/venues/{venue}/modules', [SubscriptionController::class, 'store'])->name('modules.store');
        Route::delete('/venues/{venue}/modules/{moduleCode}', [SubscriptionController::class, 'destroy'])->name('modules.destroy');

        Route::get('/invoices', [SubscriptionInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoiceType}/{invoiceId}', [SubscriptionInvoiceController::class, 'show'])->name('invoices.show');
        Route::post('/invoices/{invoiceType}/{invoiceId}/pay', [SubscriptionInvoiceController::class, 'pay'])->name('invoices.pay');

        Route::get('/payment-methods', [SubscriptionPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [SubscriptionPaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::post('/payment-methods/{method}/default', [SubscriptionPaymentMethodController::class, 'setDefault'])->name('payment-methods.default');
        Route::delete('/payment-methods/{method}', [SubscriptionPaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        Route::get('/billing-address', [SubscriptionBillingAddressController::class, 'edit'])->name('billing-address.edit');
        Route::put('/billing-address/{type}', [SubscriptionBillingAddressController::class, 'update'])->name('billing-address.update');
    });

    // Settings — owner or general manager only
    Route::prefix('settings')->name('settings.')->middleware(['module:menu', 'role:owner,general_manager'])->group(function () {
        Route::get('/', fn () => Inertia::render('Settings/Index'))->name('index');

        Route::get('/venue', [VenueController::class, 'edit'])->name('venue');
        Route::put('/venue', [VenueController::class, 'update'])->name('venue.update');

        Route::get('/general', [VenueSettingsController::class, 'edit'])->name('general');
        Route::put('/general', [VenueSettingsController::class, 'update'])->name('general.update');

        Route::get('/kitchen-stations', [KitchenStationController::class, 'index'])->name('kitchen-stations.index');
        Route::post('/kitchen-stations', [KitchenStationController::class, 'store'])->name('kitchen-stations.store');
        Route::put('/kitchen-stations/{station}', [KitchenStationController::class, 'update'])->name('kitchen-stations.update');
        Route::delete('/kitchen-stations/{station}', [KitchenStationController::class, 'destroy'])->name('kitchen-stations.destroy');

        Route::get('/preparation-statuses', [PreparationStatusController::class, 'index'])->name('preparation-statuses.index');
        Route::post('/preparation-statuses', [PreparationStatusController::class, 'store'])->name('preparation-statuses.store');
        Route::put('/preparation-statuses/{status}', [PreparationStatusController::class, 'update'])->name('preparation-statuses.update');
        Route::delete('/preparation-statuses/{status}', [PreparationStatusController::class, 'destroy'])->name('preparation-statuses.destroy');

        Route::get('/service-locations', [ServiceLocationController::class, 'index'])->name('service-locations.index');
        Route::post('/service-locations', [ServiceLocationController::class, 'store'])->name('service-locations.store');
        Route::put('/service-locations/{location}', [ServiceLocationController::class, 'update'])->name('service-locations.update');
        Route::delete('/service-locations/{location}', [ServiceLocationController::class, 'destroy'])->name('service-locations.destroy');
        Route::post('/service-locations/{location}/qr', [ServiceLocationController::class, 'generateQr'])->name('service-locations.qr');
        Route::get('/service-locations/{location}/qr-pdf', [ServiceLocationController::class, 'qrPdf'])->name('service-locations.qr-pdf');

        Route::get('/attendance-channels', [AttendanceChannelController::class, 'index'])->name('attendance-channels.index');
        Route::post('/attendance-channels', [AttendanceChannelController::class, 'store'])->name('attendance-channels.store');
        Route::put('/attendance-channels/{channel}', [AttendanceChannelController::class, 'update'])->name('attendance-channels.update');
        Route::delete('/attendance-channels/{channel}', [AttendanceChannelController::class, 'destroy'])->name('attendance-channels.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });
});
