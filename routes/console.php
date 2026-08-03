<?php

use App\Jobs\Billing\ExpireTrialsJob;
use App\Jobs\Billing\GenerateInvoicesJob;
use App\Jobs\Billing\MarkInvoicesOverdueJob;
use App\Jobs\Billing\NotifyTrialEndingSoonJob;
use App\Jobs\Billing\ReconcileGatewayPaymentsJob;
use App\Jobs\Billing\RetryOverdueInvoicesJob;
use App\Jobs\Billing\SuspendOverdueSubscriptionsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// onOneServer + withoutOverlapping evitam que dois containers/execuções
// simultâneas gerem faturas ou suspensões duplicadas para o mesmo período.
Schedule::job(new ExpireTrialsJob)->daily()->onOneServer()->withoutOverlapping();
Schedule::job(new NotifyTrialEndingSoonJob)->daily()->onOneServer()->withoutOverlapping();
Schedule::job(new SuspendOverdueSubscriptionsJob)->daily()->onOneServer()->withoutOverlapping();
Schedule::job(new MarkInvoicesOverdueJob)->daily()->onOneServer()->withoutOverlapping();
Schedule::job(new RetryOverdueInvoicesJob)->dailyAt('09:00')->onOneServer()->withoutOverlapping();
Schedule::job(new ReconcileGatewayPaymentsJob)->dailyAt('05:00')->onOneServer()->withoutOverlapping();
Schedule::job(new GenerateInvoicesJob(now()->format('Y-m')))->monthlyOn(1, '00:00')->onOneServer()->withoutOverlapping();
