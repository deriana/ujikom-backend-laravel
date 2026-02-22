Hello, {{ $user->name }}!

You are receiving this email because we received a password reset request for your account.

Reset Password: {{ $url }}

This link will expire in 60 minutes.

If you did not request a password reset, no further action is required.

Regards,
{{ config('app.name') }}