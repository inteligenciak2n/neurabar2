<?php

use App\Http\Controllers\Auth\VenueSelectorController;
use App\Http\Controllers\Backoffice\Support\BackofficeTicketController;
use App\Http\Controllers\Backoffice\Support\BackofficeTutorialController;
use App\Http\Controllers\Corporation\CorporationDashboardController;
use App\Http\Controllers\Corporation\VenueController as CorporationVenueController;
use App\Http\Controllers\Guest\GuestCheckoutController;
use App\Http\Controllers\Guest\GuestHubController;
use App\Http\Controllers\Guest\GuestOrderController;
use App\Http\Controllers\Guest\GuestSessionController;
use App\Http\Controllers\Guest\PublicMenuController;
use App\Http\Controllers\Guest\TrackOrderController;
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
use App\Http\Controllers\Platform\CorporationController as PlatformCorporationController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\PlanAssignmentController;
use App\Http\Controllers\Platform\PlanCatalogController;
use App\Http\Controllers\Platform\PlatformUserController;
use App\Http\Controllers\Settings\AttendanceChannelController;
use App\Http\Controllers\Settings\DashboardController;
use App\Http\Controllers\Settings\KitchenStationController;
use App\Http\Controllers\Settings\PreparationStatusController;
use App\Http\Controllers\Settings\ServiceLocationController;
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\VenueController;
use App\Http\Controllers\Settings\VenueSettingsController;
use App\Http\Controllers\Support\SupportDashboardController;
use App\Http\Controllers\Support\TicketController;
use App\Http\Controllers\Support\TicketMessageController;
use App\Http\Controllers\Support\TicketRatingController;
use App\Http\Controllers\Support\TutorialController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect()->route('login');
    // return Inertia::render('Welcome', [
    //     'canLogin' => Route::has('login'),
    //     'canRegister' => Route::has('register'),
    // ]);
})->name('welcome');

// Public guest routes — no auth required
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/kitchen/monitor', [KdsController::class, 'monitor'])->name('kitchen.monitor');
    Route::get('/order/{order}/track', [TrackOrderController::class, 'show'])->name('orders.track');
});

