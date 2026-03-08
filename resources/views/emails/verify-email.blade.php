<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HRIS Account Activation</title>
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
            background-color: #f1f5f9;
            padding-bottom: 40px;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #2563eb; /* Warna solid lebih aman untuk email */
            padding: 50px 30px;
            text-align: center;
        }
        .header h1 {
            color: #ffffff;
            margin: 0;
            font-size: 26px;
            font-weight: 800;
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
            background-color: #2563eb;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 600;
            font-size: 16px;
            /* Menghapus transition dan shadow yang kompleks agar tidak 'glitchy' */
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
            background-color: #f1f5f9;
            border-radius: 8px;
            word-break: break-all;
            font-size: 12px;
            color: #64748b;
            border: 1px dashed #cbd5e1;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <h1>HRIS Account Activation</h1>
            </div>
            <div class="content">
                <p class="user-greeting">Hello, {{ $user->name }}</p>
                <p>Your HRIS account has been successfully created. Please activate your email to start accessing attendance features and employment data.</p>

                <div class="button-wrapper">
                    <a href="{{ $url }}" class="button">Activate Account Now</a>
                </div>

                <p>This activation link will expire in <strong>24 hours</strong>.</p>

                <div class="fallback">
                    <strong>Having trouble?</strong> Copy and paste the link below:<br>
                    <span style="color: #2563eb;">{{ $url }}</span>
                </div>
            </div>
            <div class="footer">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
