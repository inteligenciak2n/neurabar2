<?php

namespace App\Actions\Onboarding;

use App\Actions\Corporation\CreateVenueAction;
use App\Models\Tenant\Corporation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinalizeOnboardingAction
{
    public function __construct(private readonly CreateVenueAction $createVenueAction) {}

    /**
     * Atualiza os dados da corporation, cria as venues informadas (ou com dados
     * fake quando marcadas como "pular") e conclui o onboarding do usuário.
     *
     * @param  array<string, mixed>  $corporationData
     * @param  array<int, array<string, mixed>>  $venues
     */
    public function execute(User $user, Corporation $corporation, array $corporationData, array $venues): void
    {
        DB::transaction(function () use ($user, $corporation, $corporationData, $venues): void {
            $corporation->update($corporationData);

            $firstVenueId = null;

            foreach ($venues as $index => $venueData) {
                $data = ($venueData['skip'] ?? false)
                    ? $this->fakeVenueData($user, $index + 1)
                    : $this->venuePayload($venueData);

                $venue = $this->createVenueAction->execute($corporation, $data);

                $firstVenueId ??= $venue->id;
            }

            $user->current_venue_id = $firstVenueId;
            $user->onboarding_completed_at = now();
            $user->save();
        });

        session()->forget('onboarding.venue_count');
    }

    /**
     * @param  array<string, mixed>  $venueData
     * @return array<string, mixed>
     */
    private function venuePayload(array $venueData): array
    {
        return [
            'name' => $venueData['name'],
            'tax_id' => $venueData['tax_id'] ?? null,
            'phone' => $venueData['phone'] ?? null,
            'city' => $venueData['city'] ?? null,
            'state' => $venueData['state'] ?? null,
            'timezone' => $venueData['timezone'] ?? 'America/Sao_Paulo',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fakeVenueData(User $user, int $sequence): array
    {
        return [
            'name' => "{$user->name} - Ponto de Venda {$sequence}",
            'tax_id' => null,
            'phone' => null,
            'city' => null,
            'state' => null,
            'timezone' => 'America/Sao_Paulo',
        ];
    }
}
