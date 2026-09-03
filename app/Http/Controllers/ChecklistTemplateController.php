<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ChecklistTemplateController extends Controller
{
    protected function authorizeChecklist(): void
    {
        if (! Auth::user()?->canManageChecklists()) {
            abort(403, 'You do not have permission to manage checklist templates.');
        }
    }

    /**
     * Display a listing of the checklist templates.
     */
    public function index(Request $request): View
    {
        $this->authorizeChecklist();

        $selectedType = $request->query('type', Document::TYPE_PROFORMA);

        $templates = ChecklistTemplate::where('document_type', $selectedType)
            ->orderBy('sort_order')
            ->get();

        $types = Document::documentTypes();

        return view('checklists.index', compact('templates', 'types', 'selectedType'));
    }

    /**
     * Store a newly created checklist template in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeChecklist();

        $validated = $request->validate([
            'document_type' => 'required|string',
            'item_text' => 'required|string|max:255',
            'hint' => 'nullable|string|max:255',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['is_required'] = $request->has('is_required');
        $validated['is_active'] = true;

        ChecklistTemplate::create($validated);

        return redirect()->route('checklists.index', ['type' => $validated['document_type']])
            ->with('success', 'Checklist item added successfully.');
    }

    /**
     * Update the specified checklist template in storage.
     */
    public function update(Request $request, ChecklistTemplate $checklist): RedirectResponse
    {
        $this->authorizeChecklist();

        $validated = $request->validate([
            'item_text' => 'required|string|max:255',
            'hint' => 'nullable|string|max:255',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $validated['is_required'] = $request->boolean('is_required');
        $validated['is_active'] = $request->boolean('is_active', true);

        $checklist->update($validated);

        return redirect()->route('checklists.index', ['type' => $checklist->document_type])
            ->with('success', 'Checklist item updated successfully.');
    }

    /**
     * Remove the specified checklist template from storage.
     */
    public function destroy(ChecklistTemplate $checklist): RedirectResponse
    {
        $this->authorizeChecklist();

        $type = $checklist->document_type;
        $checklist->delete();

        return redirect()->route('checklists.index', ['type' => $type])
            ->with('success', 'Checklist item removed.');
    }

    /**
     * API: Get active checklist items for a specific document type.
     */
    public function getChecklistApi(string $documentType): JsonResponse
    {
        $items = ChecklistTemplate::where('document_type', $documentType)
            ->active()
            ->get(['id', 'document_type', 'item_text', 'hint', 'is_required', 'sort_order']);

        return response()->json([
            'document_type' => $documentType,
            'items' => $items,
        ]);
    }
}
