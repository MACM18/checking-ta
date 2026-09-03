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
                <div class="lg:col-span-8 bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                        <div>
                            <h3 class="font-bold text-base text-gray-800">
                                Checklist for {{ $types[$selectedType] ?? $selectedType }}
                            </h3>
                            <p class="text-xs text-gray-400 mt-0.5">These items appear in the side drawer whenever this document type is detected.</p>
                        </div>
                        <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $templates->count() }} item(s)
                        </span>
                    </div>

                    <div class="space-y-3">
                        @forelse($templates as $index => $item)
                            <div class="p-4 rounded-lg border border-gray-200 hover:border-indigo-200 bg-white transition space-y-3" x-data="{ editing: false }">
                                <div class="flex items-start justify-between" x-show="!editing">
                                    <div class="flex items-start space-x-3">
                                        <span class="font-mono text-xs font-bold text-gray-400 mt-0.5">#{{ $item->sort_order ?: ($index + 1) }}</span>
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
                                            <button type="button" @click="editing = true" class="text-xs text-indigo-600 hover:text-indigo-900 font-semibold">
                                                Edit
                                            </button>
                                            <form action="{{ route('checklists.destroy', $item) }}"
                                                  method="POST"
                                                  class="inline"
                                                  data-confirm="Remove '{{ $item->label }}' from the {{ $item->document_type }} checklist template?"
                                                  data-confirm-title="Delete Checklist Item"
                                                  data-confirm-button="Yes, Remove"
                                                  data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-semibold">
                                                    Delete
                                                </button>
                                            </form>
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
                                No checklist items configured for this document type yet. Add one using the form on the right!
                            </div>
                        @endforelse
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
