<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verification Pending</title>
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8 text-center">
        <div class="mb-6 inline-flex items-center justify-center w-16 h-16 bg-blue-100 text-blue-600 rounded-full">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Verify Your Email</h1>
        <p class="text-slate-600 mb-8">We've sent a verification link to your email address. Please click the link to activate your account.</p>
        
        <div id="resend-section">
            <p class="text-sm text-slate-500 mb-4">Didn't receive the email?</p>
            <button id="resend-btn" onclick="resendEmail()" class="inline-block w-full py-3 px-6 bg-slate-900 hover:bg-slate-800 text-white font-semibold rounded-xl transition duration-200 disabled:bg-slate-300 disabled:cursor-not-allowed">
                Resend Verification Link
            </button>
            <p id="timer" class="text-sm text-blue-600 mt-4 hidden">You can resend again in <span id="countdown">60</span>s</p>
            <p id="message" class="text-sm mt-4 hidden"></p>
        </div>
    </div>

    <script>
        async function resendEmail() {
            const btn = document.getElementById('resend-btn');
            const timer = document.getElementById('timer');
            const countdown = document.getElementById('countdown');
            const message = document.getElementById('message');
            
            // Get email from URL or prompt (for demo purposes)
            let email = new URLSearchParams(window.location.search).get('email');
            if (!email) {
                email = prompt("Please enter your email address:");
            }

            if (!email) return;

            btn.disabled = true;
            message.classList.remove('hidden', 'text-red-600', 'text-green-600');
            message.textContent = 'Sending...';

            try {
                const response = await fetch('/api/auth/resend-verification', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email })
                });

                const data = await response.json();

                if (response.ok) {
                    message.textContent = data.message;
                    message.classList.add('text-green-600', 'hidden'); // keep it green but hide after some time or keep it
                    message.classList.remove('hidden');
                    startTimer(60);
                } else {
                    message.textContent = data.message || 'Something went wrong.';
                    message.classList.add('text-red-600');
                    message.classList.remove('hidden');
                    btn.disabled = false;
                }
            } catch (error) {
                message.textContent = 'Network error. Please try again.';
                message.classList.add('text-red-600');
                message.classList.remove('hidden');
                btn.disabled = false;
            }
        }

        function startTimer(seconds) {
            const btn = document.getElementById('resend-btn');
            const timerEl = document.getElementById('timer');
            const countdownEl = document.getElementById('countdown');
            
            timerEl.classList.remove('hidden');
            let remaining = seconds;
            
            const interval = setInterval(() => {
                remaining--;
                countdownEl.textContent = remaining;
                if (remaining <= 0) {
                    clearInterval(interval);
                    btn.disabled = false;
                    timerEl.classList.add('hidden');
                }
            }, 1000);
        }
    </script>
</body>
</html>
