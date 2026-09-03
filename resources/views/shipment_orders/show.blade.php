<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div class="flex items-center space-x-3">
                <a href="{{ route('shipment-orders.index') }}" class="text-gray-400 hover:text-gray-600 transition" title="Back to Orders">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="font-bold text-2xl text-gray-900 font-mono leading-tight">
                            {{ $shipmentOrder->order_number }}
                        </h2>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $shipmentOrder->status === 'completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800' }}">
                            {{ ucfirst($shipmentOrder->status) }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5 flex items-center space-x-2">
                        <span>Customer: <strong class="text-gray-700">{{ $shipmentOrder->company_name }} ({{ $shipmentOrder->country }})</strong></span>
                        @if($shipmentOrder->document)
                            <span>&bull;</span>
                            <span>Originating PI: <a href="{{ route('documents.show', $shipmentOrder->document) }}" class="text-indigo-600 hover:underline font-mono font-bold">{{ $shipmentOrder->document->document_number }}</a></span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Customer PO Indicator -->
            <div class="flex items-center space-x-3">
                @if($shipmentOrder->customer_po_number)
                    <div class="bg-indigo-50 border border-indigo-200 px-3 py-1.5 rounded-lg text-xs">
                        <span class="text-gray-500 uppercase font-bold text-[10px] block">Customer PO Ref</span>
                        <span class="font-mono font-bold text-indigo-900 text-sm">{{ $shipmentOrder->customer_po_number }}</span>
                    </div>
                @else
                    <div class="bg-amber-50 border border-amber-200 px-3 py-1.5 rounded-lg text-xs text-amber-800 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-amber-500 me-2 animate-pulse"></span>
                        <span>Awaiting Customer PO</span>
                    </div>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- Interactive Milestone Tracking App with Alpine.js -->
    <div class="py-8" x-data="orderTracker(@js($shipmentOrder), @js($shipmentOrder->milestones))">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- Flash Success Message -->
            @if(session('success'))
                <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-md flex items-center text-emerald-800 text-sm shadow-sm">
                    <svg class="w-5 h-5 me-2 text-emerald-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            <!-- Live Progress Cockpit Header -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <h3 class="font-extrabold text-lg text-gray-900 flex items-center">
                            <span>Shipment Lifecycle Progress:</span>
                            <span class="ms-2 font-mono text-indigo-600" x-text="`${progressPercent}% Complete`"></span>
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span x-text="completedCount"></span> of <span x-text="milestones.length"></span> stages verified &bull; Click any milestone below to update its status live.
                        </p>
                    </div>

                    <!-- Celebration Badge -->
                    <template x-if="progressPercent === 100">
                        <div class="px-4 py-2 bg-emerald-100 border border-emerald-300 text-emerald-900 rounded-lg text-xs font-bold flex items-center space-x-2 animate-bounce">
                            <svg class="w-4 h-4 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                            <span>Shipment Delivered & Completed!</span>
                        </div>
                    </template>
                </div>

                <!-- Animated Progress Bar -->
                <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full transition-all duration-500 ease-out"
                         :class="progressPercent === 100 ? 'bg-emerald-500' : 'bg-gradient-to-r from-indigo-500 to-indigo-600'"
                         :style="`width: ${progressPercent}%`">
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                <!-- Left Column: Interactive 8-Stage Milestones Checklist (8 Cols) -->
                <div class="lg:col-span-8 space-y-4">

                    <h4 class="font-bold text-xs uppercase tracking-wider text-gray-500 px-1">
                        Order Lifecycle Stages
                    </h4>

                    <!-- Milestone Cards List -->
                    <template x-for="(m, index) in milestones" :key="m.id">
                        <div class="bg-white rounded-xl shadow-sm border p-5 transition"
                             :class="m.is_completed ? 'border-emerald-200 bg-emerald-50/20' : 'border-gray-200 hover:border-indigo-200'">
                            <div class="flex items-start justify-between gap-4">

                                <!-- Interactive Checkbox & Details -->
                                <div class="flex items-start space-x-4 flex-1">
                                    <!-- Large Checkbox Button -->
                                    <button type="button"
                                            @click="toggleMilestone(m)"
                                            :disabled="isUpdating"
                                            class="mt-1 w-7 h-7 rounded-lg flex items-center justify-center transition focus:outline-none focus:ring-2 focus:ring-offset-2"
                                            :class="m.is_completed ? 'bg-emerald-500 text-white hover:bg-emerald-600 focus:ring-emerald-400 shadow-sm' : 'border-2 border-gray-300 hover:border-indigo-500 text-transparent focus:ring-indigo-400 bg-white'"
                                            :title="m.is_completed ? 'Click to mark as pending' : 'Click to mark as completed'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="3">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>

                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <span class="font-bold text-sm text-gray-900" :class="{'text-emerald-900': m.is_completed}" x-text="m.stage_name"></span>
                                            <span x-show="m.is_completed" class="text-[10px] uppercase font-bold px-2 py-0.2 rounded-full bg-emerald-100 text-emerald-800">
                                                Verified
                                            </span>
                                        </div>

                                        <p class="text-xs text-gray-500 mt-0.5" x-text="m.notes"></p>

                                        <!-- Reference Number Input / Display -->
                                        <div class="mt-3 flex items-center space-x-2 text-xs">
                                            <span class="font-semibold text-gray-500 text-[11px]">Reference / Details:</span>
                                            <input type="text"
                                                   x-model="m.reference_no"
                                                   @blur="saveMilestoneMeta(m)"
                                                   placeholder="e.g. Ref #, PO #, AWB Tracking #"
                                                   class="text-xs font-mono rounded border-gray-300 py-1 px-2 w-64 focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>

                                        <!-- Verification Audit Stamp -->
                                        <div x-show="m.is_completed && m.completed_at" class="mt-2 text-[11px] text-emerald-700 flex items-center space-x-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            <span>Completed <span x-text="formatDate(m.completed_at)"></span></span>
                                            <span x-show="m.completed_by_name">&bull; By <strong x-text="m.completed_by_name"></strong></span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step Icon Badge -->
                                <div class="w-8 h-8 rounded-full flex items-center justify-center font-bold text-xs font-mono"
                                     :class="m.is_completed ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-500'"
                                     x-text="index + 1">
                                </div>
                            </div>
                        </div>
                    </template>

                </div>

                <!-- Right Column: Order Details & Connected Documents (4 Cols) -->
                <div class="lg:col-span-4 space-y-6">

                    <!-- Customer Purchase Order (PO) Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Customer PO Details
                            </h4>
                            <span class="text-[10px] font-bold uppercase text-gray-400 font-mono">Stage 2</span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div>
                                <span class="text-gray-400 block text-[10px] uppercase font-semibold">PO Number</span>
                                <span class="text-sm font-mono font-bold text-gray-900">{{ $shipmentOrder->customer_po_number ?: 'Not yet received' }}</span>
                            </div>
                            @if($shipmentOrder->customer_po_date)
                                <div>
                                    <span class="text-gray-400 block text-[10px] uppercase font-semibold">PO Date</span>
                                    <span class="text-gray-700">{{ $shipmentOrder->customer_po_date->format('M d, Y') }}</span>
                                </div>
                            @endif
                            @if($shipmentOrder->customer_po_notes)
                                <div class="bg-gray-50 p-2.5 rounded-lg border border-gray-100 text-gray-600 whitespace-pre-line">
                                    {{ $shipmentOrder->customer_po_notes }}
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Payment Information -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Payment Verification
                            </h4>
                            <span class="text-[10px] font-bold uppercase text-gray-400 font-mono">Stage 3</span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Status:</span>
                                <span class="font-bold uppercase font-mono {{ $shipmentOrder->payment_status === 'fully_paid' ? 'text-emerald-700' : 'text-amber-700' }}">
                                    {{ str_replace('_', ' ', $shipmentOrder->payment_status) }}
                                </span>
                            </div>
                            @if($shipmentOrder->payment_reference)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Swift/TT Ref:</span>
                                    <span class="font-mono font-bold text-gray-800">{{ $shipmentOrder->payment_reference }}</span>
                                </div>
                            @endif
                            @if($shipmentOrder->payment_amount)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Amount Received:</span>
                                    <span class="font-mono font-bold text-indigo-700 text-sm">
                                        {{ $shipmentOrder->currency }} {{ number_format($shipmentOrder->payment_amount, 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Linked Documents Cards -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                        <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 border-b border-gray-100 pb-2 flex items-center">
                            <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                            Linked Workflow Documents
                        </h4>

                        <div class="space-y-3 text-xs">
                            <!-- Originating PI -->
                            @if($shipmentOrder->document)
                                <div class="p-3 bg-slate-50 rounded-lg border border-gray-200 flex items-center justify-between">
                                    <div>
                                        <span class="text-[10px] text-gray-400 uppercase font-bold block">Proforma Invoice</span>
                                        <span class="font-mono font-bold text-indigo-700 text-sm">{{ $shipmentOrder->document->document_number }}</span>
                                    </div>
                                    <a href="{{ route('documents.show', $shipmentOrder->document) }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                        View &rarr;
                                    </a>
                                </div>
                            @endif

                            <!-- Commercial Invoice (N) -->
                            <div class="p-3 bg-slate-50 rounded-lg border border-gray-200">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Commercial Invoice (N)</span>
                                <span class="font-mono font-bold text-gray-800">{{ $shipmentOrder->linked_invoice_no ?: 'Pending generation' }}</span>
                            </div>

                            <!-- Packing List (W) -->
                            <div class="p-3 bg-slate-50 rounded-lg border border-gray-200">
                                <span class="text-[10px] text-gray-400 uppercase font-bold block">Packing List (W)</span>
                                <span class="font-mono font-bold text-gray-800">{{ $shipmentOrder->linked_packing_list_no ?: 'Pending generation' }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Carrier & AWB Details -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                Carrier & Dispatch
                            </h4>
                            <span class="text-[10px] font-bold uppercase text-gray-400 font-mono">Stage 6</span>
                        </div>

                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Method:</span>
                                <span class="font-bold text-gray-800">{{ $shipmentOrder->carrier_method ?: 'DHL / Air' }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">AWB / Tracking #:</span>
                                <span class="font-mono font-bold text-indigo-700">{{ $shipmentOrder->tracking_awb_no ?: 'Pending dispatch' }}</span>
                            </div>
                            @if($shipmentOrder->dispatch_date)
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Dispatch Date:</span>
                                    <span class="text-gray-800">{{ $shipmentOrder->dispatch_date->format('M d, Y') }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <!-- Alpine.js Live Interactive Milestone Handler -->
    <script>
        function orderTracker(order, initialMilestones) {
            return {
                order: order,
                milestones: initialMilestones || [],
                isUpdating: false,

                get completedCount() {
                    return this.milestones.filter(m => m.is_completed).length;
                },

                get progressPercent() {
                    if (this.milestones.length === 0) return 0;
                    return Math.round((this.completedCount / this.milestones.length) * 100);
                },

                async toggleMilestone(m) {
                    if (this.isUpdating) return;
                    this.isUpdating = true;

                    try {
                        const response = await fetch(`/shipment-orders/${this.order.id}/milestones/${m.id}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                reference_no: m.reference_no,
                                notes: m.notes
                            })
                        });

                        if (response.ok) {
                            const data = await response.json();
                            m.is_completed = data.is_completed;
                            m.completed_at = data.completed_at;
                            m.completed_by_name = data.completed_by_name;
                        }
                    } catch (e) {
                        console.error('Milestone toggle error', e);
                    } finally {
                        this.isUpdating = false;
                    }
                },

                async saveMilestoneMeta(m) {
                    try {
                        await fetch(`/shipment-orders/${this.order.id}/milestones/${m.id}/toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                reference_no: m.reference_no,
                                notes: m.notes
                            })
                        });
                    } catch (e) {}
                },

                formatDate(dt) {
                    if (!dt) return '';
                    return dt;
                }
            };
        }
    </script>
</x-app-layout>
