<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Password Reset Verification Code</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 40px 20px; }
        .card { max-width: 500px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 36px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .logo { width: 48px; height: 48px; border-radius: 12px; margin-bottom: 20px; }
        h1 { font-size: 20px; font-weight: 800; color: #0f172a; margin: 0 0 12px; }
        p { font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 16px; }
        .otp-box { background-color: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 18px; text-align: center; margin: 24px 0; }
        .otp-code { font-family: 'Courier New', Courier, monospace; font-size: 36px; font-weight: 900; letter-spacing: 8px; color: #4338ca; }
        .timer { font-size: 13px; font-weight: 600; color: #dc2626; margin-top: 8px; }
        .notice { font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 16px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <img src="https://storage.macm.dev/portfolio/favicons/cmj8uwynb0000nj0jnkb3tk15/1786703371734.webp" alt="Checking TA Logo" class="logo">
        <h1>Password Reset Request</h1>
        <p>You recently requested to reset your password for your <strong>Checking TA</strong> account. Enter the 6-digit verification code below to set a new password:</p>
        
        <div class="otp-box">
            <div class="otp-code">{{ $otp }}</div>
            <div class="timer">Expires in {{ $expiresInMinutes }} minutes</div>
        </div>

        <p>If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>

        <div class="notice">
            This verification code is single-use and will expire in 15 minutes for your security.
        </div>
    </div>
</body>
</html>
