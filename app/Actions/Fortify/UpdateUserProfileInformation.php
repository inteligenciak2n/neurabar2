<?php

namespace App\Actions\Fortify;

use App\Enums\PreferredPhoneHand;
use App\Models\User;
use App\Services\Languages\TranslationService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    /**
     * Validate and update the given user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'photo' => ['nullable', 'mimes:jpg,jpeg,png', 'max:1024'],
            'lang' => ['nullable', 'string', 'in:'.implode(',', TranslationService::getLanguageKeys())],
            'preferred_phone_hand' => ['nullable', Rule::enum(PreferredPhoneHand::class)],
        ])->validateWithBag('updateProfileInformation');

        if (isset($input['photo'])) {
            $user->updateProfilePhoto($input['photo']);
        }

        if ($input['email'] !== $user->email &&
            $user instanceof MustVerifyEmail) {
            $this->updateVerifiedUser($user, $input);
        } else {
            $user->forceFill([
                'name' => $input['name'],
                'email' => $input['email'],
                'lang' => $input['lang'] ?? $user->lang ?? 'pt',
                'preferred_phone_hand' => $this->preferredPhoneHandFrom($input, $user),
            ])->save();

            $user->setSessionLanguage();
        }
    }

    /**
     * Update the given verified user's profile information.
     *
     * @param  array<string, mixed>  $input
     */
    protected function updateVerifiedUser(User $user, array $input): void
    {
        $user->forceFill([
            'name' => $input['name'],
            'email' => $input['email'],
            'lang' => $input['lang'] ?? $user->lang ?? 'pt',
            'preferred_phone_hand' => $this->preferredPhoneHandFrom($input, $user),
            'email_verified_at' => null,
        ])->save();

        $user->sendEmailVerificationNotification();
    }

    /** @param  array<string, mixed>  $input */
    private function preferredPhoneHandFrom(array $input, User $user): string
    {
        if (! array_key_exists('preferred_phone_hand', $input)) {
            return ($user->preferred_phone_hand ?? PreferredPhoneHand::default())->value;
        }

        return filled($input['preferred_phone_hand'])
            ? (string) $input['preferred_phone_hand']
            : PreferredPhoneHand::default()->value;
    }
}
