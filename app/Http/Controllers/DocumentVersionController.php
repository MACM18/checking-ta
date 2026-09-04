<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\DocumentLockService;
use App\Services\DocumentVersionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentVersionController extends Controller
{
    protected DocumentVersionService $versionService;

    protected DocumentLockService $lockService;

    public function __construct(DocumentVersionService $versionService, DocumentLockService $lockService)
    {
        $this->versionService = $versionService;
        $this->lockService = $lockService;
    }

    /**
     * Display a specific past version snapshot.
     */
    public function show(Document $document, int $versionNumber): View
    {
        $version = DocumentVersion::where('document_id', $document->id)
            ->where('version_number', $versionNumber)
            ->with('creator')
            ->firstOrFail();

        return view('documents.version-show', compact('document', 'version'));
    }

    /**
     * Restore a document to a previous version.
     */
    public function restore(Request $request, Document $document, int $versionNumber): RedirectResponse
    {
        $user = $request->user();

        // Check if user has edit rights
        if (! $user->canEdit()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'You do not have permission to restore versions.');
        }

        // Check if locked by someone else
        if ($document->isLockedByOther($user)) {
            $lock = $document->getActiveLock();

            return redirect()->route('documents.show', $document)
                ->with('error', "Cannot restore while document is being edited by {$lock->user?->name}.");
        }

        $restored = $this->versionService->restoreVersion($document, $versionNumber, $user);

        return redirect()->route('documents.show', $restored)
            ->with('success', "Document restored successfully to Version {$versionNumber} (now Version {$restored->current_version}).");
    }

    /**
     * Explicitly create a new version snapshot of the document.
     */
    public function store(Request $request, Document $document): RedirectResponse
    {
        $user = $request->user();

        if (! $user->canEdit()) {
            return redirect()->route('documents.show', $document)
                ->with('error', 'You do not have permission to create version snapshots.');
        }

        if ($document->isLockedByOther($user)) {
            $lock = $document->getActiveLock();

            return redirect()->route('documents.show', $document)
                ->with('error', "Cannot create a version snapshot while document is being edited by {$lock->user?->name}.");
        }

        $validated = $request->validate([
            'change_summary' => ['required', 'string', 'max:255'],
        ]);

        $newVersionNumber = $document->current_version + 1;
        $document->update([
            'current_version' => $newVersionNumber,
            'updated_by' => $user->id,
        ]);

        $this->versionService->createSnapshot($document, $user, $validated['change_summary']);

        return redirect()->route('documents.edit', $document)
            ->with('success', "Version {$newVersionNumber} snapshot created. You can now edit the document.");
    }
}
