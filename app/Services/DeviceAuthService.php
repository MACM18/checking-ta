<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Cookie;

class DeviceAuthService
{
    public const COOKIE_NAME = 'checking_device_sig';

    public const LIFETIME_DAYS = 7;

    /**
     * Compute a cryptographic HMAC-SHA256 signature for this device and user.
     */
    public function generateSignature(Request $request, User $user, string $deviceUuid): string
    {
        $ua = (string) ($request->header('User-Agent') ?? '');
        $lang = (string) ($request->header('Accept-Language') ?? '');
        $entropy = "{$user->id}|{$deviceUuid}|{$ua}|{$lang}";

        return hash_hmac('sha256', $entropy, config('app.key') ?: 'checking-ta-secret-device-key');
    }

    /**
     * Human-friendly parser for Operating System and Browser.
     */
    public function parseDeviceName(?string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Device';
        }

        // Detect Operating System / Platform
        $os = 'Unknown OS';
        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            $os = 'iOS Device';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            $os = 'macOS';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        }

        // Detect Browser
        $browser = 'Browser';
        if (preg_match('/Edg\/([0-9.]+)/i', $userAgent)) {
            $browser = 'Edge';
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $userAgent) && ! preg_match('/Edg/i', $userAgent)) {
            $browser = 'Chrome';
        } elseif (preg_match('/Firefox\/([0-9.]+)/i', $userAgent)) {
            $browser = 'Firefox';
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $userAgent) && ! preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/OPR\/([0-9.]+)/i', $userAgent) || preg_match('/Opera/i', $userAgent)) {
            $browser = 'Opera';
        }

        return "{$os} • {$browser}";
    }

    /**
     * Issue a new 7-day device session and return an HTTP-only secure cookie.
     */
    public function issueDeviceSession(User $user, Request $request): Cookie
    {
        $deviceUuid = (string) Str::uuid();
        $rawToken = Str::random(64);
        $tokenHash = hash('sha256', $rawToken);
        $signature = $this->generateSignature($request, $user, $deviceUuid);
        $deviceName = $this->parseDeviceName($request->userAgent());

        UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => $deviceName,
            'device_signature' => $signature,
            'token_hash' => $tokenHash,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'last_active_at' => now(),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
            'is_revoked' => false,
        ]);

        $cookieValue = "{$deviceUuid}|{$rawToken}";
        $minutes = self::LIFETIME_DAYS * 24 * 60;

        return cookie(
            self::COOKIE_NAME,
            $cookieValue,
            $minutes,
            '/',
            null,
            $request->isSecure(),
            true, // HttpOnly
            false,
            'lax'
        );
    }

    /**
     * Attempt automatic authentication from the device signature cookie.
     */
    public function attemptAutoLogin(Request $request): ?User
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        if (! $cookieValue || ! is_string($cookieValue) || ! str_contains($cookieValue, '|')) {
            return null;
        }

        [$deviceUuid, $rawToken] = explode('|', $cookieValue, 2);
        if (empty($deviceUuid) || empty($rawToken)) {
            return null;
        }

        $device = UserDevice::with('user')
            ->where('device_uuid', $deviceUuid)
            ->where('is_revoked', false)
            ->first();

        if (! $device || $device->isExpired() || ! $device->user) {
            return null;
        }

        // Verify cryptographic secret token
        if (! hash_equals($device->token_hash, hash('sha256', $rawToken))) {
            return null;
        }

        // Verify cryptographic device signature
        $currentSignature = $this->generateSignature($request, $device->user, $deviceUuid);
        if (! hash_equals($device->device_signature, $currentSignature)) {
            // Tamper/Theft alert: Stolen cookie used on mismatched browser or operating system!
            // Instantly revoke this device session and forget the cookie.
            $device->revoke();
            cookie()->queue(cookie()->forget(self::COOKIE_NAME));

            return null;
        }

        // Authenticate the user
        Auth::login($device->user);

        // Slide the 7-day expiration window and update activity
        $device->update([
            'last_active_at' => now(),
            'ip_address' => $request->ip(),
            'expires_at' => now()->addDays(self::LIFETIME_DAYS),
        ]);

        // Refresh the client cookie for another 7 days
        $minutes = self::LIFETIME_DAYS * 24 * 60;
        cookie()->queue(cookie(
            self::COOKIE_NAME,
            "{$deviceUuid}|{$rawToken}",
            $minutes,
            '/',
            null,
            $request->isSecure(),
            true,
            false,
            'lax'
        ));

        return $device->user;
    }

    /**
     * Revoke the current device session upon logout.
     */
    public function revokeCurrentDevice(Request $request): Cookie
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        if ($cookieValue && is_string($cookieValue) && str_contains($cookieValue, '|')) {
            [$deviceUuid] = explode('|', $cookieValue, 2);
            if (! empty($deviceUuid)) {
                UserDevice::where('device_uuid', $deviceUuid)->update(['is_revoked' => true]);
            }
        }

        cookie()->queue(cookie()->forget(self::COOKIE_NAME));

        return cookie()->forget(self::COOKIE_NAME);
    }

    /**
     * Revoke a specific device belonging to a user.
     */
    public function revokeDevice(User $user, int $deviceId): bool
    {
        $device = $user->devices()->where('id', $deviceId)->first();
        if ($device) {
            $device->revoke();

            return true;
        }

        return false;
    }

    /**
     * Revoke all other devices for a user except the current device.
     */
    public function revokeOtherDevices(User $user, Request $request): int
    {
        $currentUuid = null;
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        if ($cookieValue && is_string($cookieValue) && str_contains($cookieValue, '|')) {
            [$currentUuid] = explode('|', $cookieValue, 2);
        }

        $query = $user->devices()->where('is_revoked', false);
        if ($currentUuid) {
            $query->where('device_uuid', '!=', $currentUuid);
        }

        return $query->update(['is_revoked' => true]);
    }

    /**
     * Check if a device UUID matches the current request's cookie.
     */
    public function isCurrentDevice(Request $request, string $deviceUuid): bool
    {
        $cookieValue = $request->cookie(self::COOKIE_NAME);
        if ($cookieValue && is_string($cookieValue) && str_contains($cookieValue, '|')) {
            [$currentUuid] = explode('|', $cookieValue, 2);

            return $currentUuid === $deviceUuid;
        }

        return false;
    }
}
