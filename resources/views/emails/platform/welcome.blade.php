@component('mail::message')
# Welcome to NeuraBar

Hello {{ $owner->name }},

Your account for **{{ $corporation->name }}** has been created.

**Email:** {{ $owner->email }}
**Temporary Password:** `{{ $temporaryPassword }}`

Please log in and change your password as soon as possible.

@component('mail::button', ['url' => url('/login')])
Access NeuraBar
@endcomponent

Thanks,
The NeuraBar Team
@endcomponent
