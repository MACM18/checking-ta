<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-6 h-6 me-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    {{ __('Document Types Management') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Configure document classifications, real-time number detection patterns (prefixes/suffixes), and status badge colors.') }}
                </p>
            </div>
            <a href="{{ route('document-types.create') }}" class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                + Add Document Type
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success / Error Messages -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl flex items-center text-emerald-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-xl flex items-center text-red-800 text-sm shadow-xs">
                    <svg class="w-5 h-5 me-2 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Info Guide Card -->
            <div class="bg-indigo-50/70 border border-indigo-100 rounded-2xl p-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <h4 class="text-xs font-bold text-indigo-950 uppercase tracking-wider">Automated Type Detection</h4>
                        <p class="text-xs text-indigo-800/80 mt-0.5">
                            When users enter document numbers in the system (e.g. <span class="font-mono font-bold">E26211</span> or <span class="font-mono font-bold">N10045</span>), the prefix and suffix patterns defined below instantly identify the document type and trigger the appropriate checklists.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Document Types Table -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50 text-gray-500 uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left w-64">Document Type</th>
                                <th scope="col" class="px-6 py-3.5 text-left w-36">Code / Key</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Detection Patterns</th>
                                <th scope="col" class="px-6 py-3.5 text-center w-28">Documents</th>
                                <th scope="col" class="px-6 py-3.5 text-center w-28">Checklists</th>
                                <th scope="col" class="px-6 py-3.5 text-center w-24">Status</th>
                                <th scope="col" class="sticky right-0 z-20 bg-gray-50 px-6 py-3.5 text-right w-28 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach($documentTypes as $type)
                                @php
                                    $colorMap = [
                                        'indigo' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                        'emerald' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                        'blue' => 'bg-blue-50 text-blue-700 border-blue-200',
                                        'amber' => 'bg-amber-50 text-amber-700 border-amber-200',
                                        'rose' => 'bg-rose-50 text-rose-700 border-rose-200',
                                        'teal' => 'bg-teal-50 text-teal-700 border-teal-200',
                                        'violet' => 'bg-violet-50 text-violet-700 border-violet-200',
                                        'sky' => 'bg-sky-50 text-sky-700 border-sky-200',
                                        'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
                                        'gray' => 'bg-gray-50 text-gray-700 border-gray-200',
                                    ];
                                    $badgeClass = $colorMap[$type->badge_color] ?? $colorMap['indigo'];
                                    $count = $docCounts[$type->code] ?? 0;
                                    $chkCount = $checklistCounts[$type->code] ?? 0;
                                @endphp
                                <tr class="hover:bg-slate-50 transition group">
                                    
                                    <!-- Name & Badge Preview -->
                                    <td class="px-6 py-4">
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold border {{ $badgeClass }}">
                                                {{ $type->name }}
                                            </span>
                                            @if($type->is_system)
                                                <span class="text-[10px] text-gray-400 uppercase font-bold" title="Core system document type">System</span>
                                            @endif
                                        </div>
                                        @if($type->description)
                                            <p class="text-[11px] text-gray-500 mt-1">{{ $type->description }}</p>
                                        @endif
                                    </td>

                                    <!-- Code -->
                                    <td class="px-6 py-4 font-mono text-xs text-gray-700 font-semibold">
                                        {{ $type->code }}
                                    </td>

                                    <!-- Detection Patterns -->
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            @if($type->prefix)
                                                <span class="text-[10px] text-gray-500 font-bold uppercase me-0.5">Prefix:</span>
                                                @foreach($type->prefix_list as $pfx)
                                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-800 font-mono font-bold text-[11px] border border-slate-200">
                                                        {{ $pfx }}*
                                                    </span>
                                                @endforeach
                                            @endif

                                            @if($type->suffix)
                                                <span class="text-[10px] text-gray-500 font-bold uppercase ms-2 me-0.5">Suffix:</span>
                                                @foreach($type->suffix_list as $sfx)
                                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-900 font-mono font-bold text-[11px] border border-amber-200">
                                                        *{{ $sfx }}
                                                    </span>
                                                @endforeach
                                            @endif

                                            @if(!$type->prefix && !$type->suffix)
                                                <span class="text-gray-400 italic text-[11px]">Manual selection</span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Documents Count -->
                                    <td class="px-6 py-4 text-center font-mono font-bold text-xs {{ $count > 0 ? 'text-gray-900' : 'text-gray-400' }}">
                                        {{ number_format($count) }}
                                    </td>

                                    <!-- Checklist Count -->
                                    <td class="px-6 py-4 text-center font-mono font-bold text-xs {{ $chkCount > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                                        {{ $chkCount }}
                                    </td>

                                    <!-- Status -->
                                    <td class="px-6 py-4 text-center">
                                        @if($type->is_active)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                Active
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-gray-100 text-gray-500 border border-gray-200">
                                                Inactive
                                            </span>
                                        @endif
                                    </td>

                                    <!-- Actions -->
                                    <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-6 py-4 text-right space-x-2 whitespace-nowrap shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
                                        <a href="{{ route('document-types.edit', $type) }}" class="inline-flex text-indigo-600 hover:text-indigo-800 font-semibold p-1" title="Edit Document Type">
                                            Edit
                                        </a>

                                        @if(!$type->is_system && $count === 0)
                                            <form action="{{ route('document-types.destroy', $type) }}"
                                                  method="POST"
                                                  class="inline-block"
                                                  data-confirm="Are you sure you want to delete the document type '{{ $type->name }}'?"
                                                  data-confirm-title="Delete Document Type"
                                                  data-confirm-button="Delete Type"
                                                  data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-500 hover:text-red-700 font-semibold p-1" title="Delete type">
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </td>

                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
