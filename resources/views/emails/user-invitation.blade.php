<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Workspace Invitation</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 40px 20px; }
        .card { max-width: 540px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 36px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .logo { width: 52px; height: 52px; border-radius: 12px; margin-bottom: 20px; }
        h1 { font-size: 22px; font-weight: 800; color: #0f172a; margin: 0 0 12px; }
        p { font-size: 14px; line-height: 1.6; color: #475569; margin: 0 0 16px; }
        .btn { display: inline-block; background-color: #4f46e5; color: #ffffff !important; font-weight: 700; font-size: 14px; text-decoration: none; padding: 12px 28px; border-radius: 10px; margin: 16px 0 24px; }
        .badge { display: inline-block; padding: 4px 10px; background: #e0e7ff; color: #4338ca; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; }
        .notice { font-size: 12px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 16px; margin-top: 24px; }
    </style>
</head>
<body>
    <div class="card">
        <img src="https://storage.macm.dev/portfolio/favicons/cmj8uwynb0000nj0jnkb3tk15/1786703371734.webp" alt="Checking TA Logo" class="logo">
        <div class="badge">{{ ucfirst($user->role) }} Access</div>
        <h1>Welcome to Checking TA, {{ $user->name }}!</h1>
        <p>You have been invited to join the <strong>Checking TA</strong> workspace. An account has been created for you with the email <code>{{ $user->email }}</code>.</p>
        <p>Click the secure link below to sign in instantly. This magic login link is valid for <strong>24 hours</strong>. Upon your first sign-in, you will be prompted to create your personal account password.</p>
        
        <p style="text-align: center;">
            <a href="{{ $magicLink }}" class="btn">Sign In & Set Password &rarr;</a>
        </p>

        <p style="font-size: 12px; color: #64748b; word-break: break-all;">
            Or copy and paste this URL into your browser:<br>
            <a href="{{ $magicLink }}" style="color: #4f46e5;">{{ $magicLink }}</a>
        </p>

        <div class="notice">
            This invitation link expires in 24 hours. If you did not expect this invitation, you can safely ignore this email.
        </div>
    </div>
</body>
</html>
