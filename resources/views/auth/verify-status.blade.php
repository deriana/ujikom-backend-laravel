<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - {{ ucfirst($status) }}</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        @if($status === 'success')
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 bg-green-100 text-green-600 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Email Verified!</h1>
            <p class="text-slate-600 mb-8">Thank you for verifying your email. Your account is now fully activated.</p>
            <a href="/" class="inline-block w-full py-3 px-6 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition duration-200">
                Go to Dashboard
            </a>
        @elseif($status === 'expired')
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 bg-orange-100 text-orange-600 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Link Expired</h1>
            <p class="text-slate-600 mb-8">The verification link has expired. Please request a new one.</p>
            <a href="{{ route('verification.pending') }}" class="inline-block w-full py-3 px-6 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition duration-200">
                Request New Link
            </a>
        @else
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 bg-red-100 text-red-600 rounded-full">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-slate-900 mb-2">Invalid Token</h1>
            <p class="text-slate-600 mb-8">This verification link is invalid or has already been used.</p>
            <a href="/" class="inline-block w-full py-3 px-6 bg-slate-200 hover:bg-slate-300 text-slate-700 font-semibold rounded-xl transition duration-200">
                Back to Home
            </a>
        @endif
    </div>
</body>
</html>
