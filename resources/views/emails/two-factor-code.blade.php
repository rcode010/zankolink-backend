<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Your ZankoLink Verification Code</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #f0f0ed; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif; -webkit-font-smoothing: antialiased; }
        .shell { background: #f0f0ed; padding: 40px 20px; }
        .email { background: #ffffff; border-radius: 16px; width: 100%; max-width: 520px; margin: 0 auto; overflow: hidden; }
        .header { background: #0f172a; padding: 32px 40px 28px; }
        .logo { display: flex; align-items: center; gap: 10px; }
        .logo-mark { width: 32px; height: 32px; background: #3b82f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
        .logo-name { color: #ffffff; font-size: 17px; font-weight: 600; letter-spacing: -0.3px; }
        .body { padding: 36px 40px 32px; }
        .badge { display: inline-flex; align-items: center; gap: 5px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 20px; padding: 4px 10px; font-size: 11px; color: #166534; font-weight: 500; margin-bottom: 24px; }
        .badge-dot { width: 5px; height: 5px; background: #22c55e; border-radius: 50%; display: inline-block; }
        .greeting { font-size: 13px; color: #6b7280; letter-spacing: 0.3px; text-transform: uppercase; font-weight: 500; margin-bottom: 10px; }
        .headline { font-size: 22px; font-weight: 600; color: #0f172a; letter-spacing: -0.4px; line-height: 1.3; margin-bottom: 16px; }
        .subtext { font-size: 14px; color: #6b7280; line-height: 1.6; margin-bottom: 32px; }
        .otp-block { background: #f8faff; border: 1px solid #dbeafe; border-radius: 12px; padding: 24px; text-align: center; margin-bottom: 28px; }
        .otp-label { font-size: 11px; color: #93c5fd; letter-spacing: 1.2px; text-transform: uppercase; font-weight: 600; margin-bottom: 14px; }
        .otp-digits { display: flex; justify-content: center; gap: 8px; margin-bottom: 16px; }
        .digit { width: 44px; height: 54px; background: #ffffff; border: 1.5px solid #bfdbfe; border-radius: 10px; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; font-weight: 700; color: #1e40af; }
        .otp-timer { display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 12px; color: #6b7280; }
        .timer-dot { width: 6px; height: 6px; background: #22c55e; border-radius: 50%; display: inline-block; }
        .divider { height: 1px; background: #f3f4f6; margin: 0 0 24px; }
        .warning { display: flex; gap: 12px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 10px; padding: 14px 16px; margin-bottom: 28px; }
        .warning-text { font-size: 13px; color: #92400e; line-height: 1.5; }
        .warning-text strong { font-weight: 600; }
        .footer-note { font-size: 13px; color: #9ca3af; line-height: 1.6; margin-bottom: 24px; }
        .footer-note a { color: #3b82f6; text-decoration: none; }
        .footer-note strong { color: #6b7280; font-weight: 500; }
        .footer { border-top: 1px solid #f3f4f6; padding: 20px 40px; }
        .footer-inner { display: flex; justify-content: space-between; align-items: center; }
        .footer-brand { font-size: 12px; color: #9ca3af; }
        .footer-links { display: flex; gap: 16px; }
        .footer-links a { font-size: 12px; color: #9ca3af; text-decoration: none; }
    </style>
</head>
<body>
<div class="shell">
    <div class="email">

        <div class="header">
            <div class="logo">
                <div class="logo-mark">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 2L15.5 6V12L9 16L2.5 12V6L9 2Z" stroke="white" stroke-width="1.5" stroke-linejoin="round"/>
                        <circle cx="9" cy="9" r="2.5" fill="white"/>
                    </svg>
                </div>
                <span class="logo-name">ZankoLink</span>
            </div>
        </div>

        <div class="body">
            <div class="badge">
                <span class="badge-dot"></span>
                Security verification
            </div>

            <p class="greeting">Hello, {{ $user->name }}</p>
            <h1 class="headline">Your one-time<br>verification code</h1>
            <p class="subtext">Use the code below to complete your sign-in. It's only valid for the next 10 minutes.</p>

            <div class="otp-block">
                <p class="otp-label">Verification code</p>
                <div class="otp-digits">
                    @foreach(str_split((string) $otp) as $digit)
                        <div class="digit">{{ $digit }}</div>
                    @endforeach
                </div>
                <div class="otp-timer">
                    <span class="timer-dot"></span>
                    Expires in 10 minutes
                </div>
            </div>

            <div class="warning">
                <div style="flex-shrink:0; margin-top:2px;">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M9 1.5L16.5 15H1.5L9 1.5Z" stroke="#d97706" stroke-width="1.5" stroke-linejoin="round"/>
                        <path d="M9 7V10" stroke="#d97706" stroke-width="1.5" stroke-linecap="round"/>
                        <circle cx="9" cy="12.5" r="0.75" fill="#d97706"/>
                    </svg>
                </div>
                <p class="warning-text">
                    <strong>Didn't request this?</strong> Someone may be trying to access your account. Secure it immediately by changing your password.
                </p>
            </div>

            <div class="divider"></div>

            <p class="footer-note">
                This code was requested from <strong>Erbil, Iraq</strong>.
                If this wasn't you, <a href="{{ config('app.url') }}">contact support</a> right away.
            </p>
        </div>

        <div class="footer">
            <div class="footer-inner">
                <span class="footer-brand">© {{ date('Y') }} ZankoLink · Kurdistan Region</span>
                <div class="footer-links">
                    <a href="{{ config('app.url') }}">Support</a>
                    <a href="{{ config('app.url') }}">Privacy</a>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
