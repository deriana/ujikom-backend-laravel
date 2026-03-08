<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body {
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f1f5f9;
            padding-bottom: 40px;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05);
        }
        .header {
            background: linear-gradient(135deg, #e11d48 0%, #fb7185 100%);
            padding: 50px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.025em;
        }
        .content {
            padding: 40px 40px;
            text-align: left;
            line-height: 1.6;
        }
        .content p {
            font-size: 16px;
            color: #475569;
            margin-bottom: 20px;
        }
        .user-greeting {
            font-weight: 700;
            color: #1e293b;
            font-size: 18px;
        }
        .button-wrapper {
            text-align: center;
            margin: 35px 0;
        }
        .button {
            display: inline-block;
            padding: 16px 36px;
            background-color: #e11d48;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            box-shadow: 0 4px 6px -1px rgba(225, 29, 72, 0.2);
            transition: all 0.2s;
        }
        .footer {
            padding: 30px;
            background-color: #f8fafc;
            text-align: center;
            border-top: 1px solid #edf2f7;
        }
        .footer p {
            font-size: 13px;
            color: #94a3b8;
            margin: 5px 0;
        }
        .fallback {
            margin-top: 25px;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 8px;
            word-break: break-all;
            font-size: 12px;
            color: #64748b;
            border: 1px solid #e2e8f0;
        }
        .notice {
            font-size: 14px;
            color: #94a3b8;
            font-style: italic;
            border-left: 3px solid #e2e8f0;
            padding-left: 15px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>Reset Password</h1>
            </div>
            <div class="content">
                <p class="user-greeting">Hello, {{ $user->name }}</p>
                <p>We received a request to reset the password for your HRIS account. Don't worry, it happens to the best of us.</p>

                <div class="button-wrapper">
                    <a href="{{ $url }}" class="button">Reset Password</a>
                </div>

                <p>This link is only valid for <strong>60 minutes</strong> for your account security.</p>

                <p class="notice">If you did not request a password reset, please ignore this email. Your password will remain unchanged.</p>

                <div class="fallback">
                    <strong>Having trouble?</strong> Copy and paste the link below into your browser:<br>
                    <span style="color: #e11d48;">{{ $url }}</span>
                </div>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
                <p>This email was sent automatically to maintain your account security.</p>
            </div>
        </div>
    </div>
</body>
</html>