// Guest Hub — QR-code-based visitor flow
Route::prefix('g/{token}')->name('guest.')->group(function () {
    Route::get('/', [GuestHubController::class, 'show'])->name('hub')->middleware('throttle:60,1');
    Route::get('/menu', [PublicMenuController::class, 'show'])->name('menu')->middleware('throttle:60,1');
    Route::post('/session', [GuestSessionController::class, 'store'])->name('session.store')->middleware('throttle:10,1');
    Route::post('/session/verify', [GuestSessionController::class, 'verify'])->name('session.verify')->middleware('throttle:3,15');
    Route::get('/orders', [GuestOrderController::class, 'index'])->name('orders.index')->middleware('throttle:30,1');
    Route::post('/orders', [GuestOrderController::class, 'store'])->name('orders.store')->middleware('throttle:30,1');
    Route::post('/signal', [GuestHubController::class, 'signal'])->name('signal')->middleware('throttle:10,1');
    Route::post('/checkout', [GuestCheckoutController::class, 'store'])->name('checkout')->middleware('throttle:5,1');
    Route::post('/verify-location', [GuestHubController::class, 'verifyLocation'])->name('verify-location')->middleware('throttle:10,1');
});

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
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Menu — edit routes restricted to managers; products page accessible by all
    Route::prefix('menu')->name('menu.')->middleware(['role:owner,general_manager'])->group(function () {
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
    Route::prefix('attendances')->name('attendances.')->group(function () {
        Route::get('/', [AttendanceController::class, 'index'])->name('index');
        Route::post('/', [AttendanceController::class, 'store'])->name('store');
        Route::get('/{attendance}', [AttendanceController::class, 'show'])->name('show');
        Route::put('/{attendance}', [AttendanceController::class, 'update'])->name('update');
        Route::get('/{attendance}/orders', [AttendanceController::class, 'orders'])->name('orders');
        Route::post('/{attendance}/orders', [OrderController::class, 'store'])->name('orders.store');
    });

    // Order Taker
    Route::get('/orders/take/{attendance}', [OrderController::class, 'create'])->name('orders.take');

    // Kitchen KDS
    Route::prefix('kitchen')->name('kitchen.')->group(function () {
        Route::get('/kds', [KdsController::class, 'index'])->name('kds');
        Route::put('/items/{item}/status', [KdsController::class, 'updateItemStatus'])->name('items.status');
    });

    // Payment
    Route::prefix('payment')->name('payment.')->group(function () {
        Route::get('/{attendance}', [PaymentController::class, 'show'])->name('show');
        Route::post('/{attendance}', [PaymentController::class, 'store'])->name('store');
    });

    // Support — available to all authenticated users with a venue context
    Route::prefix('support')->name('support.')->group(function () {
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

    // Settings — owner or general manager only
    Route::prefix('settings')->name('settings.')->middleware(['role:owner,general_manager'])->group(function () {
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

// Corporation panel — auth + tenant + owner role
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'tenant',
    'role:owner,general_manager',
])->prefix('corporation')->name('corporation.')->group(function () {
    Route::get('/dashboard', [CorporationDashboardController::class, 'index'])->name('dashboard');
    Route::post('/venues/{id}/switch', [CorporationDashboardController::class, 'switchVenue'])->name('venues.switch');
    Route::get('/venues', [CorporationVenueController::class, 'index'])->name('venues.index');
    Route::get('/venues/create', [CorporationVenueController::class, 'create'])->name('venues.create');
    Route::post('/venues', [CorporationVenueController::class, 'store'])->name('venues.store');
    Route::get('/venues/{venue}/edit', [CorporationVenueController::class, 'edit'])->name('venues.edit');
    Route::put('/venues/{venue}', [CorporationVenueController::class, 'update'])->name('venues.update');
});

// Platform backoffice — guard web com platform_profile
$platformPath = config('platform.path', 'backoffice');

Route::prefix($platformPath)->name('platform.')->group(function () {
    Route::middleware(['auth', 'platform_profile'])->group(function () {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');

        Route::get('/corporations', [PlatformCorporationController::class, 'index'])->name('corporations.index');
        Route::get('/corporations/create', [PlatformCorporationController::class, 'create'])->name('corporations.create');
        Route::post('/corporations', [PlatformCorporationController::class, 'store'])->name('corporations.store');
        Route::get('/corporations/{corporation}/edit', [PlatformCorporationController::class, 'edit'])->name('corporations.edit');
        Route::put('/corporations/{corporation}', [PlatformCorporationController::class, 'update'])->name('corporations.update');
        Route::put('/corporations/{corporation}/plan', [PlanAssignmentController::class, 'update'])->name('corporations.plan');

        Route::get('/plans', [PlanCatalogController::class, 'index'])->name('plans.index');
        Route::post('/plans', [PlanCatalogController::class, 'store'])->name('plans.store');
        Route::put('/plans/{plan}', [PlanCatalogController::class, 'update'])->name('plans.update');
        Route::delete('/plans/{plan}', [PlanCatalogController::class, 'destroy'])->name('plans.destroy');

        Route::middleware(['platform_role:super_admin'])->group(function () {
            Route::get('/users', [PlatformUserController::class, 'index'])->name('users.index');
            Route::post('/users', [PlatformUserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [PlatformUserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [PlatformUserController::class, 'destroy'])->name('users.destroy');
        });

        // Support management — all platform users
        Route::prefix('support')->name('support.')->group(function () {
            Route::prefix('tickets')->name('tickets.')->group(function () {
                Route::get('/', [BackofficeTicketController::class, 'index'])->name('index');
                Route::get('/{ticketId}', [BackofficeTicketController::class, 'show'])->name('show');
                Route::put('/{ticketId}', [BackofficeTicketController::class, 'update'])->name('update');
                Route::post('/{ticketId}/messages', [BackofficeTicketController::class, 'reply'])->name('messages.store');
            });

            Route::prefix('tutorials')->name('tutorials.')->group(function () {
                Route::get('/', [BackofficeTutorialController::class, 'index'])->name('index');
                Route::get('/create', [BackofficeTutorialController::class, 'create'])->name('create');
                Route::post('/', [BackofficeTutorialController::class, 'store'])->name('store');
                Route::get('/{tutorialId}/edit', [BackofficeTutorialController::class, 'edit'])->name('edit');
                Route::put('/{tutorialId}', [BackofficeTutorialController::class, 'update'])->name('update');
                Route::delete('/{tutorialId}', [BackofficeTutorialController::class, 'destroy'])->name('destroy');
                Route::post('/{tutorialId}/toggle-published', [BackofficeTutorialController::class, 'togglePublished'])->name('toggle-published');
            });
        });
    });
});
