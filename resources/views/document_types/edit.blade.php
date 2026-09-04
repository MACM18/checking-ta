<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-3">
                <a href="{{ route('document-types.index') }}" class="text-gray-400 hover:text-gray-600 transition" title="Back to Document Types">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                        {{ __('Edit Document Type') }}:
                        <span class="ms-2 font-mono text-indigo-600 text-lg">{{ $documentType->name }}</span>
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ __('Update classification details and detection patterns.') }}
                    </p>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 sm:p-8">
                
                <form action="{{ route('document-types.update', $documentType) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <!-- 1. Name & Code (Code is readonly) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Document Type Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="name"
                                   value="{{ old('name', $documentType->name) }}"
                                   required
                                   class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            @error('name') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                System Slug / Code
                            </label>
                            <input type="text"
                                   value="{{ $documentType->code }}"
                                   disabled
                                   class="w-full text-xs font-mono bg-gray-50 text-gray-500 rounded-xl border-gray-200 cursor-not-allowed">
                            <span class="text-[10px] text-gray-400 mt-1 block">Unique identifier used in database and checklists.</span>
                        </div>
                    </div>

                    <!-- 2. Detection Patterns (Prefixes & Suffixes) -->
                    <div class="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-4">
                        <div class="flex items-center space-x-2">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-wider">Real-time Detection Rules</h4>
                        </div>
                        <p class="text-[11px] text-gray-500">
                            When typing a document number in document creation, matching these prefixes or suffixes will automatically identify this document type.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Number Prefixes (Comma-separated)
                                </label>
                                <input type="text"
                                       name="prefix"
                                       value="{{ old('prefix', $documentType->prefix) }}"
                                       placeholder="e.g. E, EL"
                                       class="w-full text-xs font-mono uppercase rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="text-[10px] text-gray-400 block mt-1">E.g. entering <strong class="text-gray-700">E,EL</strong> matches numbers starting with E or EL.</span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1">
                                    Number Suffixes (Comma-separated)
                                </label>
                                <input type="text"
                                       name="suffix"
                                       value="{{ old('suffix', $documentType->suffix) }}"
                                       placeholder="e.g. CR, C, D, R"
                                       class="w-full text-xs font-mono uppercase rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <span class="text-[10px] text-gray-400 block mt-1">E.g. entering <strong class="text-gray-700">CR</strong> matches numbers ending in CR.</span>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Badge Color & Sort Order -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Badge Color Theme <span class="text-red-500">*</span>
                            </label>
                            <select name="badge_color" class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="indigo" {{ old('badge_color', $documentType->badge_color) === 'indigo' ? 'selected' : '' }}>Indigo / Purple</option>
                                <option value="emerald" {{ old('badge_color', $documentType->badge_color) === 'emerald' ? 'selected' : '' }}>Emerald / Green</option>
                                <option value="blue" {{ old('badge_color', $documentType->badge_color) === 'blue' ? 'selected' : '' }}>Blue / Oceanic</option>
                                <option value="amber" {{ old('badge_color', $documentType->badge_color) === 'amber' ? 'selected' : '' }}>Amber / Yellow</option>
                                <option value="rose" {{ old('badge_color', $documentType->badge_color) === 'rose' ? 'selected' : '' }}>Rose / Red</option>
                                <option value="teal" {{ old('badge_color', $documentType->badge_color) === 'teal' ? 'selected' : '' }}>Teal / Cyan</option>
                                <option value="violet" {{ old('badge_color', $documentType->badge_color) === 'violet' ? 'selected' : '' }}>Violet / Deep Purple</option>
                                <option value="sky" {{ old('badge_color', $documentType->badge_color) === 'sky' ? 'selected' : '' }}>Sky / Light Blue</option>
                                <option value="gray" {{ old('badge_color', $documentType->badge_color) === 'gray' ? 'selected' : '' }}>Gray / Neutral</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                Display Sort Order
                            </label>
                            <input type="number"
                                   name="sort_order"
                                   value="{{ old('sort_order', $documentType->sort_order) }}"
                                   min="0"
                                   class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- 4. Description -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                            Description / Workflow Purpose
                        </label>
                        <textarea name="description"
                                  rows="3"
                                  class="w-full text-xs rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $documentType->description) }}</textarea>
                    </div>

                    <!-- 5. Active Status -->
                    <div class="flex items-center space-x-2 pt-2">
                        <input type="checkbox"
                               name="is_active"
                               id="is_active"
                               value="1"
                               {{ old('is_active', $documentType->is_active) ? 'checked' : '' }}
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="is_active" class="text-xs font-bold text-gray-800">
                            Active (Available in dropdowns and real-time detection)
                        </label>
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100">
                        <a href="{{ route('document-types.index') }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                            Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            Update Document Type
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>
</x-app-layout>
