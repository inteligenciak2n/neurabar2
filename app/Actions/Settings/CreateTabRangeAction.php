<?php

namespace App\Actions\Settings;

use App\Http\Requests\Settings\StoreTabRangeRequest;
use App\Models\Settings\Tab;
use App\Models\Tenant\Venue;
use App\Support\OperationalConnection;
use App\Support\TabCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateTabRangeAction
{
    public function __construct(private ResolveTabSeriesAction $resolveTabSeries) {}

    /** @return array{created: int, skipped: int} */
    public function execute(Venue $venue, StoreTabRangeRequest $request): array
    {
        $from = (int) $request->validated('from');
        $to = (int) $request->validated('to');
        $prefix = TabCode::normalizePrefix($request->validated('prefix'));
        $notes = $request->validated('notes');

        return DB::connection(OperationalConnection::current())->transaction(function () use ($venue, $from, $to, $prefix, $notes): array {
            $series = $this->resolveTabSeries->execute($venue, $prefix, $notes);

            $existing = Tab::query()
                ->where('venue_id', $venue->id)
                ->pluck('code')
                ->map(fn (string $code): string => strtoupper($code))
                ->all();

            $created = 0;
            $skipped = 0;

            for ($number = $from; $number <= $to; $number++) {
                $code = TabCode::compose($prefix, $number);

                if (in_array(strtoupper($code), $existing, true)) {
                    $skipped++;

                    continue;
                }

                Tab::create([
                    'venue_id' => $venue->id,
                    'tab_series_id' => $series->id,
                    'code' => $code,
                    'active' => true,
                ]);

                $existing[] = strtoupper($code);
                $created++;
            }

            if ($created === 0) {
                throw ValidationException::withMessages([
                    'from' => 'Every code in this range already exists.',
                ]);
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }
}
