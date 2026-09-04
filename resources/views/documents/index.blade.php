<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                    Shared Documents Workspace
                </h2>
                <p class="text-sm text-gray-500 mt-0.5">
                    Manage company invoices, proformas, packing lists, delivery notes, and reserves with real-time lock protection.
                </p>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{{ route('reports.freight-weights', ['format' => 'excel']) }}" class="inline-flex items-center px-3 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Export Freight & Weights Log to Excel">
                    <svg class="w-3.5 h-3.5 me-1.5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                    Weights & Freight (XLSX)
                </a>
                <a href="{{ route('reports.freight-weights', ['format' => 'pdf']) }}" class="inline-flex items-center px-3 py-2 bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 rounded-xl text-xs font-bold transition shadow-2xs" title="Export Freight & Weights Log to PDF">
                    <svg class="w-3.5 h-3.5 me-1.5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                    PDF
                </a>
                @if(Auth::user()->canEdit())
                    <a href="{{ route('documents.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-xl font-bold text-xs text-white tracking-wide hover:bg-indigo-700 active:bg-indigo-800 shadow-sm transition">
                        <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        New Document
                    </a>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Alerts -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-md flex items-center text-red-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-red-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <!-- Document Type Filter Pills -->
            <div class="flex items-center space-x-2 overflow-x-auto pb-2 scrollbar-thin">
                <a href="{{ route('documents.index') }}" class="px-3.5 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition {{ !request('type') ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                    All Types ({{ $counts['all'] }})
                </a>
                @foreach($types as $typeKey => $typeLabel)
                    <a href="{{ route('documents.index', array_merge(request()->query(), ['type' => $typeKey])) }}" class="px-3 py-1.5 rounded-full text-xs font-medium whitespace-nowrap transition {{ request('type') === $typeKey ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white text-gray-600 hover:bg-gray-100 border border-gray-200' }}">
                        {{ $typeLabel }}
                        <span class="ms-1 px-1.5 py-0.2 rounded-full text-[10px] {{ request('type') === $typeKey ? 'bg-indigo-800 text-white' : 'bg-gray-100 text-gray-600' }}">
                            {{ $counts[$typeKey] ?? 0 }}
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- Search & Secondary Filter Bar -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
                <form method="GET" action="{{ route('documents.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    @if(request('type'))
                        <input type="hidden" name="type" value="{{ request('type') }}">
                    @endif

                    <div class="md:col-span-2 relative">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search Document No, Company name, Country..." class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 pl-10">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                    </div>

                    <div>
                        <select name="currency" class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Currencies</option>
                            <option value="USD" {{ request('currency') === 'USD' ? 'selected' : '' }}>USD ($)</option>
                            <option value="AED" {{ request('currency') === 'AED' ? 'selected' : '' }}>AED (AED)</option>
                        </select>
                    </div>

                    <div class="flex items-center space-x-2">
                        <button type="submit" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Filter
                        </button>
                        @if(request()->hasAny(['search', 'type', 'currency', 'status']))
                            <a href="{{ route('documents.index') }}" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 rounded-lg text-xs font-semibold transition" title="Clear Filters">
                                Reset
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <!-- Documents Table with Live Lock Sync -->
            @php
                $initialLocks = [];
                foreach ($documents as $d) {
                    $l = $d->getActiveLock();
                    if ($l) {
                        $initialLocks[$d->id] = [
                            'is_locked' => true,
                            'is_locked_by_me' => $l->user_id === Auth::id(),
                            'locked_by' => $l->user?->name ?? 'Another user',
                        ];
                    }
                }
            @endphp

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden"
                 x-data="workspaceLiveLocks(@js($initialLocks))">
                <div class="px-6 py-2.5 bg-slate-50 border-b border-gray-100 flex items-center justify-between text-xs text-gray-500">
                    <span class="font-medium text-gray-600">Company Documents</span>
                    <div class="flex items-center space-x-2">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-[11px] text-gray-500">Live Multi-User Lock Sync Active</span>
                        <span class="text-[10px] text-gray-400 font-mono" x-text="`(${lastSync})`"></span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider font-semibold">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left">Document No & Type</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Customer / Company</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Date</th>
                                <th scope="col" class="px-6 py-3.5 text-left">Version</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Total Amount</th>
                                <th scope="col" class="px-6 py-3.5 text-center">Live Status / Lock</th>
                                <th scope="col" class="px-6 py-3.5 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse($documents as $doc)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <!-- Document No & Type -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <a href="{{ route('documents.show', $doc) }}" class="font-mono font-bold text-indigo-600 hover:text-indigo-900 text-base">
                                                {{ $doc->document_number }}
                                            </a>
                                        </div>
                                        <div class="mt-1">
                                            @php
                                                $badgeClasses = match($doc->document_type) {
                                                    'proforma_invoice' => 'bg-blue-50 text-blue-700 border-blue-200',
                                                    'invoice' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                                    'packing_list' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                    'reserve' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                    'credit_note' => 'bg-rose-50 text-rose-700 border-rose-200',
                                                    'delivery_note' => 'bg-teal-50 text-teal-700 border-teal-200',
                                                    'clearing_invoice' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                                    'cash_receipt' => 'bg-cyan-50 text-cyan-700 border-cyan-200',
                                                    default => 'bg-gray-50 text-gray-700 border-gray-200'
                                                };
                                            @endphp
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium border {{ $badgeClasses }}">
                                                {{ $doc->formatted_type }}
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Company & Country -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="font-medium text-gray-900">{{ $doc->company_name }}</div>
                                        <div class="text-xs text-gray-500 flex items-center mt-0.5">
                                            <svg class="w-3.5 h-3.5 me-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ $doc->country }}
                                        </div>
                                    </td>

                                    <!-- Date -->
                                    <td class="px-6 py-4 whitespace-nowrap text-gray-600 text-xs">
                                        {{ $doc->document_date ? $doc->document_date->format('M d, Y') : '-' }}
                                    </td>

                                    <!-- Version -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-800 border border-gray-200">
                                            v{{ $doc->current_version }}
                                        </span>
                                    </td>

                                    <!-- Total Amount -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="font-mono font-bold text-gray-900">
                                            {{ $doc->currency }} {{ number_format($doc->final_total, 2) }}
                                        </div>
                                        @if($doc->total_gross_weight)
                                            <div class="text-[11px] text-gray-400 font-mono">
                                                GW: {{ number_format($doc->total_gross_weight, 2) }} kg
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Real-time Reactive Lock & Status Indicator -->
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <template x-if="getLock({{ $doc->id }}) && getLock({{ $doc->id }}).is_locked_by_me">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
                                                Editing by You
                                            </span>
                                        </template>

                                        <template x-if="getLock({{ $doc->id }}) && !getLock({{ $doc->id }}).is_locked_by_me">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 me-1.5 animate-ping"></span>
                                                Locked: <span class="ms-1" x-text="getLock({{ $doc->id }}).locked_by"></span>
                                            </span>
                                        </template>

                                        <template x-if="!getLock({{ $doc->id }})">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600">
                                                Available
                                            </span>
                                        </template>
                                    </td>

                                    <!-- Actions -->
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-xs font-medium space-x-2">
                                        <a href="{{ route('documents.show', $doc) }}" class="inline-flex items-center px-2.5 py-1.5 rounded bg-gray-100 hover:bg-gray-200 text-gray-700 transition">
                                            View
                                        </a>

                                        @if(Auth::user()->canEdit())
                                            <!-- If locked by someone else -->
                                            <template x-if="getLock({{ $doc->id }}) && !getLock({{ $doc->id }}).is_locked_by_me">
                                                <a href="{{ route('documents.edit', $doc) }}" class="inline-flex items-center px-2.5 py-1.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-800 transition" title="Currently being edited. Click to open in View-Only mode">
                                                    <svg class="w-3.5 h-3.5 me-1 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                    Locked
                                                </a>
                                            </template>

                                            <!-- If available or locked by me -->
                                            <template x-if="!getLock({{ $doc->id }}) || getLock({{ $doc->id }}).is_locked_by_me">
                                                <a href="{{ route('documents.edit', $doc) }}" class="inline-flex items-center px-2.5 py-1.5 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-700 transition">
                                                    Edit
                                                </a>
                                            </template>
                                        @endif

                                        @if(Auth::user()->isAdmin() || $doc->created_by === Auth::id())
                                            <form action="{{ route('documents.destroy', $doc) }}"
                                                  method="POST"
                                                  class="inline"
                                                  data-confirm="Are you sure you want to delete document {{ $doc->document_number }}? All associated items, packages, and versions will be permanently removed."
                                                  data-confirm-title="Delete Document {{ $doc->document_number }}"
                                                  data-confirm-button="Yes, Delete Document"
                                                  data-confirm-type="danger">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-2 py-1.5 rounded hover:bg-red-50 text-red-600 transition" title="Delete document">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <p class="mt-2 text-sm font-semibold text-gray-700">No documents found</p>
                                        <p class="text-xs text-gray-400 mt-1">Try adjusting your search criteria or create a new document.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($documents->hasPages())
                    <div class="px-6 py-4 border-t border-gray-100 bg-gray-50">
                        {{ $documents->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script>
        function workspaceLiveLocks(initialLocks) {
            return {
                locks: initialLocks || {},
                lastSync: 'just now',

                init() {
                    setInterval(() => {
                        this.pollLocks();
                    }, 4000);
                },

                async pollLocks() {
                    try {
                        const res = await fetch('{{ route('documents.lock.all') }}');
                        if (res.ok) {
                            this.locks = await res.json();
                            const now = new Date();
                            this.lastSync = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        }
                    } catch (e) {
                        // ignore network interruptions
                    }
                },

                getLock(docId) {
                    return this.locks[docId] || null;
                }
            };
        }
    </script>
</x-app-layout>
