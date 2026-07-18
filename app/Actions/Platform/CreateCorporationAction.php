<?php

namespace App\Actions\Platform;

use App\Actions\Corporation\CreateVenueAction;
use App\Enums\ProfileEnum;
use App\Jobs\Platform\SendWelcomeEmailJob;
use App\Models\Tenant\Corporation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateCorporationAction
{
    public function __construct(
        private readonly CreateVenueAction $createVenueAction,
    ) {}

    public function execute(array $data): Corporation
    {
        return DB::transaction(function () use ($data) {
            $temporaryPassword = Str::random(12);

            $owner = User::create([
                'name' => $data['owner_name'],
                'email' => $data['owner_email'],
                'profile' => ProfileEnum::Client,
                'password' => Hash::make($temporaryPassword),
                'active' => true,
                'email_verified_at' => now(),
            ]);

            $corporation = Corporation::create([
                'owner_id' => $owner->id,
                'name' => $data['name'],
                'tax_id' => $data['tax_id'] ?? null,
                'email' => $data['email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'active' => true,
            ]);

            $venue = $this->createVenueAction->execute($corporation, [
                'name' => $data['name'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'timezone' => $data['timezone'] ?? 'America/Sao_Paulo',
            ]);

            $owner->current_venue_id = $venue->id;
            $owner->save();

            SendWelcomeEmailJob::dispatch($corporation, $owner, $temporaryPassword);

            return $corporation->fresh();
        });
    }
}
