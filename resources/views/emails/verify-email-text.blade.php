Hello {{ $user->name }},

Your HRIS account has been successfully created by the system. Please activate your email to start accessing attendance features, payslips, and other employment data through the link below:

Activate Account Now: {{ $url }}

For security reasons, this activation link will expire in 24 hours. If you feel you should not have received this email, please contact the IT or HR department.

Regards,
{{ config('app.name') }} Team
