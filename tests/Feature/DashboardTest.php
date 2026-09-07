<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\ShipmentOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');

        $rootResponse = $this->get('/');
        $rootResponse->assertRedirect('/login');
    }

    public function test_authenticated_user_accesses_opening_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $document = Document::create([
            'document_number' => 'PI-2026-001',
            'document_type' => Document::TYPE_PROFORMA,
            'company_name' => 'Acme Corporation',
            'country' => 'United States',
            'document_date' => now(),
            'currency' => 'USD',
            'subtotal' => 1500,
            'final_total' => 1500,
            'current_version' => 1,
            'status' => 'draft',
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $shipment = ShipmentOrder::create([
            'order_number' => 'SO-2026-001',
            'company_name' => 'Acme Corporation',
            'country' => 'United States',
            'carrier_method' => 'DHL',
            'tracking_awb_no' => '1234567890',
            'status' => 'active',
            'current_stage' => 2,
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Executive Operations Hub');
        $response->assertSee('Centralized Reports');
        $response->assertSee('PI-2026-001');
        $response->assertSee('SO-2026-001');
    }

    public function test_authenticated_user_visiting_root_is_redirected_to_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');
        $response->assertRedirect('/dashboard');
    }
}
