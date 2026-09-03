<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use App\Services\DocumentLockService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_acquire_lock_and_second_user_is_denied(): void
    {
        $userA = User::factory()->create(['role' => 'editor', 'name' => 'User A']);
        $userB = User::factory()->create(['role' => 'editor', 'name' => 'User B']);

        $document = Document::create([
            'document_number' => 'E26211',
            'document_type' => 'proforma_invoice',
            'company_name' => 'Gulf Apex LLC',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $userA->id,
        ]);

        $lockService = new DocumentLockService;

        // User A acquires lock
        $resA = $lockService->acquireLock($document, $userA);
        $this->assertTrue($resA['acquired']);

        // User B attempts to acquire lock -> should fail
        $resB = $lockService->acquireLock($document, $userB);
        $this->assertFalse($resB['acquired']);
        $this->assertEquals('User A', $resB['locked_by']);

        // Verify document helper
        $this->assertTrue($document->isLockedByOther($userB));
        $this->assertFalse($document->isLockedByOther($userA));

        // User A releases lock
        $lockService->releaseLock($document, $userA);

        // User B now can acquire lock
        $resB2 = $lockService->acquireLock($document, $userB);
        $this->assertTrue($resB2['acquired']);
    }

    public function test_expired_lock_is_automatically_reclaimed(): void
    {
        $userA = User::factory()->create(['role' => 'editor']);
        $userB = User::factory()->create(['role' => 'editor']);

        $document = Document::create([
            'document_number' => 'N10045',
            'document_type' => 'invoice',
            'company_name' => 'Apex Co',
            'country' => 'UAE',
            'document_date' => now(),
            'currency' => 'USD',
            'created_by' => $userA->id,
        ]);

        $lockService = new DocumentLockService;
        $lockService->acquireLock($document, $userA);

        // Simulate 2 minutes passing (past 90s TTL)
        Carbon::setTestNow(Carbon::now()->addSeconds(120));

        // User B acquires lock successfully as previous expired
        $resB = $lockService->acquireLock($document, $userB);
        $this->assertTrue($resB['acquired']);

        Carbon::setTestNow();
    }
}
