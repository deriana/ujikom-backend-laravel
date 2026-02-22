serHello {{ $user->name }},

Thanks for signing up. Please verify your email address by clicking the link below. This link will expire in 24 hours.

Verify Email Address: {{ $url }}

If you did not create an account, no further action is required.

Regards,
{{ config('app.name') }}
