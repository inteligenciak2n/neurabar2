<?php

use App\Http\Controllers\Backoffice\Support\BackofficeTicketController;
use App\Http\Controllers\Backoffice\Support\BackofficeTutorialController;
use App\Http\Controllers\Platform\CorporationController as PlatformCorporationController;
use App\Http\Controllers\Platform\CorporationDiscountController;
use App\Http\Controllers\Platform\CorporationModuleController;
use App\Http\Controllers\Platform\DashboardController as PlatformDashboardController;
use App\Http\Controllers\Platform\InvoiceController;
use App\Http\Controllers\Platform\ManualInvoiceController;
use App\Http\Controllers\Platform\ModuleCatalogController;
use App\Http\Controllers\Platform\PlanAssignmentController;
use App\Http\Controllers\Platform\PlanCatalogController;
use App\Http\Controllers\Platform\PlatformUserController;
use App\Http\Controllers\Platform\SubscriptionController;
use App\Http\Controllers\Platform\VenueModuleController;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Support\Facades\Route;

// Platform backoffice — guard web com platform_profile
$platformPath = config('platform.path', 'backoffice');

Route::prefix($platformPath)->name('platform.')->group(function () {
    Route::middleware(['auth', 'verified', AuthenticateSession::class, 'platform_profile'])->group(function () {
        Route::get('/', [PlatformDashboardController::class, 'index'])->name('dashboard');

        Route::get('/corporations', [PlatformCorporationController::class, 'index'])->name('corporations.index');

        Route::middleware(['platform_role:super_admin,finance,registration'])->group(function () {
            Route::get('/corporations/create', [PlatformCorporationController::class, 'create'])->name('corporations.create');
            Route::post('/corporations', [PlatformCorporationController::class, 'store'])->name('corporations.store');
        });

        // scopeBindings garante que discount/module/venue aninhados pertençam à
        // corporation da URL — sem isso é possível apagar recurso de outro tenant.
        Route::middleware(['platform_role:super_admin,finance'])->scopeBindings()->group(function () {
            Route::get('/corporations/{corporation}/edit', [PlatformCorporationController::class, 'edit'])->name('corporations.edit');
            Route::put('/corporations/{corporation}', [PlatformCorporationController::class, 'update'])->name('corporations.update');
            Route::put('/corporations/{corporation}/plan', [PlanAssignmentController::class, 'update'])->name('corporations.plan');
            Route::put('/corporations/{corporation}/subscription', [SubscriptionController::class, 'update'])->name('corporations.subscription.update');
            Route::post('/corporations/{corporation}/discounts', [CorporationDiscountController::class, 'store'])->name('corporations.discounts.store');
            Route::delete('/corporations/{corporation}/discounts/{discount}', [CorporationDiscountController::class, 'destroy'])->name('corporations.discounts.destroy');
            Route::post('/corporations/{corporation}/invoices', [ManualInvoiceController::class, 'store'])->name('corporations.invoices.store');
            Route::put('/corporations/{corporation}/invoices/{invoice}/status', [ManualInvoiceController::class, 'updateStatus'])->name('corporations.invoices.status');

            Route::get('/corporations/{corporation}/modules', [CorporationModuleController::class, 'index'])->name('corporations.modules.index');
            Route::post('/corporations/{corporation}/modules', [CorporationModuleController::class, 'store'])->name('corporations.modules.store');
            Route::delete('/corporations/{corporation}/modules/{module}', [CorporationModuleController::class, 'destroy'])->name('corporations.modules.destroy');

            Route::get('/corporations/{corporation}/venues/{venue}/modules', [VenueModuleController::class, 'index'])->name('corporations.venues.modules.index');
            Route::post('/corporations/{corporation}/venues/{venue}/modules', [VenueModuleController::class, 'store'])->name('corporations.venues.modules.store');
            Route::delete('/corporations/{corporation}/venues/{venue}/modules/{module}', [VenueModuleController::class, 'destroy'])->name('corporations.venues.modules.destroy');
        });

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

        Route::get('/plans', [PlanCatalogController::class, 'index'])->name('plans.index');
        Route::get('/modules', [ModuleCatalogController::class, 'index'])->name('modules.index');

        Route::middleware(['platform_role:super_admin,finance'])->group(function () {
            Route::post('/plans', [PlanCatalogController::class, 'store'])->name('plans.store');
            Route::put('/plans/{plan}', [PlanCatalogController::class, 'update'])->name('plans.update');
            Route::delete('/plans/{plan}', [PlanCatalogController::class, 'destroy'])->name('plans.destroy');
            Route::post('/modules', [ModuleCatalogController::class, 'store'])->name('modules.store');
            Route::put('/modules/{module}', [ModuleCatalogController::class, 'update'])->name('modules.update');
            Route::delete('/modules/{module}', [ModuleCatalogController::class, 'destroy'])->name('modules.destroy');
        });

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
