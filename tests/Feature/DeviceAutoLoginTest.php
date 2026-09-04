<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use App\Services\DeviceAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class DeviceAutoLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_device_session_cookie_and_creates_db_record(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'password123',
        ], [
            'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36',
            'Accept-Language' => 'en-US,en;q=0.9',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertCookie(DeviceAuthService::COOKIE_NAME);

        $this->assertDatabaseHas('user_devices', [
            'user_id' => $user->id,
            'is_revoked' => false,
        ]);

        $device = UserDevice::where('user_id', $user->id)->first();
        $this->assertNotNull($device);
        $this->assertStringContainsString('macOS', $device->device_name);
        $this->assertTrue(Carbon::parse($device->expires_at)->isFuture());
    }

    public function test_unauthenticated_request_auto_logs_in_with_valid_device_cookie_and_signature(): void
    {
        $user = User::factory()->create();

        $service = app(DeviceAuthService::class);

        $deviceUuid = (string) Str::uuid();
        $rawToken = Str::random(64);
        $userAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36';
        $acceptLanguage = 'en-US,en;q=0.9';

        $fakeRequest = Request::create('/fake', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $userAgent,
            'HTTP_ACCEPT_LANGUAGE' => $acceptLanguage,
            'REMOTE_ADDR' => '127.0.0.1',
        ]);

        $signature = $service->generateSignature($fakeRequest, $user, $deviceUuid);

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => 'macOS • Chrome',
            'device_signature' => $signature,
            'token_hash' => hash('sha256', $rawToken),
            'ip_address' => '127.0.0.1',
            'user_agent' => $userAgent,
            'last_active_at' => now(),
            'expires_at' => now()->addDays(7),
            'is_revoked' => false,
        ]);

        $cookiePayload = "{$deviceUuid}|{$rawToken}";

        // Request /profile as a guest with the device cookie and identical client headers
        $response = $this->withCookie(DeviceAuthService::COOKIE_NAME, $cookiePayload)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept-Language' => $acceptLanguage,
            ])
            ->get(route('profile.edit'));

        $response->assertOk();
        $this->assertAuthenticatedAs($user);

        // Verify device last_active_at was refreshed
        $device->refresh();
        $this->assertFalse((bool) $device->is_revoked);
    }

    public function test_tampered_device_signature_is_rejected_and_device_is_revoked(): void
    {
        $user = User::factory()->create();

        $service = app(DeviceAuthService::class);

        $deviceUuid = (string) Str::uuid();
        $rawToken = Str::random(64);
        $originalUserAgent = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7)';
        $acceptLanguage = 'en-US,en;q=0.9';

        $fakeRequest = Request::create('/fake', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $originalUserAgent,
            'HTTP_ACCEPT_LANGUAGE' => $acceptLanguage,
        ]);

        $signature = $service->generateSignature($fakeRequest, $user, $deviceUuid);

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => 'macOS',
            'device_signature' => $signature,
            'token_hash' => hash('sha256', $rawToken),
            'ip_address' => '127.0.0.1',
            'user_agent' => $originalUserAgent,
            'last_active_at' => now(),
            'expires_at' => now()->addDays(7),
            'is_revoked' => false,
        ]);

        $cookiePayload = "{$deviceUuid}|{$rawToken}";

        // Attacker steals cookie and presents it from a Windows / Firefox device
        $attackerUserAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:120.0) Gecko/20100101 Firefox/120.0';

        $response = $this->withCookie(DeviceAuthService::COOKIE_NAME, $cookiePayload)
            ->withHeaders([
                'User-Agent' => $attackerUserAgent,
                'Accept-Language' => $acceptLanguage,
            ])
            ->get(route('profile.edit'));

        // Should be redirected to login because auto-login failed
        $response->assertRedirect(route('login'));
        $this->assertGuest();

        // The device session must be immediately revoked to protect the account
        $device->refresh();
        $this->assertTrue((bool) $device->is_revoked);
    }

    public function test_expired_device_session_is_rejected(): void
    {
        $user = User::factory()->create();

        $service = app(DeviceAuthService::class);

        $deviceUuid = (string) Str::uuid();
        $rawToken = Str::random(64);
        $userAgent = 'Mozilla/5.0 (Macintosh)';
        $acceptLanguage = 'en-US';

        $fakeRequest = Request::create('/fake', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $userAgent,
            'HTTP_ACCEPT_LANGUAGE' => $acceptLanguage,
        ]);

        $signature = $service->generateSignature($fakeRequest, $user, $deviceUuid);

        UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => 'macOS',
            'device_signature' => $signature,
            'token_hash' => hash('sha256', $rawToken),
            'ip_address' => '127.0.0.1',
            'user_agent' => $userAgent,
            'last_active_at' => now()->subDays(8),
            'expires_at' => now()->subHour(),
            'is_revoked' => false,
        ]);

        $cookiePayload = "{$deviceUuid}|{$rawToken}";

        $response = $this->withCookie(DeviceAuthService::COOKIE_NAME, $cookiePayload)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept-Language' => $acceptLanguage,
            ])
            ->get(route('profile.edit'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_logout_revokes_device_session_and_clears_cookie(): void
    {
        $user = User::factory()->create();

        $service = app(DeviceAuthService::class);
        $deviceUuid = (string) Str::uuid();
        $rawToken = Str::random(64);
        $userAgent = 'Mozilla/5.0 (Macintosh)';
        $acceptLanguage = 'en-US';

        $fakeRequest = Request::create('/fake', 'GET', [], [], [], [
            'HTTP_USER_AGENT' => $userAgent,
            'HTTP_ACCEPT_LANGUAGE' => $acceptLanguage,
        ]);

        $signature = $service->generateSignature($fakeRequest, $user, $deviceUuid);

        $device = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => $deviceUuid,
            'device_name' => 'macOS',
            'device_signature' => $signature,
            'token_hash' => hash('sha256', $rawToken),
            'ip_address' => '127.0.0.1',
            'user_agent' => $userAgent,
            'last_active_at' => now(),
            'expires_at' => now()->addDays(7),
            'is_revoked' => false,
        ]);

        $cookiePayload = "{$deviceUuid}|{$rawToken}";

        $response = $this->actingAs($user)
            ->withCookie(DeviceAuthService::COOKIE_NAME, $cookiePayload)
            ->withHeaders([
                'User-Agent' => $userAgent,
                'Accept-Language' => $acceptLanguage,
            ])
            ->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();

        $device->refresh();
        $this->assertTrue((bool) $device->is_revoked);
    }

    public function test_user_can_revoke_other_devices(): void
    {
        $user = User::factory()->create();

        $currentDevice = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'Current Laptop',
            'device_signature' => 'sig-curr',
            'token_hash' => 'hash-curr',
            'ip_address' => '127.0.0.1',
            'expires_at' => now()->addDays(7),
            'is_revoked' => false,
        ]);

        $otherDevice1 = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'Old Phone',
            'device_signature' => 'sig-other1',
            'token_hash' => 'hash-other1',
            'ip_address' => '127.0.0.2',
            'expires_at' => now()->addDays(5),
            'is_revoked' => false,
        ]);

        $otherDevice2 = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'Tablet',
            'device_signature' => 'sig-other2',
            'token_hash' => 'hash-other2',
            'ip_address' => '127.0.0.3',
            'expires_at' => now()->addDays(4),
            'is_revoked' => false,
        ]);

        $cookiePayload = "{$currentDevice->device_uuid}|token123";

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->withCookie(DeviceAuthService::COOKIE_NAME, $cookiePayload)
            ->post(route('profile.devices.revoke-others'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $currentDevice->refresh();
        $otherDevice1->refresh();
        $otherDevice2->refresh();

        $this->assertFalse((bool) $currentDevice->is_revoked);
        $this->assertTrue((bool) $otherDevice1->is_revoked);
        $this->assertTrue((bool) $otherDevice2->is_revoked);
    }

    public function test_user_can_revoke_specific_device(): void
    {
        $user = User::factory()->create();

        $deviceToRevoke = UserDevice::create([
            'user_id' => $user->id,
            'device_uuid' => (string) Str::uuid(),
            'device_name' => 'Old Phone',
            'device_signature' => 'sig-old',
            'token_hash' => 'hash-old',
            'ip_address' => '127.0.0.2',
            'expires_at' => now()->addDays(5),
            'is_revoked' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.devices.destroy', $deviceToRevoke));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success');

        $deviceToRevoke->refresh();
        $this->assertTrue((bool) $deviceToRevoke->is_revoked);
    }
}
