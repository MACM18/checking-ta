<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('documents.index') }}" class="p-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-600 transition" title="Back">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-2">
                        <span class="font-mono text-2xl font-black text-gray-900">{{ $document->document_number }}</span>
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                            {{ $document->formatted_type }}
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 font-mono">
                            v{{ $document->current_version }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Created by {{ $document->creator?->name ?? 'System' }} on {{ $document->created_at->format('M d, Y H:i') }}
                    </p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <button onclick="window.print()" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition">
                    <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Print Document
                </button>

                @if(Auth::user()->canEdit())
                    @if($document->isLockedByOther(Auth::user()))
                        <div class="inline-flex items-center px-4 py-2 bg-amber-50 border border-amber-300 rounded-lg text-xs font-bold text-amber-800 shadow-sm" title="Locked by {{ $activeLock->user?->name }}">
                            <svg class="w-4 h-4 me-1.5 text-amber-600 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Locked by {{ $activeLock->user?->name }} (View-Only)
                        </div>
                    @else
                        <button type="button" @click="$dispatch('open-create-version')" class="inline-flex items-center px-3 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-bold text-xs rounded-lg border border-indigo-200 shadow-2xs transition">
                            <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            New Version
                        </button>
                        <a href="{{ route('documents.edit', $document) }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 shadow-sm transition">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Edit Document
                        </a>
                    @endif
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Alerts & Real-time Live Lock Watcher -->
            <div x-data="documentLockWatcher({{ $document->id }}, {{ $document->isLockedByOther(Auth::user()) ? 'true' : 'false' }}, '{{ $activeLock?->user?->name }}')">

                <!-- Dynamic Live Unlocked Banner -->
                <div x-show="justUnlocked" x-transition class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center justify-between text-emerald-900 text-sm shadow-sm mb-4">
                    <div class="flex items-center space-x-3">
                        <span class="w-3 h-3 rounded-full bg-emerald-500 animate-ping"></span>
                        <div>
                            <span class="font-bold">Document Just Unlocked!</span>
                            <span class="text-xs text-emerald-700 block">The previous editor has saved and released the lock. You can now edit this document.</span>
                        </div>
                    </div>
                    @if(Auth::user()->canEdit())
                        <a href="{{ route('documents.edit', $document) }}" class="inline-flex items-center px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
                            <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            Start Editing Now
                        </a>
                    @endif
                </div>

                <!-- Still Locked Banner -->
                <div x-show="isCurrentlyLocked && !justUnlocked" class="p-4 bg-amber-50 border-l-4 border-amber-500 rounded-r-md flex items-start justify-between text-amber-900 text-sm shadow-sm mb-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 me-2 text-amber-500 flex-shrink-0 mt-0.5 animate-pulse" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                        <div>
                            <span class="font-bold">Concurrency Protection:</span> Document currently locked by <span class="font-semibold" x-text="lockedByUser"></span>. Opened in View-Only mode.
                            <p class="text-xs text-amber-700 mt-1 flex items-center">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 me-1.5 animate-ping"></span>
                                Live polling active &bull; This banner will notify you immediately once released.
                            </p>
                        </div>
                    </div>
                </div>

                @if(session('success'))
                    <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center text-emerald-800 text-sm shadow-sm mb-4">
                        <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- Document Main View (8 Cols) -->
                <div class="lg:col-span-8 space-y-6">

                    <!-- Header / Customer Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Bill To / Company</h4>
                                <div class="text-lg font-bold text-gray-900 mt-1">{{ $document->company_name }}</div>
                                <div class="text-sm font-semibold text-indigo-600 mt-0.5 flex items-center">
                                    <svg class="w-4 h-4 me-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    {{ $document->country }}
                                </div>
                                @if($document->address)
                                    <div class="text-xs text-gray-600 mt-2 whitespace-pre-line bg-gray-50 p-2.5 rounded-lg border border-gray-100">
                                        {{ $document->address }}
                                    </div>
                                @endif
                            </div>

                            <div class="space-y-3 bg-slate-50/60 p-4 rounded-xl border border-slate-100">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-bold text-gray-500 uppercase">Document Date</span>
                                    <span class="font-semibold text-gray-900">{{ $document->document_date ? $document->document_date->format('M d, Y') : '-' }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-bold text-gray-500 uppercase">Currency</span>
                                    <span class="px-2 py-0.5 rounded font-mono font-bold bg-indigo-100 text-indigo-800">{{ $document->currency }}</span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-bold text-gray-500 uppercase">Document Status</span>
                                    <span class="capitalize font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        {{ $document->status }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-bold text-gray-500 uppercase">Version</span>
                                    <span class="font-mono font-bold text-gray-800">Version {{ $document->current_version }}</span>
                                </div>
                            </div>
                        </div>

                        @if($document->contact_details)
                            <div class="border-t border-gray-100 pt-3">
                                <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-1">Contact & Attention Details</h5>
                                <p class="text-xs text-gray-700">{{ $document->contact_details }}</p>
                            </div>
                        @endif
                    </div>

                    <!-- Line Items Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                            <h3 class="font-bold text-sm text-gray-800 uppercase tracking-wider">Line Items ({{ $document->items->count() }})</h3>
                            <span class="text-xs text-gray-500 font-mono">Currency: {{ $document->currency }}</span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-xs">
                                <thead class="bg-gray-50/50 text-gray-500 font-bold uppercase tracking-wider">
                                    <tr>
                                        <th class="px-6 py-3 text-left w-12">#</th>
                                        <th class="px-6 py-3 text-left">Item Code</th>
                                        <th class="px-6 py-3 text-left">Description</th>
                                        <th class="px-6 py-3 text-right">Unit Amount</th>
                                        <th class="px-6 py-3 text-right">Unit Price</th>
                                        <th class="px-6 py-3 text-right">Total ({{ $document->currency }})</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($document->items as $idx => $item)
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-6 py-3 text-gray-400 font-mono">{{ $idx + 1 }}</td>
                                            <td class="px-6 py-3 font-mono font-bold text-gray-900">{{ $item->item_code }}</td>
                                            <td class="px-6 py-3 text-gray-700">{{ $item->description ?: '-' }}</td>
                                            <td class="px-6 py-3 text-right font-mono">{{ number_format($item->unit_amount, 2) }}</td>
                                            <td class="px-6 py-3 text-right font-mono">{{ number_format($item->unit_price, 2) }}</td>
                                            <td class="px-6 py-3 text-right font-mono font-bold text-gray-900">{{ number_format($item->total_amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="px-6 py-6 text-center text-gray-400">No items on this document.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <!-- Weights and Subtotal Bar -->
                        <div class="p-6 bg-slate-50/70 border-t border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="flex items-center space-x-6 text-xs text-gray-600">
                                <div>
                                    <span class="font-bold text-gray-500 uppercase">Total Net Weight:</span>
                                    <span class="font-mono font-bold text-gray-800 ms-1">{{ $document->total_net_weight ? number_format($document->total_net_weight, 3) . ' kg' : 'N/A' }}</span>
                                </div>
                                <div>
                                    <span class="font-bold text-gray-500 uppercase">Total Gross Weight:</span>
                                    <span class="font-mono font-bold text-gray-800 ms-1">{{ $document->total_gross_weight ? number_format($document->total_gross_weight, 3) . ' kg' : 'N/A' }}</span>
                                </div>
                            </div>

                            <div class="text-right">
                                <span class="text-xs font-bold text-gray-500 uppercase tracking-wider block">Final Total</span>
                                <span class="text-2xl font-mono font-black text-indigo-700">
                                    {{ $document->currency }} {{ number_format($document->final_total, 2) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Package Dimensions & Diameter Breakdown -->
                    @if($document->packages->isNotEmpty())
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="px-6 py-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                                <h3 class="font-bold text-sm text-gray-800 uppercase tracking-wider">
                                    Package Dimensions & Diameter Breakdown ({{ $document->packages->sum('quantity') }} pkgs)
                                </h3>
                                <div class="flex items-center space-x-4 text-xs font-mono text-gray-600">
                                    <span>Vol. Wt: <strong class="text-indigo-700">{{ number_format($document->packages->sum('volumetric_weight_kg'), 2) }} kg</strong></span>
                                    <span>Volume: <strong class="text-emerald-700">{{ number_format($document->packages->sum('cbm'), 3) }} m³</strong></span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50/50 text-gray-500 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-6 py-3 text-left">Package Type</th>
                                            <th class="px-6 py-3 text-left">Format</th>
                                            <th class="px-6 py-3 text-left">Dimensions</th>
                                            <th class="px-6 py-3 text-right">Quantity</th>
                                            <th class="px-6 py-3 text-right">Weight / Pkg</th>
                                            <th class="px-6 py-3 text-right">Volumetric Wt</th>
                                            <th class="px-6 py-3 text-right">CBM</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($document->packages as $pkg)
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-6 py-3 font-semibold text-gray-800">{{ $pkg->package_type }}</td>
                                                <td class="px-6 py-3">
                                                    @if($pkg->dimension_type === 'diameter')
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                                            Cylinder (Ø×H)
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 border border-gray-200">
                                                            Box (L×W×H)
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-3 font-mono font-bold text-gray-900">{{ $pkg->formatted_dimensions }}</td>
                                                <td class="px-6 py-3 text-right font-mono font-bold">{{ $pkg->quantity }}</td>
                                                <td class="px-6 py-3 text-right font-mono">{{ $pkg->gross_weight_per_pkg_kg ? number_format($pkg->gross_weight_per_pkg_kg, 3) . ' kg' : '-' }}</td>
                                                <td class="px-6 py-3 text-right font-mono font-semibold text-indigo-700">{{ $pkg->volumetric_weight_kg ? number_format($pkg->volumetric_weight_kg, 2) . ' kg' : '-' }}</td>
                                                <td class="px-6 py-3 text-right font-mono text-emerald-700">{{ $pkg->cbm ? number_format($pkg->cbm, 3) . ' m³' : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Shipment Method Costs (DHL, Air, Sea) with Rate per KG -->
                    @if($document->shipmentCosts->isNotEmpty())
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                <h4 class="font-bold text-sm text-gray-800 uppercase tracking-wider">
                                    Shipment Method Costs Audit & Rate / KG
                                </h4>
                                @php
                                    $totVol = $document->packages->sum('volumetric_weight_kg');
                                    $chgWt = max($document->total_gross_weight ?? 0, $totVol);
                                @endphp
                                @if($totVol > 0)
                                    <span class="text-xs text-indigo-600 font-mono">
                                        Chargeable Weight evaluated: <strong>{{ number_format($chgWt, 2) }} kg</strong>
                                    </span>
                                @endif
                            </div>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left">Carrier</th>
                                            <th class="px-4 py-2.5 text-right">Checked Wt (kg)</th>
                                            <th class="px-4 py-2.5 text-right">Rate / kg ({{ $document->currency }})</th>
                                            <th class="px-4 py-2.5 text-right">System Amount ({{ $document->currency }})</th>
                                            <th class="px-4 py-2.5 text-right">Added Amount ({{ $document->currency }})</th>
                                            <th class="px-4 py-2.5 text-right">Given Amount ({{ $document->currency }})</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($document->shipmentCosts as $ship)
                                            <tr>
                                                <td class="px-4 py-2.5 font-bold text-gray-800">{{ $ship->method_label }}</td>
                                                <td class="px-4 py-2.5 text-right font-mono">{{ $ship->checked_weight !== null ? number_format($ship->checked_weight, 3) : '-' }}</td>
                                                <td class="px-4 py-2.5 text-right font-mono text-indigo-600 font-semibold">{{ $ship->rate_per_kg !== null ? number_format($ship->rate_per_kg, 2) : '-' }}</td>
                                                <td class="px-4 py-2.5 text-right font-mono">{{ $ship->system_amount !== null ? number_format($ship->system_amount, 2) : '-' }}</td>
                                                <td class="px-4 py-2.5 text-right font-mono">{{ $ship->added_amount !== null ? number_format($ship->added_amount, 2) : '-' }}</td>
                                                <td class="px-4 py-2.5 text-right font-mono font-bold text-indigo-700">{{ $ship->given_amount !== null ? number_format($ship->given_amount, 2) : '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                    <!-- Notes -->
                    @if($document->notes)
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Notes & Terms</h4>
                            <p class="text-sm text-gray-700 whitespace-pre-line">{{ $document->notes }}</p>
                        </div>
                    @endif

                </div>

                <!-- Right Column: Version History & Concurrency Lock Status (4 Cols) -->
                <div class="lg:col-span-4 space-y-6">

                    <!-- Shipment Order Lifecycle Tracker Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-indigo-100 p-5 space-y-3">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-indigo-900 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                Order Lifecycle Tracker
                            </h4>
                            @php
                                $connectedOrders = $document->all_connected_shipment_orders;
                            @endphp
                            @if($connectedOrders->isNotEmpty())
                                <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-indigo-50 text-indigo-700">
                                    {{ $connectedOrders->count() }} Linked
                                </span>
                            @endif
                        </div>

                        @if($connectedOrders->isNotEmpty())
                            @foreach($connectedOrders as $order)
                                <div class="p-3 bg-slate-50 rounded-lg border border-slate-200 space-y-2 text-xs">
                                    <div class="flex items-center justify-between">
                                        <span class="font-mono font-bold text-indigo-700">{{ $order->order_number }}</span>
                                        <span class="font-bold text-emerald-600">{{ $order->progress_percent }}% Complete</span>
                                    </div>
                                    <div class="text-[11px] text-gray-500 flex items-center space-x-1">
                                        <span>Stage:</span>
                                        <strong class="text-gray-800">{{ $order->milestones->where('is_completed', true)->last()?->stage_name ?? 'PI Initialized' }}</strong>
                                    </div>
                                    @if($order->customer_po_number)
                                        <p class="text-gray-600">PO: <strong class="text-gray-900 font-mono">{{ $order->customer_po_number }}</strong></p>
                                    @endif
                                    @if($order->tracking_awb_no)
                                        <p class="text-gray-600">AWB/Tracking: <strong class="text-gray-900 font-mono">{{ $order->tracking_awb_no }}</strong></p>
                                    @endif
                                    <!-- Progress Bar -->
                                    <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden">
                                        <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $order->progress_percent }}%"></div>
                                    </div>
                                    <div class="pt-1 flex items-center justify-between">
                                        <a href="{{ route('shipment-orders.show', $order) }}" class="inline-flex items-center text-xs font-bold text-indigo-600 hover:text-indigo-800">
                                            Open Interactive Cockpit &rarr;
                                        </a>
                                        <a href="{{ route('shipment-orders.edit', $order) }}" class="text-[11px] font-semibold text-gray-500 hover:text-indigo-600">
                                            Edit Links
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <p class="text-xs text-gray-500">
                                Track this order's progress from Proforma Invoice (PI) & customer PO to payment, draft docs, dispatch, and final delivery.
                            </p>
                            <a href="{{ route('shipment-orders.create', ['document_id' => $document->id]) }}" class="w-full py-2 px-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold text-xs flex items-center justify-center space-x-1.5 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                <span>Start Shipment Order Tracker</span>
                            </a>
                        @endif
                    </div>

                    <!-- Concurrency Lock Info Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 flex items-center">
                            <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            Editing Lock Status
                        </h4>

                        @if($activeLock)
                            <div class="p-3 bg-amber-50 rounded-lg border border-amber-200 text-xs text-amber-900 space-y-1">
                                <div class="font-bold flex items-center">
                                    <span class="w-2 h-2 rounded-full bg-amber-500 me-2 animate-ping"></span>
                                    Currently Locked
                                </div>
                                <p>User: <span class="font-semibold">{{ $activeLock->user?->name }}</span></p>
                                <p class="text-[11px] text-amber-700">Expires: {{ $activeLock->expires_at->diffForHumans() }}</p>
                            </div>
                        @else
                            <div class="p-3 bg-emerald-50 rounded-lg border border-emerald-200 text-xs text-emerald-900 flex items-center">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 me-2"></span>
                                Document unlocked and available for editing.
                            </div>
                        @endif
                    </div>

                    <!-- Version History & Restore Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Version History
                            </h4>
                            @if(Auth::user()->canEdit() && !$document->isLockedByOther(Auth::user()))
                                <button type="button" @click="$dispatch('open-create-version')" class="inline-flex items-center text-[11px] font-bold text-indigo-600 hover:text-indigo-800 bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded-md border border-indigo-200 transition">
                                    <svg class="w-3 h-3 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    + New Version
                                </button>
                            @else
                                <span class="text-xs font-semibold text-gray-500 font-mono">{{ $document->versions->count() }} snapshot(s)</span>
                            @endif
                        </div>

                        <div class="space-y-3">
                            @forelse($document->versions as $v)
                                <div class="p-3 rounded-lg border {{ $v->version_number === $document->current_version ? 'bg-indigo-50/50 border-indigo-200' : 'bg-white border-gray-200 hover:bg-slate-50' }} transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-mono font-bold text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-800">
                                                v{{ $v->version_number }}
                                            </span>
                                            @if($v->version_number === $document->current_version)
                                                <span class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 bg-indigo-100 px-1.5 py-0.2 rounded">Current</span>
                                            @endif
                                        </div>
                                        <span class="text-[11px] text-gray-400">{{ $v->created_at->format('M d, H:i') }}</span>
                                    </div>

                                    <p class="text-xs text-gray-600 mt-1 italic">{{ $v->change_summary ?: 'Version snapshot' }}</p>
                                    <p class="text-[10px] text-gray-400 mt-0.5">By {{ $v->creator?->name ?? 'User' }}</p>

                                    <div class="mt-2.5 pt-2 border-t border-gray-100 flex items-center justify-between text-xs">
                                        <a href="{{ route('documents.versions.show', [$document, $v->version_number]) }}" class="text-indigo-600 hover:text-indigo-800 font-semibold text-[11px]">
                                            View Snapshot &rarr;
                                        </a>

                                        @if(Auth::user()->canEdit() && $v->version_number !== $document->current_version)
                                            <form action="{{ route('documents.versions.restore', [$document, $v->version_number]) }}"
                                                  method="POST"
                                                  data-confirm="Restore document to Version {{ $v->version_number }}? This will create a new current active version (v{{ $document->current_version + 1 }}) reflecting these exact contents."
                                                  data-confirm-title="Restore Version {{ $v->version_number }}"
                                                  data-confirm-button="Yes, Restore Version"
                                                  data-confirm-type="primary">
                                                @csrf
                                                <button type="submit" class="text-amber-700 hover:text-amber-900 font-semibold text-[11px] bg-amber-50 hover:bg-amber-100 px-2 py-0.5 rounded border border-amber-200 transition">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-4 text-xs text-gray-400">
                                    No past versions saved yet.
                                </div>
                            @endforelse
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- In-Site Modal: Create New Version Snapshot -->
    <div x-data="{ isOpen: false }"
         @open-create-version.window="isOpen = true"
         x-show="isOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         style="display: none;">

        <!-- Backdrop -->
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"
             @click="isOpen = false"></div>

        <!-- Modal Dialog Content -->
        <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
            <div x-show="isOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 @keydown.escape.window="isOpen = false"
                 class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-gray-100">

                <form action="{{ route('documents.versions.store', $document) }}" method="POST">
                    @csrf
                    <div class="bg-white p-6">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 flex-shrink-0">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900">Create Version Snapshot</h3>
                                <div class="flex items-center space-x-2 text-xs text-gray-500 mt-0.5">
                                    <span class="font-mono font-bold bg-gray-100 px-1.5 py-0.5 rounded text-gray-700">Current: v{{ $document->current_version }}</span>
                                    <span>&rarr;</span>
                                    <span class="font-mono font-bold bg-indigo-100 px-1.5 py-0.5 rounded text-indigo-800">New: v{{ $document->current_version + 1 }}</span>
                                </div>
                            </div>
                        </div>

                        <p class="text-xs text-gray-500 mb-4 leading-relaxed">
                            This will freeze and record an immutable snapshot of all current line items, packages, volumetric weights, and shipping costs under <strong>Version {{ $document->current_version + 1 }}</strong>.
                        </p>

                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-700 mb-1">
                                Version Change Summary <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   name="change_summary"
                                   required
                                   autofocus
                                   placeholder="e.g. Approved by buyer; finalized packing dimensions"
                                   class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <div class="bg-gray-50/80 px-6 py-4 flex items-center justify-end space-x-3 border-t border-gray-100">
                        <button type="button"
                                @click="isOpen = false"
                                class="rounded-xl bg-white px-4 py-2 text-xs font-semibold text-gray-700 shadow-2xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                                class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white shadow-xs hover:bg-indigo-700 transition">
                            Create Snapshot v{{ $document->current_version + 1 }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function documentLockWatcher(docId, initialLockedByOther, initialUserName) {
            return {
                isCurrentlyLocked: initialLockedByOther,
                lockedByUser: initialUserName || 'Another user',
                justUnlocked: false,

                init() {
                    if (this.isCurrentlyLocked) {
                        const interval = setInterval(async () => {
                            try {
                                const res = await fetch(`/documents/${docId}/lock-status`);
                                if (res.ok) {
                                    const data = await res.json();
                                    if (!data.is_locked || data.is_locked_by_me) {
                                        this.isCurrentlyLocked = false;
                                        this.justUnlocked = true;
                                        clearInterval(interval);
                                    } else {
                                        this.lockedByUser = data.locked_by;
                                    }
                                }
                            } catch (e) {}
                        }, 3000);
                    }
                }
            };
        }
    </script>
</x-app-layout>
