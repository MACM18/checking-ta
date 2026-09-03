<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $editor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->editor = User::factory()->create(['role' => User::ROLE_EDITOR]);
    }

    public function test_public_registration_is_disabled(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(404);
    }

    public function test_non_admin_cannot_access_user_management(): void
    {
        $response = $this->actingAs($this->editor)->get(route('users.index'));
        $response->assertStatus(403);
    }

    public function test_admin_can_view_user_list(): void
    {
        $response = $this->actingAs($this->admin)->get(route('users.index'));
        $response->assertStatus(200);
        $response->assertSee('User Management');
        $response->assertSee($this->editor->email);
    }

    public function test_admin_can_create_new_user(): void
    {
        $response = $this->actingAs($this->admin)->post(route('users.store'), [
            'name' => 'Alice Operator',
            'email' => 'alice@example.com',
            'role' => User::ROLE_EDITOR,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Alice Operator',
            'email' => 'alice@example.com',
            'role' => User::ROLE_EDITOR,
        ]);
    }

    public function test_admin_can_update_user_role_and_details(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($this->admin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'role' => User::ROLE_EDITOR,
        ]);

        $response->assertRedirect(route('users.index'));
        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals(User::ROLE_EDITOR, $user->role);
    }

    public function test_prevent_admin_deletion_force(): void
    {
        $anotherAdmin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // Attempt to delete administrator
        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $anotherAdmin));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');

        // Verify the administrator was NOT deleted from database
        $this->assertDatabaseHas('users', ['id' => $anotherAdmin->id]);
    }

    public function test_cannot_delete_own_account(): void
    {
        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $this->admin));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }

    public function test_admin_can_delete_regular_user(): void
    {
        $viewer = User::factory()->create(['role' => User::ROLE_VIEWER]);

        $response = $this->actingAs($this->admin)->delete(route('users.destroy', $viewer));

        $response->assertRedirect(route('users.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $viewer->id]);
    }
}
