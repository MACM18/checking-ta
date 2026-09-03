<?php

namespace Tests\Feature\Auth;

use App\Mail\PasswordResetOtpMail;
use App\Models\PasswordResetOtp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_can_be_rendered(): void
    {
        $response = $this->get('/forgot-password');

        $response->assertStatus(200);
    }

    public function test_password_reset_otp_can_be_requested(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        $response = $this->post('/forgot-password', ['email' => $user->email]);

        $response->assertRedirect(route('password.otp.verify', ['email' => $user->email]));

        Mail::assertSent(PasswordResetOtpMail::class, function ($mail) {
            return strlen($mail->otp) === 6 && $mail->expiresInMinutes === 15;
        });
    }

    public function test_otp_verification_screen_can_be_rendered(): void
    {
        $user = User::factory()->create();

        $response = $this->get(route('password.otp.verify', ['email' => $user->email]));

        $response->assertStatus(200);
        $response->assertSee($user->email);
    }

    public function test_password_can_be_reset_with_valid_otp(): void
    {
        $user = User::factory()->create();

        PasswordResetOtp::create([
            'email' => $user->email,
            'otp' => '987654',
            'expires_at' => Carbon::now()->addMinutes(15),
            'created_at' => Carbon::now(),
        ]);

        $response = $this->post(route('password.otp.reset'), [
            'email' => $user->email,
            'otp' => '987654',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }
}
