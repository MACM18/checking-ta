<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Manage Verification Checklists
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Configure standardized audit and verification steps for each document type to prevent preparation errors.
                </p>
            </div>
            <a href="{{ route('documents.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-sm transition">
                Test in Document Wizard
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash alerts -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Type Tabs -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-2 border-b border-gray-200">
                @foreach($types as $typeKey => $typeLabel)
                    <a href="{{ route('checklists.index', ['type' => $typeKey]) }}"
                       class="px-4 py-2 text-xs font-bold rounded-lg whitespace-nowrap transition {{ $selectedType === $typeKey ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                        {{ $typeLabel }}
                    </a>
                @endforeach
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- Left: Existing Checklist Items (8 Cols) -->
                <div class="lg:col-span-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5"
                     x-data="{
                         selectedIds: [],
                         allIds: {{ json_encode($templates->pluck('id')->all()) }},
                         deletedIds: [],
                         showImportModal: false,
                         get visibleIds() {
                             return this.allIds.filter(id => !this.deletedIds.includes(id));
                         },
                         toggleAll() {
                             const visible = this.visibleIds;
                             if (this.selectedIds.length === visible.length) {
                                 this.selectedIds = [];
                             } else {
                                 this.selectedIds = [...visible];
                             }
                         },
                         async deleteItem(id, label) {
                             const confirmed = await window.systemConfirm({
                                 title: 'Delete Checklist Item',
                                 message: `Remove '${label}' from the checklist template?`,
                                 confirmText: 'Yes, Remove',
                                 type: 'danger'
                             });
                             if (!confirmed) return;

                             // 1. Optimistic UI update (0ms)
                             this.deletedIds.push(id);
                             this.selectedIds = this.selectedIds.filter(i => i !== id);
                             window.showToast?.('Checklist item removed', 'info', 2500);

                             try {
                                 const res = await fetch(`/checklists/${id}`, {
                                     method: 'DELETE',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'Accept': 'application/json',
                                         'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                     }
                                 });
                                 if (!res.ok) throw new Error();
                             } catch (e) {
                                 // Rollback on failure
                                 this.deletedIds = this.deletedIds.filter(i => i !== id);
                                 window.showToast?.('Failed to delete item. Reverted.', 'error');
                             }
                         },
                         async bulkDelete() {
                             if (this.selectedIds.length === 0) return;
                             const count = this.selectedIds.length;
                             const confirmed = await window.systemConfirm({
                                 title: 'Delete Selected Checklist Items',
                                 message: `Are you sure you want to permanently delete ${count} checklist item(s)?`,
                                 confirmText: 'Delete Selected',
                                 type: 'danger'
                             });
                             if (!confirmed) return;

                             const toDelete = [...this.selectedIds];
                             // 1. Optimistic UI update (0ms)
                             this.deletedIds.push(...toDelete);
                             this.selectedIds = [];
                             window.showToast?.(`Deleted ${count} checklist item(s)`, 'info', 2500);

                             try {
                                 const res = await fetch('{{ route('checklists.bulk-destroy') }}', {
                                     method: 'POST',
                                     headers: {
                                         'Content-Type': 'application/json',
                                         'Accept': 'application/json',
                                         'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                     },
                                     body: JSON.stringify({
                                         ids: toDelete,
                                         target_type: '{{ $selectedType }}'
                                     })
                                 });
                                 if (!res.ok) throw new Error();
                             } catch (e) {
                                 // Rollback on failure
                                 this.deletedIds = this.deletedIds.filter(id => !toDelete.includes(id));
                                 this.selectedIds = toDelete;
                                 window.showToast?.('Failed to delete items. Reverted.', 'error');
                             }
                         }
                     }">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-gray-100 pb-4">
                        <div>
                            <h3 class="font-bold text-base text-gray-800">
                                Checklist for {{ $types[$selectedType] ?? $selectedType }}
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">These items appear in the side drawer whenever this document type is detected.</p>
                        </div>

                        <div class="flex items-center space-x-2">
                            @if(Auth::user()->canEdit())
                                <button type="button"
                                        @click="showImportModal = true"
                                        class="inline-flex items-center px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold transition cursor-pointer">
                                    <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                    Import from Another List
                                </button>
                            @endif

                            <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                                <span x-text="visibleIds.length"></span> item(s)
                            </span>
                        </div>
                    </div>

                    <!-- Bulk Action Toolbar -->
                    <div x-show="selectedIds.length > 0" x-cloak class="p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-center justify-between transition">
                        <div class="flex items-center space-x-2 text-xs font-bold text-amber-900">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span x-text="`${selectedIds.length} item(s) selected`"></span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <button type="button" @click="selectedIds = []" class="text-xs text-gray-600 hover:text-gray-900 px-2 py-1 cursor-pointer">
                                Deselect All
                            </button>
                            <button type="button"
                                    @click="bulkDelete()"
                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-md shadow-xs transition cursor-pointer">
                                <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                <span>Delete Selected (<span x-text="selectedIds.length"></span>)</span>
                            </button>
                        </div>
                    </div>

                    @if($templates->count() > 0 && Auth::user()->canEdit())
                        <div class="flex items-center justify-between px-2 text-xs text-gray-500 border-b border-gray-100 pb-2">
                            <label class="flex items-center space-x-2 cursor-pointer select-none">
                                <input type="checkbox"
                                       @change="toggleAll()"
                                       :checked="selectedIds.length === visibleIds.length && visibleIds.length > 0"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                <span class="font-semibold text-gray-700">Select All Items</span>
                            </label>
                            <span class="text-[11px] text-gray-400">Select multiple items to delete together</span>
                        </div>
                    @endif

                    <div class="space-y-3">
                        @forelse($templates as $index => $item)
                            <div class="p-4 rounded-lg border border-gray-200 hover:border-indigo-200 bg-white transition space-y-3"
                                 x-show="!deletedIds.includes({{ $item->id }})"
                                 :class="selectedIds.includes({{ $item->id }}) ? 'border-indigo-300 bg-indigo-50/20' : ''"
                                 x-data="{ editing: false }">
                                <div class="flex items-start justify-between" x-show="!editing">
                                    <div class="flex items-start space-x-3">
                                        @if(Auth::user()->canEdit())
                                            <input type="checkbox"
                                                   :value="{{ $item->id }}"
                                                   x-model.number="selectedIds"
                                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4 mt-1 cursor-pointer">
                                        @endif
                                        <span class="font-mono text-xs font-bold text-gray-400 mt-1">#{{ $item->sort_order ?: ($index + 1) }}</span>
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ $item->item_text }}</p>
                                            @if($item->hint)
                                                <p class="text-xs text-gray-500 mt-0.5">{{ $item->hint }}</p>
                                            @endif
                                            <div class="flex items-center space-x-2 mt-2">
                                                @if($item->is_required)
                                                    <span class="text-[10px] uppercase font-bold text-rose-700 bg-rose-50 px-1.5 py-0.5 rounded border border-rose-200">Required</span>
                                                @else
                                                    <span class="text-[10px] text-gray-400">Optional</span>
                                                @endif
                                                <span class="text-[10px] {{ $item->is_active ? 'text-emerald-600' : 'text-gray-400' }}">
                                                    &bull; {{ $item->is_active ? 'Active' : 'Disabled' }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    @if(Auth::user()->canEdit())
                                        <div class="flex items-center space-x-2">
                                            <button type="button" @click="editing = true" class="text-xs text-indigo-600 hover:text-indigo-900 font-semibold cursor-pointer">
                                                Edit
                                            </button>
                                            <button type="button"
                                                    @click="deleteItem({{ $item->id }}, '{{ addslashes($item->item_text) }}')"
                                                    class="text-xs text-red-500 hover:text-red-700 font-semibold cursor-pointer">
                                                Delete
                                            </button>
                                        </div>
                                    @endif
                                </div>

                                <!-- Inline Edit Form -->
                                <form method="POST" action="{{ route('checklists.update', $item) }}" x-show="editing" class="space-y-3 pt-2 border-t border-gray-100">
                                    @csrf
                                    @method('PUT')
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Checklist Text</label>
                                        <input type="text" name="item_text" value="{{ $item->item_text }}" required class="w-full text-xs rounded border-gray-300">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Hint / Guidance</label>
                                        <input type="text" name="hint" value="{{ $item->hint }}" class="w-full text-xs rounded border-gray-300">
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <label class="flex items-center space-x-1.5 text-xs text-gray-700">
                                            <input type="checkbox" name="is_required" value="1" {{ $item->is_required ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                            <span>Mandatory Item</span>
                                        </label>
                                        <label class="flex items-center space-x-1.5 text-xs text-gray-700">
                                            <input type="checkbox" name="is_active" value="1" {{ $item->is_active ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600">
                                            <span>Active</span>
                                        </label>
                                    </div>
                                    <div class="flex justify-end space-x-2">
                                        <button type="button" @click="editing = false" class="px-2.5 py-1 text-xs text-gray-500 hover:text-gray-700">Cancel</button>
                                        <button type="submit" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-semibold hover:bg-indigo-700">Update</button>
                                    </div>
                                </form>
                            </div>
                        @empty
                            <div class="text-center py-10 text-gray-400 text-xs">
                                No checklist items configured for this document type yet. Add one using the form on the right or import from another list!
                            </div>
                        @endforelse
                    </div>

                    <!-- Import Modal -->
                    <div x-show="showImportModal"
                         x-cloak
                         class="fixed inset-0 z-50 overflow-y-auto"
                         aria-labelledby="modal-title"
                         role="dialog"
                         aria-modal="true">
                        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                                 @click="showImportModal = false"></div>

                            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                            <div class="relative inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full p-6 space-y-4">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                    <div class="flex items-center space-x-2">
                                        <div class="w-8 h-8 rounded-lg bg-indigo-100 text-indigo-700 flex items-center justify-center font-bold">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        </div>
                                        <div>
                                            <h3 class="font-bold text-sm text-gray-900">Import Checklist Items</h3>
                                            <p class="text-xs text-gray-500">Copy verification rules from another document type.</p>
                                        </div>
                                    </div>
                                    <button type="button" @click="showImportModal = false" class="text-gray-400 hover:text-gray-600">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>

                                <form action="{{ route('checklists.import') }}" method="POST" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="target_type" value="{{ $selectedType }}">

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                            Source Document Checklist <span class="text-red-500">*</span>
                                        </label>
                                        <select name="source_type" required class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">-- Choose source checklist --</option>
                                            @foreach($types as $sKey => $sLabel)
                                                @if($sKey !== $selectedType)
                                                    <option value="{{ $sKey }}">{{ $sLabel }}</option>
                                                @endif
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                            Destination Checklist
                                        </label>
                                        <div class="px-3 py-2 bg-gray-50 rounded-lg text-xs font-semibold text-gray-700 border border-gray-200">
                                            {{ $types[$selectedType] ?? $selectedType }}
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Import Mode
                                        </label>
                                        <div class="space-y-2">
                                            <label class="flex items-start space-x-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="radio" name="mode" value="append" checked class="mt-0.5 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                                                <div>
                                                    <span class="font-bold">Append to existing checklist</span>
                                                    <p class="text-[11px] text-gray-500">Keeps current items and appends items from the source checklist.</p>
                                                </div>
                                            </label>
                                            <label class="flex items-start space-x-2 text-xs text-gray-700 cursor-pointer">
                                                <input type="radio" name="mode" value="replace" class="mt-0.5 text-red-600 border-gray-300 focus:ring-red-500">
                                                <div>
                                                    <span class="font-bold text-red-700">Replace existing checklist</span>
                                                    <p class="text-[11px] text-gray-500">Deletes current items for this type and replaces with the source checklist.</p>
                                                </div>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-end space-x-2 pt-3 border-t border-gray-100">
                                        <button type="button" @click="showImportModal = false" class="px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-100 rounded-lg transition">
                                            Cancel
                                        </button>
                                        <button type="submit"
                                                data-confirm="Are you sure you want to import checklist items into {{ $types[$selectedType] ?? $selectedType }}?"
                                                data-confirm-title="Confirm Checklist Import"
                                                data-confirm-btn="Import Items"
                                                data-confirm-type="primary"
                                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition">
                                            Import Items
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Add Item Card (4 Cols) -->
                <div class="lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                    <h4 class="font-bold text-sm text-gray-800 border-b border-gray-100 pb-2">
                        Add New Checklist Item
                    </h4>

                    @if(Auth::user()->canEdit())
                        <form method="POST" action="{{ route('checklists.store') }}" class="space-y-4">
                            @csrf
                            <input type="hidden" name="document_type" value="{{ $selectedType }}">

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Item Description <span class="text-red-500">*</span>
                                </label>
                                <textarea name="item_text" rows="2" required placeholder="e.g. Verify customer registered TRN & VAT number" class="w-full text-xs rounded-lg border-gray-300"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                    Helper Hint / Instruction
                                </label>
                                <input type="text" name="hint" placeholder="e.g. Must match Federal Tax Authority registry" class="w-full text-xs rounded-lg border-gray-300">
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Order
                                    </label>
                                    <input type="number" name="sort_order" value="{{ $templates->count() + 1 }}" min="1" class="w-full text-xs rounded-lg border-gray-300">
                                </div>
                                <div class="flex items-center pt-5">
                                    <label class="flex items-center space-x-2 text-xs font-semibold text-gray-700 cursor-pointer">
                                        <input type="checkbox" name="is_required" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        <span>Mandatory</span>
                                    </label>
                                </div>
                            </div>

                            <button type="submit" class="w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-sm transition">
                                Add to {{ $types[$selectedType] ?? $selectedType }}
                            </button>
                        </form>
                    @else
                        <div class="p-3 bg-gray-50 text-gray-500 rounded-lg text-xs">
                            You have view-only permissions. Contact an Administrator to modify checklist templates.
                        </div>
                    @endif
                </div>

            </div>

        </div>
    </div>
</x-app-layout>
