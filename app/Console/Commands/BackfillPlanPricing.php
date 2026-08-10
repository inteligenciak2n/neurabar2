<?php

namespace App\Console\Commands;

use App\Actions\Billing\BackfillPlanPricingAction;
use Illuminate\Console\Command;

class BackfillPlanPricing extends Command
{
    protected $signature = 'billing:backfill-plan-pricing';

    protected $description = 'Create initial pricing versions and assignments from legacy billing data';

    public function handle(BackfillPlanPricingAction $action): int
    {
        $result = $action->execute();

        $this->components->info(sprintf(
            'Backfill completed: %d versions, %d tiers, %d assignments created.',
            $result['versions_created'],
            $result['tiers_created'],
            $result['assignments_created'],
        ));

        return self::SUCCESS;
    }
}
