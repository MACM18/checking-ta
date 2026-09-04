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
                    <p class="text-xs text-gray-500 mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span>Category: <strong class="text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ $shipmentOrder->shipment_category ?? 'Standard' }}</strong></span>
                        <span>&bull;</span>
                        <span>Customer: <strong class="text-gray-700">{{ $shipmentOrder->company_name }} ({{ $shipmentOrder->country }})</strong></span>
                        @if($shipmentOrder->document)
                            <span>&bull;</span>
                            <span>System Doc: <a href="{{ route('documents.show', $shipmentOrder->document) }}" class="text-indigo-600 hover:underline font-mono font-bold">{{ $shipmentOrder->document->document_number }}</a></span>
                        @elseif($shipmentOrder->proforma_invoice_no)
                            <span>&bull;</span>
                            <span>PI: <strong class="text-gray-800 font-mono">{{ $shipmentOrder->proforma_invoice_no }}</strong></span>
                        @elseif($shipmentOrder->document_reference)
                            <span>&bull;</span>
                            <span>External Ref: <strong class="text-amber-800 bg-amber-50 px-1.5 py-0.5 rounded font-mono">{{ $shipmentOrder->document_reference }}</strong></span>
                        @endif
                    </p>
                </div>
            </div>

            <!-- Customer PO Indicator & Actions -->
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

                @if($shipmentOrder->status !== 'completed')
                    <form action="{{ route('shipment-orders.complete', $shipmentOrder) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit"
                                data-confirm="Are you sure you want to mark this shipment order as Completed? This will immediately complete all remaining milestone stages without ticking each box manually."
                                data-confirm-title="Complete Shipment Order"
                                data-confirm-btn="Mark as Completed"
                                data-confirm-type="success"
                                class="inline-flex items-center px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                            <span>Mark as Completed</span>
                        </button>
                    </form>
                @endif

                <a href="{{ route('shipment-orders.edit', $shipmentOrder) }}" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-50 hover:text-indigo-600 shadow-sm transition">
                    <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                    <span>Edit Order & Docs</span>
                </a>
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

                    <!-- Custom Status Message Banner -->
                    <div class="bg-amber-50/70 border-2 border-amber-200 rounded-xl p-4 shadow-2xs space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                                <h4 class="font-extrabold text-xs uppercase tracking-wider text-amber-900">Custom Order Status (Highlighted on Order List)</h4>
                            </div>
                            <button type="button" @click="editingStatus = !editingStatus" class="text-xs text-amber-800 hover:text-amber-950 font-bold underline">
                                <span x-text="editingStatus ? 'Cancel' : (customStatus ? 'Edit Status' : '+ Set Status')"></span>
                            </button>
                        </div>

                        <div x-show="!editingStatus" class="pt-1">
                            <template x-if="customStatus">
                                <div class="inline-flex items-center px-3 py-1 rounded-lg text-xs font-bold bg-amber-100 text-amber-950 border border-amber-300">
                                    <svg class="w-3.5 h-3.5 me-1.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <span x-text="customStatus"></span>
                                </div>
                            </template>
                            <template x-if="!customStatus">
                                <p class="text-xs text-amber-700 italic">No custom status set. Click "+ Set Status" to add a status message (e.g. "Awaiting customs clearance").</p>
                            </template>
                        </div>

                        <div x-show="editingStatus" class="pt-1">
                            <form @submit.prevent="saveCustomStatus()" class="flex items-center gap-2">
                                <input type="text"
                                       x-model="statusInput"
                                       placeholder="e.g. Awaiting customs clearance, Urgent inspection"
                                       maxlength="255"
                                       class="text-xs rounded-lg border-amber-300 focus:border-amber-500 focus:ring-amber-500 flex-1 py-1.5">
                                <button type="submit"
                                        :disabled="isSavingStatus"
                                        class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50">
                                    <span x-show="!isSavingStatus">Save Status</span>
                                    <span x-show="isSavingStatus">Saving...</span>
                                </button>
                            </form>
                        </div>
                    </div>

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
                                Payment Tracking
                            </h4>
                            <span class="text-[10px] font-bold uppercase text-gray-400 font-mono">Stages 3 & 4</span>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500">Status:</span>
                                @if($shipmentOrder->payment_status === 'fully_paid')
                                    <span class="font-bold uppercase font-mono text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                        Fully Paid
                                    </span>
                                @elseif($shipmentOrder->payment_status === 'advance_received')
                                    <span class="font-bold uppercase font-mono text-blue-700 bg-blue-50 px-2 py-0.5 rounded border border-blue-200">
                                        Advance Received
                                    </span>
                                @elseif($shipmentOrder->payment_status === 'payment_submitted')
                                    <span class="font-bold uppercase font-mono text-purple-700 bg-purple-50 px-2 py-0.5 rounded border border-purple-200">
                                        Payment Submitted
                                    </span>
                                @else
                                    <span class="font-bold uppercase font-mono text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                                        Pending Payment
                                    </span>
                                @endif
                            </div>

                            <!-- Stage 3: Payment Submitted details -->
                            <div class="pt-2 border-t border-gray-100">
                                <div class="text-[11px] font-bold text-gray-700 uppercase mb-1">Stage 3: Payment Submitted</div>
                                @if($shipmentOrder->payment_submitted_at || $shipmentOrder->payment_submission_ref)
                                    <div class="bg-purple-50/50 p-2.5 rounded-lg border border-purple-100 space-y-1">
                                        <div class="flex justify-between">
                                            <span class="text-gray-500">Slip/Advice Ref:</span>
                                            <span class="font-mono font-bold text-gray-900">{{ $shipmentOrder->payment_submission_ref ?: 'Submitted' }}</span>
                                        </div>
                                        @if($shipmentOrder->payment_submitted_at)
                                            <div class="flex justify-between text-[11px]">
                                                <span class="text-gray-500">Submitted Date:</span>
                                                <span class="font-medium text-gray-700">{{ $shipmentOrder->payment_submitted_at->format('M d, Y') }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Awaiting payment submission from client</span>
                                @endif
                            </div>

                            <!-- Stage 4: Payment Confirmed details -->
                            <div class="pt-2 border-t border-gray-100">
                                <div class="text-[11px] font-bold text-gray-700 uppercase mb-1">Stage 4: Payment Confirmed</div>
                                @if($shipmentOrder->payment_confirmed_at || $shipmentOrder->payment_reference)
                                    <div class="bg-emerald-50/50 p-2.5 rounded-lg border border-emerald-100 space-y-1">
                                        @if($shipmentOrder->payment_reference)
                                            <div class="flex justify-between">
                                                <span class="text-gray-500">Confirmed Ref:</span>
                                                <span class="font-mono font-bold text-emerald-900">{{ $shipmentOrder->payment_reference }}</span>
                                            </div>
                                        @endif
                                        @if($shipmentOrder->payment_confirmed_at)
                                            <div class="flex justify-between text-[11px]">
                                                <span class="text-gray-500">Confirmed Date:</span>
                                                <span class="font-medium text-emerald-800">{{ $shipmentOrder->payment_confirmed_at->format('M d, Y') }}</span>
                                            </div>
                                        @endif
                                        @if($shipmentOrder->paymentConfirmedBy)
                                            <div class="flex justify-between text-[11px]">
                                                <span class="text-gray-500">Verified By:</span>
                                                <span class="font-medium text-gray-700">{{ $shipmentOrder->paymentConfirmedBy->name }}</span>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400 italic text-[11px]">Pending finance verification</span>
                                @endif
                            </div>

                            @if($shipmentOrder->payment_amount)
                                <div class="flex items-center justify-between pt-2 border-t border-gray-100">
                                    <span class="text-gray-500 font-semibold">Total Amount:</span>
                                    <span class="font-mono font-bold text-indigo-700 text-sm">
                                        {{ $shipmentOrder->currency }} {{ number_format($shipmentOrder->payment_amount, 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Linked Documents Cards -->
                    @php
                        $resolvedPI = $shipmentOrder->resolved_proforma_document;
                        $resolvedInvoice = $shipmentOrder->resolved_invoice_document;
                        $resolvedPL = $shipmentOrder->resolved_packing_list_document;
                    @endphp
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                            <h4 class="font-bold text-xs uppercase tracking-wider text-gray-700 flex items-center">
                                <svg class="w-4 h-4 me-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                                Linked Workflow Documents
                            </h4>
                            <a href="{{ route('shipment-orders.edit', $shipmentOrder) }}" class="text-[11px] font-bold text-indigo-600 hover:text-indigo-800 transition">
                                Edit Links
                            </a>
                        </div>

                        <div class="space-y-3 text-xs">
                            <!-- Originating PI -->
                            <div class="p-3 bg-slate-50 rounded-lg border border-gray-200">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] text-gray-400 uppercase font-bold">Proforma Invoice</span>
                                    @if($resolvedPI)
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-indigo-100 text-indigo-700">System Record</span>
                                    @elseif($shipmentOrder->proforma_invoice_no || $shipmentOrder->document_reference)
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-gray-200 text-gray-700">External / Ref</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-indigo-700 text-sm">
                                        {{ $resolvedPI?->document_number ?? $shipmentOrder->proforma_invoice_no ?? $shipmentOrder->document_reference ?? 'None specified' }}
                                    </span>
                                    @if($resolvedPI)
                                        <a href="{{ route('documents.show', $resolvedPI) }}" class="inline-flex items-center font-bold text-indigo-600 hover:text-indigo-800 hover:underline">
                                            View PI &rarr;
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Commercial Invoice (N) -->
                            <div class="p-3 bg-slate-50 rounded-lg border border-gray-200">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] text-gray-400 uppercase font-bold">Commercial Invoice (N)</span>
                                    @if($resolvedInvoice)
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700">System Record</span>
                                    @elseif($shipmentOrder->linked_invoice_no)
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-gray-200 text-gray-700">External / Ref</span>
                                    @else
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">Pending</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-gray-800 text-sm">
                                        {{ $resolvedInvoice?->document_number ?? $shipmentOrder->linked_invoice_no ?: 'Pending generation' }}
                                    </span>
                                    @if($resolvedInvoice)
                                        <a href="{{ route('documents.show', $resolvedInvoice) }}" class="inline-flex items-center font-bold text-emerald-600 hover:text-emerald-800 hover:underline">
                                            View Invoice &rarr;
                                        </a>
                                    @elseif(! $shipmentOrder->linked_invoice_no)
                                        <a href="{{ route('shipment-orders.edit', $shipmentOrder) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">
                                            + Link Invoice
                                        </a>
                                    @endif
                                </div>
                            </div>

                            <!-- Packing List (W) -->
                            <div class="p-3 bg-slate-50 rounded-lg border border-gray-200">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] text-gray-400 uppercase font-bold">Packing List (W)</span>
                                    @if($resolvedPL)
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">System Record</span>
                                    @elseif($shipmentOrder->linked_packing_list_no)
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-gray-200 text-gray-700">External / Ref</span>
                                    @else
                                        <span class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded bg-amber-100 text-amber-800">Pending</span>
                                    @endif
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="font-mono font-bold text-gray-800 text-sm">
                                        {{ $resolvedPL?->document_number ?? $shipmentOrder->linked_packing_list_no ?: 'Pending generation' }}
                                    </span>
                                    @if($resolvedPL)
                                        <a href="{{ route('documents.show', $resolvedPL) }}" class="inline-flex items-center font-bold text-blue-600 hover:text-blue-800 hover:underline">
                                            View List &rarr;
                                        </a>
                                    @elseif(! $shipmentOrder->linked_packing_list_no)
                                        <a href="{{ route('shipment-orders.edit', $shipmentOrder) }}" class="text-[11px] font-bold text-indigo-600 hover:underline">
                                            + Link Packing List
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('shipment-orders.edit', $shipmentOrder) }}" class="w-full py-2 px-3 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-lg font-bold text-xs flex items-center justify-center space-x-1.5 transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                            <span>Manage Linked Documents</span>
                        </a>
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
                customStatus: (order && order.custom_status_message) ? order.custom_status_message : '',
                editingStatus: false,
                statusInput: (order && order.custom_status_message) ? order.custom_status_message : '',
                isSavingStatus: false,

                async saveCustomStatus() {
                    if (this.isSavingStatus) return;
                    this.isSavingStatus = true;
                    try {
                        const response = await fetch(`/shipment-orders/${this.order.id}/custom-status`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                custom_status_message: this.statusInput
                            })
                        });
                        if (response.ok) {
                            const data = await response.json();
                            this.customStatus = data.custom_status_message || '';
                            this.statusInput = this.customStatus;
                            this.editingStatus = false;
                        }
                    } catch (e) {
                        console.error('Custom status error', e);
                    } finally {
                        this.isSavingStatus = false;
                    }
                },

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
