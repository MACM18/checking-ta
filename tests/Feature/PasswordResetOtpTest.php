<?php

namespace Tests\Feature;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_15_minute_password_reset_otp(): void
    {
        Mail::fake();

        $user = User::factory()->create([
            'email' => 'warehouse@company.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'warehouse@company.com',
        ]);

        $response->assertRedirect(route('password.otp.verify', ['email' => 'warehouse@company.com']));

        $record = PasswordResetOtp::where('email', 'warehouse@company.com')->first();
        $this->assertNotNull($record);
        $this->assertEquals(6, strlen($record->otp));
        $this->assertTrue($record->expires_at->isFuture());
        // Verify roughly 15 minutes
        $this->assertTrue($record->expires_at->diffInMinutes(Carbon::now()) <= 15);

        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) use ($record) {
            return $mail->otp === $record->otp;
        });
    }

    public function test_user_can_reset_password_with_valid_otp(): void
    {
        $user = User::factory()->create([
            'email' => 'manager@company.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        PasswordResetOtp::create([
            'email' => 'manager@company.com',
            'otp' => '654321',
            'expires_at' => Carbon::now()->addMinutes(15),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->post(route('password.otp.reset'), [
            'email' => 'manager@company.com',
            'otp' => '654321',
            'password' => 'NewFreshPassword2026!',
            'password_confirmation' => 'NewFreshPassword2026!',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');

        $this->assertTrue(Hash::check('NewFreshPassword2026!', $user->fresh()->password));
        $this->assertEquals(0, PasswordResetOtp::where('email', 'manager@company.com')->count());
    }

    public function test_expired_otp_is_rejected(): void
    {
        $user = User::factory()->create([
            'email' => 'worker@company.com',
            'password' => Hash::make('OldPassword123!'),
        ]);

        // OTP expired 1 minute ago
        PasswordResetOtp::create([
            'email' => 'worker@company.com',
            'otp' => '112233',
            'expires_at' => Carbon::now()->subMinute(),
            'created_at' => Carbon::now()->subMinutes(16),
        ]);

        $response = $this->post(route('password.otp.reset'), [
            'email' => 'worker@company.com',
            'otp' => '112233',
            'password' => 'BrandNewPassword2026!',
            'password_confirmation' => 'BrandNewPassword2026!',
        ]);

        $response->assertSessionHasErrors('otp');
        $this->assertFalse(Hash::check('BrandNewPassword2026!', $user->fresh()->password));
    }
}
