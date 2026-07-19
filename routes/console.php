<?php

use App\Jobs\Billing\ExpireTrialsJob;
use App\Jobs\Billing\GenerateInvoicesJob;
use App\Jobs\Billing\MarkInvoicesOverdueJob;
use App\Jobs\Billing\NotifyTrialEndingSoonJob;
use App\Jobs\Billing\SuspendOverdueSubscriptionsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpireTrialsJob)->daily();
Schedule::job(new NotifyTrialEndingSoonJob)->daily();
Schedule::job(new SuspendOverdueSubscriptionsJob)->daily();
Schedule::job(new MarkInvoicesOverdueJob)->daily();
Schedule::job(new GenerateInvoicesJob(now()->format('Y-m')))->monthlyOn(1, '00:00');
