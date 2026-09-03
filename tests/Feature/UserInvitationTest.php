<?php

namespace Tests\Feature;

use App\Mail\UserInvitationMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_invite_user_with_24_hour_magic_link(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Colleague',
            'email' => 'colleague@example.com',
            'role' => 'editor',
            'send_invitation' => '1',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $user = User::where('email', 'colleague@example.com')->first();
        $this->assertNotNull($user);
        $this->assertNotNull($user->invitation_token);
        $this->assertTrue($user->must_set_password);
        $this->assertNotNull($user->invitation_expires_at);
        $this->assertTrue($user->invitation_expires_at->isFuture());

        Mail::assertSent(UserInvitationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }

    public function test_user_can_sign_in_via_valid_magic_link_and_is_forced_to_set_password(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'invitation_token' => 'test-valid-token-123456',
            'invitation_expires_at' => Carbon::now()->addHours(24),
            'must_set_password' => true,
        ]);

        // Accept magic link
        $response = $this->get(route('invitation.accept', ['token' => 'test-valid-token-123456']));

        $response->assertRedirect(route('password.setup'));
        $this->assertAuthenticatedAs($user);

        // Invitation token should be cleared (single-use)
        $this->assertNull($user->fresh()->invitation_token);

        // Attempting to visit documents while must_set_password is true redirects to password.setup
        $docResponse = $this->get(route('documents.index'));
        $docResponse->assertRedirect(route('password.setup'));

        // Submit new password
        $setPassResponse = $this->post(route('password.setup.store'), [
            'password' => 'SecurePass2026!',
            'password_confirmation' => 'SecurePass2026!',
        ]);

        $setPassResponse->assertRedirect(route('documents.index'));
        $this->assertFalse($user->fresh()->must_set_password);
        $this->assertTrue(Hash::check('SecurePass2026!', $user->fresh()->password));
    }

    public function test_expired_invitation_link_is_rejected(): void
    {
        $user = User::factory()->create([
            'role' => 'editor',
            'invitation_token' => 'expired-token-789',
            'invitation_expires_at' => Carbon::now()->subHour(),
            'must_set_password' => true,
        ]);

        $response = $this->get(route('invitation.accept', ['token' => 'expired-token-789']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error');
        $this->assertGuest();
    }

    public function test_admin_can_send_magic_link_to_existing_user_and_creates_password(): void
    {
        Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $existingUser = User::factory()->create([
            'role' => 'editor',
            'must_set_password' => false,
        ]);

        $response = $this->actingAs($admin)->post(route('users.resend-invitation', $existingUser));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $existingUser->refresh();
        $this->assertNotNull($existingUser->invitation_token);
        $this->assertTrue($existingUser->must_set_password);

        Mail::assertSent(UserInvitationMail::class);

        // User accepts magic link and is DIRECTLY taken to password setup screen
        $acceptResponse = $this->get(route('invitation.accept', ['token' => $existingUser->invitation_token]));
        $acceptResponse->assertRedirect(route('password.setup'));
        $this->assertAuthenticatedAs($existingUser);

        // User sets initial password
        $setPassResponse = $this->post(route('password.setup.store'), [
            'password' => 'NewInitialPassword2026!',
            'password_confirmation' => 'NewInitialPassword2026!',
        ]);

        $setPassResponse->assertRedirect(route('documents.index'));
        $this->assertFalse($existingUser->fresh()->must_set_password);
        $this->assertTrue(Hash::check('NewInitialPassword2026!', $existingUser->fresh()->password));
    }
}
