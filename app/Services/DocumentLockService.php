<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentLock;
use App\Models\User;
use Carbon\Carbon;

class DocumentLockService
{
    public const LOCK_TTL_SECONDS = 90;

    /**
     * Try to acquire or renew an edit lock for a document.
     */
    public function acquireLock(Document $document, User $user): array
    {
        // Clean expired locks
        DocumentLock::where('expires_at', '<=', Carbon::now())->delete();

        $existingLock = DocumentLock::where('document_id', $document->id)->with('user')->first();

        if ($existingLock) {
            if ($existingLock->user_id === $user->id) {
                // Same user returning or keeping alive
                $existingLock->update([
                    'expires_at' => Carbon::now()->addSeconds(self::LOCK_TTL_SECONDS),
                ]);

                return [
                    'acquired' => true,
                    'lock' => $existingLock,
                    'message' => 'Lock active',
                ];
            }

            // Locked by another user
            return [
                'acquired' => false,
                'locked_by' => $existingLock->user?->name ?? 'Another user',
                'expires_at' => $existingLock->expires_at,
                'seconds_remaining' => max(0, Carbon::now()->diffInSeconds($existingLock->expires_at, false)),
                'message' => "Document is currently locked by {$existingLock->user?->name}.",
            ];
        }

        // Create new lock
        $lock = DocumentLock::create([
            'document_id' => $document->id,
            'user_id' => $user->id,
            'locked_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addSeconds(self::LOCK_TTL_SECONDS),
        ]);

        return [
            'acquired' => true,
            'lock' => $lock,
            'message' => 'Lock acquired successfully',
        ];
    }

    /**
     * Heartbeat to extend the lock.
     */
    public function heartbeat(Document $document, User $user): array
    {
        $lock = DocumentLock::where('document_id', $document->id)
            ->where('user_id', $user->id)
            ->first();

        if ($lock) {
            $lock->update([
                'expires_at' => Carbon::now()->addSeconds(self::LOCK_TTL_SECONDS),
            ]);

            return [
                'success' => true,
                'expires_at' => $lock->expires_at,
            ];
        }

        // Lock was lost, attempt to re-acquire
        return $this->acquireLock($document, $user);
    }

    /**
     * Release lock when user leaves or saves.
     */
    public function releaseLock(Document $document, User $user): bool
    {
        $query = DocumentLock::where('document_id', $document->id);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        return (bool) $query->delete();
    }
}
