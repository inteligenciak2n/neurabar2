<?php

namespace App\Console\Commands;

use App\Actions\Billing\UpdateCatalogPricesAction;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Throwable;

class UpdateCatalogPrices extends Command
{
    protected $signature = 'billing:update-prices
                            {--defaults : Atualiza preços e faixas de consumo para os valores padrão}
                            {--plan=* : Preço de plano no formato CODIGO=VALOR_EM_REAIS}
                            {--module=* : Preço de módulo no formato CODIGO=VALOR_EM_REAIS}
                            {--effective-from= : Início da vigência das novas versões (Y-m-d)}
                            {--force : Ignora a confirmação}';

    protected $description = 'Atualiza preços de módulos e publica novas versões de preços dos planos';

    /**
     * Planos, módulos e valores fixos usam centavos; preços unitários das faixas usam micros.
     *
     * @var array<string, mixed>
     */
    protected $defaults = [
        'plans' => [
            'basic' => 14900,
            'pro' => 24900,
            'enterprise' => 39900,
        ],
        'modules' => [
            'kds' => 3000,
            'kitchen-printer' => 3000,
            'waiter-app' => 3000,
            'waiter-printer' => 3000,
            'self-ordering' => 3000,
            'self-ordering-printer' => 3000,
        ],
        'usage_tiers' => [
            'basic' => [
                'modules' => ['kds', 'kitchen-printer', 'waiter-app', 'waiter-printer', 'self-ordering', 'self-ordering-printer'],
                'tiers' => [
                    ['min_quantity' => 0, 'max_quantity' => null, 'included_quantity' => 1000, 'price_per_unit' => 0, 'flat_price' => null, 'overage_price_per_unit' => 500, 'overage_flat_fee' => 0],
                ],
            ],
            'pro' => [
                'modules' => ['kds', 'kitchen-printer', 'waiter-app', 'waiter-printer', 'self-ordering', 'self-ordering-printer'],
                'tiers' => [
                    ['min_quantity' => 0, 'max_quantity' => null, 'included_quantity' => 5000, 'price_per_unit' => 0, 'flat_price' => null, 'overage_price_per_unit' => 500, 'overage_flat_fee' => 0],
                ],
            ],
            'enterprise' => [
                'modules' => ['kds', 'kitchen-printer', 'waiter-app', 'waiter-printer', 'self-ordering', 'self-ordering-printer'],
                'tiers' => [
                    ['min_quantity' => 0, 'max_quantity' => null, 'included_quantity' => 10000, 'price_per_unit' => 0, 'flat_price' => null, 'overage_price_per_unit' => 500, 'overage_flat_fee' => 0],
                ],
            ],
        ],
    ];

    public function handle(UpdateCatalogPricesAction $action): int
    {
        try {
            if ($this->option('defaults')) {
                $planPrices = $this->defaults['plans'];
                $modulePrices = $this->defaults['modules'];
                $planUsageTiers = $this->expandDefaultUsageTiers();
                $effectiveFrom = $this->effectiveFrom(true);
            } else {
                $planPrices = $this->parsePrices((array) $this->option('plan'), 'plano');
                $modulePrices = $this->parsePrices((array) $this->option('module'), 'módulo');
                $planUsageTiers = [];

                if ($planPrices === [] && $modulePrices === []) {
                    throw new InvalidArgumentException('Informe ao menos uma opção --plan ou --module.');
                }

                $effectiveFrom = $this->effectiveFrom($planPrices !== []);
            }
        } catch (InvalidArgumentException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Tipo', 'Código', 'Novo preço'],
            [
                ...$this->priceRows('Plano', $planPrices),
                ...$this->priceRows('Módulo', $modulePrices),
            ],
        );

        if ($planPrices !== []) {
            $this->line('Vigência das novas versões: '.$effectiveFrom->toDateString());
        }

        if ($planUsageTiers !== []) {
            $this->newLine();
            $this->components->info('Faixas de consumo');
            $this->table(
                ['Plano', 'Módulo', 'Faixa', 'Incluso', 'Base/un.', 'Excedente/un.'],
                $this->usageTierRows($planUsageTiers),
            );
        }

        if (! $this->option('force') && ! $this->confirm('Confirma a atualização dos preços?')) {
            $this->components->warn('Operação cancelada.');

            return self::SUCCESS;
        }

        try {
            $result = $action->execute($planPrices, $modulePrices, $effectiveFrom, $planUsageTiers);
        } catch (Throwable $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Preços atualizados: %d planos, %d módulos e %d faixas criadas.',
            $result['plans_updated'],
            $result['modules_updated'],
            $result['tiers_created'],
        ));

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $entries
     * @return array<string, int>
     */
    private function parsePrices(array $entries, string $type): array
    {
        $prices = [];

        foreach ($entries as $entry) {
            if (! preg_match('/^([a-zA-Z0-9_-]+)=(\d+(?:\.\d{1,2})?)$/', trim($entry), $matches)) {
                throw new InvalidArgumentException("Preço de {$type} inválido [{$entry}]. Use CODIGO=VALOR, por exemplo basic=149.90.");
            }

            $code = $matches[1];

            if (array_key_exists($code, $prices)) {
                throw new InvalidArgumentException("O {$type} [{$code}] foi informado mais de uma vez.");
            }

            $prices[$code] = Money::fromFloat($matches[2]);
        }

        return $prices;
    }

    private function effectiveFrom(bool $required): Carbon
    {
        $value = $this->option('effective-from');

        if (! $value) {
            return today()->addMonth()->startOfMonth();
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', (string) $value)->startOfDay();
        } catch (Throwable) {
            throw new InvalidArgumentException('A opção --effective-from deve usar o formato Y-m-d.');
        }

        if ($date->format('Y-m-d') !== $value) {
            throw new InvalidArgumentException('A opção --effective-from deve usar o formato Y-m-d.');
        }

        if ($required && $date->lte(today())) {
            throw new InvalidArgumentException('A vigência de uma nova versão de plano deve ser uma data futura.');
        }

        return $date;
    }

    /**
     * @param  array<string, int>  $prices
     * @return list<array{string, string, string}>
     */
    private function priceRows(string $type, array $prices): array
    {
        return collect($prices)
            ->map(fn (int $price, string $code): array => [$type, $code, Money::format($price)])
            ->values()
            ->all();
    }

    /** @return array<string, list<array<string, int|string|null>>> */
    private function expandDefaultUsageTiers(): array
    {
        $expanded = [];

        foreach ($this->defaults['usage_tiers'] as $planCode => $configuration) {
            foreach ($configuration['modules'] as $moduleCode) {
                foreach ($configuration['tiers'] as $tier) {
                    $expanded[$planCode][] = [
                        'module_code' => $moduleCode,
                        ...$tier,
                        'currency' => 'BRL',
                    ];
                }
            }
        }

        return $expanded;
    }

    /**
     * @param  array<string, list<array<string, int|string|null>>>  $planUsageTiers
     * @return list<array{string, string, string, int, string, string}>
     */
    private function usageTierRows(array $planUsageTiers): array
    {
        $rows = [];

        foreach ($planUsageTiers as $planCode => $tiers) {
            foreach ($tiers as $tier) {
                $rows[] = [
                    $planCode,
                    (string) $tier['module_code'],
                    $tier['min_quantity'].' - '.($tier['max_quantity'] ?? '∞'),
                    (int) $tier['included_quantity'],
                    Money::format((int) $tier['price_per_unit'], Money::MICRO_SCALE),
                    Money::format((int) $tier['overage_price_per_unit'], Money::MICRO_SCALE),
                ];
            }
        }

        return $rows;
    }
}
