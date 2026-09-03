<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentLock;
use App\Services\DocumentLockService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DocumentLockController extends Controller
{
    protected DocumentLockService $lockService;

    public function __construct(DocumentLockService $lockService)
    {
        $this->lockService = $lockService;
    }

    /**
     * Heartbeat endpoint called by frontend every 30-45 seconds.
     */
    public function heartbeat(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $result = $this->lockService->heartbeat($document, $user);

        return response()->json($result);
    }

    /**
     * Release lock when leaving edit view or submitting.
     */
    public function release(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $released = $this->lockService->releaseLock($document, $user);

        return response()->json(['released' => $released]);
    }

    /**
     * Check if a document is currently locked.
     */
    public function status(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();
        $activeLock = $document->getActiveLock();

        if (! $activeLock) {
            return response()->json([
                'is_locked' => false,
                'can_edit' => true,
            ]);
        }

        $isMe = $activeLock->user_id === $user->id;

        return response()->json([
            'is_locked' => true,
            'is_locked_by_me' => $isMe,
            'can_edit' => $isMe,
            'locked_by' => $activeLock->user?->name ?? 'Another user',
            'seconds_remaining' => max(0, Carbon::now()->diffInSeconds($activeLock->expires_at, false)),
        ]);
    }

    /**
     * Get all active locks for the shared workspace table.
     */
    public function allLocks(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        // Clean expired
        DocumentLock::where('expires_at', '<=', Carbon::now())->delete();

        $locks = DocumentLock::with('user:id,name')
            ->where('expires_at', '>', Carbon::now())
            ->get();

        $result = [];
        foreach ($locks as $lock) {
            $result[$lock->document_id] = [
                'is_locked' => true,
                'is_locked_by_me' => $lock->user_id === $userId,
                'locked_by' => $lock->user?->name ?? 'Another user',
                'seconds_remaining' => max(0, Carbon::now()->diffInSeconds($lock->expires_at, false)),
            ];
        }

        return response()->json($result);
    }
}
