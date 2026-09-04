<?php

namespace App\Http\Controllers;

use App\Models\ChecklistTemplate;
use App\Models\Document;
use App\Models\DocumentType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentTypeController extends Controller
{
    /**
     * Display a listing of document types.
     */
    public function index(): View
    {
        $documentTypes = DocumentType::ordered()->get();

        // Calculate document counts and checklist counts per type
        $docCounts = Document::groupBy('document_type')
            ->selectRaw('document_type, count(*) as total')
            ->pluck('total', 'document_type');

        $checklistCounts = ChecklistTemplate::groupBy('document_type')
            ->selectRaw('document_type, count(*) as total')
            ->pluck('total', 'document_type');

        return view('document_types.index', compact('documentTypes', 'docCounts', 'checklistCounts'));
    }

    /**
     * Show the form for creating a new document type.
     */
    public function create(): View
    {
        return view('document_types.create');
    }

    /**
     * Store a newly created document type.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'required|string|max:50|alpha_dash|unique:document_types,code',
            'name' => 'required|string|max:100',
            'prefix' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'badge_color' => 'required|string|in:indigo,emerald,blue,amber,rose,teal,violet,sky,purple,gray',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? 10;
        $validated['is_system'] = false;

        $docType = DocumentType::create($validated);

        return redirect()->route('document-types.index')
            ->with('success', "Document Type '{$docType->name}' created successfully.");
    }

    /**
     * Show the form for editing the specified document type.
     */
    public function edit(DocumentType $documentType): View
    {
        return view('document_types.edit', compact('documentType'));
    }

    /**
     * Update the specified document type.
     */
    public function update(Request $request, DocumentType $documentType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'prefix' => 'nullable|string|max:100',
            'suffix' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'badge_color' => 'required|string|in:indigo,emerald,blue,amber,rose,teal,violet,sky,purple,gray',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->has('is_active');
        $validated['sort_order'] = $validated['sort_order'] ?? $documentType->sort_order;

        $documentType->update($validated);

        return redirect()->route('document-types.index')
            ->with('success', "Document Type '{$documentType->name}' updated successfully.");
    }

    /**
     * Remove the specified document type.
     */
    public function destroy(DocumentType $documentType): RedirectResponse
    {
        if ($documentType->is_system) {
            return back()->with('error', "System document type '{$documentType->name}' cannot be deleted. You may deactivate it instead.");
        }

        $docsCount = Document::where('document_type', $documentType->code)->count();
        if ($docsCount > 0) {
            return back()->with('error', "Cannot delete '{$documentType->name}' because it is linked to {$docsCount} existing document(s). You may deactivate it instead.");
        }

        $name = $documentType->name;
        $documentType->delete();

        return redirect()->route('document-types.index')
            ->with('success', "Document Type '{$name}' deleted.");
    }
}
