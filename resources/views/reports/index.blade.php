<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight flex items-center">
                    <svg class="w-7 h-7 me-2.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    {{ __('Reports & Data Exports Center') }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Export high-fidelity Excel (.xlsx) spreadsheets and formatted PDF documents for operational auditing, freight logistics, and inventory management.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- Summary KPI Cards -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-gray-400 uppercase tracking-wider block">Total Orders / Docs</span>
                    <span class="text-2xl font-black text-gray-900 mt-1 block">{{ number_format($metrics['total_orders']) }}</span>
                    <span class="text-[11px] text-gray-400 mt-0.5 block">Recorded documents</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-indigo-500 uppercase tracking-wider block">Total Gross Weight</span>
                    <span class="text-2xl font-black text-indigo-700 mt-1 block">{{ number_format($metrics['total_weight_kg'], 1) }} kg</span>
                    <span class="text-[11px] text-indigo-600/70 mt-0.5 block">Cargo weight evaluated</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-emerald-600 uppercase tracking-wider block">Active Ongoing Orders</span>
                    <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ $metrics['active_shipments'] }}</span>
                    <span class="text-[11px] text-emerald-700/70 mt-0.5 block">Shipments in progress</span>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-xs border border-gray-100">
                    <span class="text-xs font-bold text-rose-600 uppercase tracking-wider block">Total Missing Parts</span>
                    <span class="text-2xl font-black text-rose-600 mt-1 block">{{ number_format($metrics['total_short_parts'], 2) }}</span>
                    <span class="text-[11px] text-rose-700/70 mt-0.5 block">Across {{ $metrics['short_items_count'] }} short items</span>
                </div>
            </div>

            <!-- Report Generator Cards Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- 1. Freight & Weights Orders Log -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-gray-900 leading-tight">Orders Log with Weights & Freight</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Net/Gross weights & quoted carrier freight charges</p>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed">
                            Generates a detailed log of orders with Net and Gross weights, package counts, CBM, and carrier freight charges with rate per kg (DHL, Air, Sea, Road, Courier).
                        </p>

                        <form id="form-freight" action="{{ route('reports.freight-weights') }}" method="GET" class="space-y-3 pt-2">
                            <input type="hidden" name="format" id="format-freight" value="excel">

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">From Date</label>
                                    <input type="date" name="start_date" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">To Date</label>
                                    <input type="date" name="end_date" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Document Type</label>
                                <select name="document_type" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">All Document Types</option>
                                    @foreach($documentTypes as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Carrier Method</label>
                                <select name="carrier_method" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">All Carrier Methods</option>
                                    @foreach($carrierMethods as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center space-x-2">
                        <button type="button" onclick="submitReport('form-freight', 'format-freight', 'excel')"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                            Excel (.xlsx)
                        </button>
                        <button type="button" onclick="submitReport('form-freight', 'format-freight', 'pdf')"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                            PDF Document
                        </button>
                    </div>
                </div>

                <!-- 2. Current Ongoing Orders List with Progress -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-gray-900 leading-tight">Ongoing Orders Progress</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Live tracking milestones, PO, payment & carrier</p>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed">
                            Reports live shipment orders status, current milestones, completion percentage, customer purchase orders, payment verification, and dispatch/tracking AWB.
                        </p>

                        <form id="form-orders" action="{{ route('reports.ongoing-orders') }}" method="GET" class="space-y-3 pt-2">
                            <input type="hidden" name="format" id="format-orders" value="excel">

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Order Status</label>
                                <select name="status" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="active">Active / Ongoing Orders Only</option>
                                    <option value="all">All Orders (Including Completed)</option>
                                    <option value="completed">Completed Orders</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Carrier Category</label>
                                <select name="category" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">All Carriers / Categories</option>
                                    @foreach($carrierMethods as $code => $label)
                                        <option value="{{ $code }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">Payment Status</label>
                                <select name="payment_status" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="all">All Payment Stages</option>
                                    <option value="pending">Pending</option>
                                    <option value="payment_submitted">Payment Submitted</option>
                                    <option value="advance_received">Advance Received</option>
                                    <option value="fully_paid">Fully Paid</option>
                                </select>
                            </div>
                        </form>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center space-x-2">
                        <button type="button" onclick="submitReport('form-orders', 'format-orders', 'excel')"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                            Excel (.xlsx)
                        </button>
                        <button type="button" onclick="submitReport('form-orders', 'format-orders', 'pdf')"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                            PDF Document
                        </button>
                    </div>
                </div>

                <!-- 3. Short Parts List (Consolidated Master List) -->
                <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 flex flex-col justify-between space-y-6">
                    <div class="space-y-4">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div>
                                <h3 class="font-black text-base text-gray-900 leading-tight">Master Short Parts Report</h3>
                                <p class="text-xs text-gray-500 mt-0.5">Consolidated missing items across all orders</p>
                            </div>
                        </div>

                        <p class="text-xs text-gray-600 leading-relaxed">
                            Aggregates all missing parts across every Reserve (R) document. Shows total short quantities by item code, affected order numbers, warehouse bins, and reasons.
                        </p>

                        <form id="form-shortage" action="{{ route('reports.master-shortage') }}" method="GET" class="space-y-3 pt-2">
                            <input type="hidden" name="format" id="format-shortage" value="excel">

                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">From Date</label>
                                    <input type="date" name="start_date" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold uppercase text-gray-500 mb-1">To Date</label>
                                    <input type="date" name="end_date" class="w-full text-xs rounded-lg border-gray-300 py-1.5 focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>

                            <div class="p-3 bg-amber-50/70 border border-amber-200 rounded-xl">
                                <span class="text-[11px] text-amber-800 font-semibold block">
                                    💡 Tip: You can also export individual shortage reports directly from any specific reservation detail view.
                                </span>
                            </div>
                        </form>
                    </div>

                    <div class="pt-4 border-t border-gray-100 flex items-center space-x-2">
                        <button type="button" onclick="submitReport('form-shortage', 'format-shortage', 'excel')"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a2 2 0 00-2 2v12a2 2 0 002 2h8a2 2 0 002-2V7.414A2 2 0 0015.414 6L12 2.586A2 2 0 0010.586 2H6zm5 6a1 1 0 10-2 0v3.586l-1.293-1.293a1 1 0 10-1.414 1.414l3 3a1 1 0 001.414 0l3-3a1 1 0 00-1.414-1.414L11 11.586V8z" clip-rule="evenodd"></path></svg>
                            Excel (.xlsx)
                        </button>
                        <button type="button" onclick="submitReport('form-shortage', 'format-shortage', 'pdf')"
                                class="flex-1 inline-flex items-center justify-center px-3 py-2 bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
                            <svg class="w-4 h-4 me-1.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd"></path></svg>
                            PDF Document
                        </button>
                    </div>
                </div>

            </div>

        </div>
    </div>

    <script>
        function submitReport(formId, formatInputId, format) {
            document.getElementById(formatInputId).value = format;
            document.getElementById(formId).submit();
        }
    </script>
</x-app-layout>
