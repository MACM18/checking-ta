<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <div class="flex items-center space-x-2">
                    <a href="{{ route('documents.show', $document) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 flex items-center">
                        <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        Exit Edit Mode
                    </a>
                </div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight mt-1 flex items-center">
                    Editing {{ $document->document_number }}
                    <span class="ms-2 px-2.5 py-0.5 rounded-full text-xs font-bold uppercase tracking-wider bg-indigo-50 text-indigo-700 border border-indigo-200">
                        {{ $document->formatted_type }}
                    </span>
                    <span class="ms-2 px-2 py-0.5 rounded-md text-xs font-semibold bg-gray-100 text-gray-700 font-mono">
                        v{{ $document->current_version }}
                    </span>
                </h2>
            </div>

            <!-- Active Lock Heartbeat Badge -->
            <div class="inline-flex items-center px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-800 border border-emerald-300 shadow-sm"
                 x-data="{ lastPing: 'Just now' }"
                 x-init="
                    setInterval(() => {
                        fetch('{{ route('documents.lock.heartbeat', $document) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        }).then(r => r.json()).then(() => {
                            lastPing = new Date().toLocaleTimeString();
                        });
                    }, 40000);
                 ">
                <span class="w-2 h-2 rounded-full bg-emerald-500 me-2 animate-pulse"></span>
                <span>Lock Active (Secured for you)</span>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="documentEditor(@js($document->items), @js($document->currency), @js($document->document_type), @js($document->packages), @js($shipmentCosts), {{ $document->total_gross_weight ?? 0 }})">
        <form method="POST" action="{{ route('documents.update', $document) }}">
            @csrf
            @method('PUT')

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

                    <!-- Main Document Details Form (8 Cols) -->
                    <div class="lg:col-span-8 space-y-6">

                        <!-- Step 1: Identification & Classification -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-5">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">1</span>
                                    Document Classification
                                </h3>
                                <span class="text-xs text-gray-400 font-mono">Current Version: {{ $document->current_version }}</span>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Number <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text"
                                           name="document_number"
                                           value="{{ old('document_number', $document->document_number) }}"
                                           required
                                           class="w-full text-base font-mono uppercase font-semibold rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Type <span class="text-red-500">*</span>
                                    </label>
                                    <select name="document_type"
                                            x-model="documentType"
                                            @change="loadChecklistsForType(documentType)"
                                            required
                                            class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2.5">
                                        @foreach($types as $key => $label)
                                            <option value="{{ $key }}" {{ $document->document_type === $key ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Document Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date" name="document_date" value="{{ old('document_date', $document->document_date ? $document->document_date->format('Y-m-d') : '') }}" required class="w-full text-sm rounded-lg border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Currency <span class="text-red-500">*</span>
                                    </label>
                                    <select name="currency" x-model="currency" required class="w-full text-sm font-semibold rounded-lg border-gray-300">
                                        <option value="USD">USD ($)</option>
                                        <option value="AED">AED (AED)</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Status
                                    </label>
                                    <select name="status" class="w-full text-sm rounded-lg border-gray-300">
                                        <option value="draft" {{ $document->status === 'draft' ? 'selected' : '' }}>Draft</option>
                                        <option value="active" {{ $document->status === 'active' ? 'selected' : '' }}>Active / Issued</option>
                                        <option value="final" {{ $document->status === 'final' ? 'selected' : '' }}>Finalized</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: Customer / Company Details -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="border-b border-gray-100 pb-3">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                    <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">2</span>
                                    Company & Recipient Details
                                </h3>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Company Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="company_name" value="{{ old('company_name', $document->company_name) }}" required class="w-full text-sm rounded-lg border-gray-300">
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Country <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="country" value="{{ old('country', $document->country) }}" required class="w-full text-sm rounded-lg border-gray-300">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Address Needed
                                    </label>
                                    <textarea name="address" rows="3" class="w-full text-sm rounded-lg border-gray-300">{{ old('address', $document->address) }}</textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Other Contact Details
                                    </label>
                                    <textarea name="contact_details" rows="3" class="w-full text-sm rounded-lg border-gray-300">{{ old('contact_details', $document->contact_details) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Line Items -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">3</span>
                                        Line Items
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Edit, add, or remove line items. Line total and subtotal auto-compute in real time.</p>
                                </div>
                                <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Line Item
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3 py-2 text-left w-32">Item Code</th>
                                            <th class="px-3 py-2 text-left">Description</th>
                                            <th class="px-3 py-2 text-right w-24">Unit Amount</th>
                                            <th class="px-3 py-2 text-right w-28">Unit Price (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2 text-right w-32">Total Amount</th>
                                            <th class="px-2 py-2 text-center w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2">
                                                    <input type="text" :name="`items[${index}][item_code]`" x-model="item.item_code" placeholder="SKU-101" required class="w-full text-xs font-mono font-semibold rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="text" :name="`items[${index}][description]`" x-model="item.description" placeholder="Description" class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.001" min="0.001" :name="`items[${index}][unit_amount]`" x-model.number="item.unit_amount" @input="recalcItem(item)" required class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model.number="item.unit_price" @input="recalcItem(item)" required class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2 text-right font-mono font-bold text-gray-800">
                                                    <span x-text="currency"></span> <span x-text="formatNumber(item.total_amount)"></span>
                                                </td>
                                                <td class="px-2 py-2 text-center">
                                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1" class="text-red-400 hover:text-red-600 transition p-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Weights & Subtotal Bar -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-4 border-t border-gray-100 bg-slate-50 p-4 rounded-lg">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Net Weight (kg)
                                    </label>
                                    <input type="number" step="0.001" min="0" name="total_net_weight" value="{{ old('total_net_weight', $document->total_net_weight) }}" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Gross Weight (kg)
                                    </label>
                                    <input type="number" step="0.001" min="0" name="total_gross_weight" value="{{ old('total_gross_weight', $document->total_gross_weight) }}" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div class="text-right flex flex-col justify-center">
                                    <span class="text-xs uppercase tracking-wider font-semibold text-gray-500">Calculated Subtotal</span>
                                    <span class="text-xl font-mono font-extrabold text-indigo-700">
                                        <span x-text="currency"></span> <span x-text="formatNumber(subtotal)"></span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Step 4: Package Dimensions & Diameters Breakdown -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">4</span>
                                        Package Dimensions & Diameter Breakdown
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Specify dimensions for multiple packages. Supports standard rectangular (L × W × H) or cylindrical (Diameter × Height) packaging.
                                    </p>
                                </div>
                                <button type="button" @click="addPackage()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                    <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                    Add Package Type
                                </button>
                            </div>

                            <!-- Package Rows Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-3 py-2 text-left w-32">Package Type</th>
                                            <th class="px-3 py-2 text-left w-28">Type</th>
                                            <th class="px-3 py-2 text-left">Dimensions (cm)</th>
                                            <th class="px-3 py-2 text-right w-20">Qty (Pkgs)</th>
                                            <th class="px-3 py-2 text-right w-28">Weight/Pkg (kg)</th>
                                            <th class="px-3 py-2 text-right w-28">Vol. Wt (kg)</th>
                                            <th class="px-3 py-2 text-right w-24">CBM (m³)</th>
                                            <th class="px-2 py-2 text-center w-10"></th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(pkg, pIndex) in packages" :key="pIndex">
                                            <tr class="hover:bg-slate-50">
                                                <td class="px-3 py-2">
                                                    <select :name="`packages[${pIndex}][package_type]`" x-model="pkg.package_type" class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                        <option value="Carton">Carton / Box</option>
                                                        <option value="Wooden Crate">Wooden Crate</option>
                                                        <option value="Pallet">Pallet</option>
                                                        <option value="Drum">Drum / Cylinder</option>
                                                        <option value="Roll">Roll</option>
                                                        <option value="Bundle">Bundle</option>
                                                    </select>
                                                </td>

                                                <td class="px-3 py-2">
                                                    <select :name="`packages[${pIndex}][dimension_type]`" x-model="pkg.dimension_type" @change="recalcPackage(pkg)" class="w-full text-xs font-semibold rounded border-gray-300 py-1.5 px-2 text-indigo-700 bg-indigo-50/50">
                                                        <option value="standard">Box (L×W×H)</option>
                                                        <option value="diameter">Cylinder (Ø×H)</option>
                                                    </select>
                                                </td>

                                                <td class="px-3 py-2">
                                                    <template x-if="pkg.dimension_type === 'standard'">
                                                        <div class="flex items-center space-x-1 font-mono">
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][length_cm]`" x-model.number="pkg.length_cm" @input="recalcPackage(pkg)" placeholder="L" class="w-16 text-xs text-right rounded border-gray-300 py-1 px-1.5">
                                                            <span class="text-gray-400">×</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][width_cm]`" x-model.number="pkg.width_cm" @input="recalcPackage(pkg)" placeholder="W" class="w-16 text-xs text-right rounded border-gray-300 py-1 px-1.5">
                                                            <span class="text-gray-400">×</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][height_cm]`" x-model.number="pkg.height_cm" @input="recalcPackage(pkg)" placeholder="H" class="w-16 text-xs text-right rounded border-gray-300 py-1 px-1.5">
                                                            <span class="text-[11px] text-gray-400">cm</span>
                                                        </div>
                                                    </template>

                                                    <template x-if="pkg.dimension_type === 'diameter'">
                                                        <div class="flex items-center space-x-1 font-mono">
                                                            <span class="text-xs text-indigo-600 font-bold">Ø</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][diameter_cm]`" x-model.number="pkg.diameter_cm" @input="recalcPackage(pkg)" placeholder="Dia" class="w-20 text-xs text-right rounded border-gray-300 py-1 px-1.5" title="Diameter in cm">
                                                            <span class="text-gray-400">×</span>
                                                            <input type="number" step="0.1" min="0" :name="`packages[${pIndex}][height_cm]`" x-model.number="pkg.height_cm" @input="recalcPackage(pkg)" placeholder="H" class="w-20 text-xs text-right rounded border-gray-300 py-1 px-1.5" title="Height in cm">
                                                            <span class="text-[11px] text-gray-400">cm</span>
                                                        </div>
                                                    </template>
                                                </td>

                                                <td class="px-3 py-2">
                                                    <input type="number" min="1" :name="`packages[${pIndex}][quantity]`" x-model.number="pkg.quantity" @input="recalcPackage(pkg)" required class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>

                                                <td class="px-3 py-2">
                                                    <input type="number" step="0.001" min="0" :name="`packages[${pIndex}][gross_weight_per_pkg_kg]`" x-model.number="pkg.gross_weight_per_pkg_kg" @input="recalcPackage(pkg)" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                </td>

                                                <td class="px-3 py-2 text-right font-mono font-semibold text-gray-700">
                                                    <span x-text="pkg.volumetric_weight_kg ? Number(pkg.volumetric_weight_kg).toFixed(2) : '0.00'"></span> kg
                                                </td>

                                                <td class="px-3 py-2 text-right font-mono text-gray-600">
                                                    <span x-text="pkg.cbm ? Number(pkg.cbm).toFixed(3) : '0.000'"></span> m³
                                                </td>

                                                <td class="px-2 py-2 text-center">
                                                    <button type="button" @click="removePackage(pIndex)" x-show="packages.length > 1" class="text-red-400 hover:text-red-600 transition p-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Package Aggregation Bar -->
                            <div class="pt-3 border-t border-gray-100 bg-slate-50 p-4 rounded-lg flex flex-wrap items-center justify-between gap-4 text-xs">
                                <div class="flex items-center space-x-6">
                                    <div>
                                        <span class="text-gray-500 uppercase tracking-wider font-semibold block text-[10px]">Total Packages</span>
                                        <span class="text-base font-bold font-mono text-gray-900" x-text="totalPackagesCount"></span> pkgs
                                    </div>
                                    <div>
                                        <span class="text-gray-500 uppercase tracking-wider font-semibold block text-[10px]">Total Package Gross Wt</span>
                                        <span class="text-base font-bold font-mono text-gray-900" x-text="totalPackageGrossWeight.toFixed(2)"></span> kg
                                    </div>
                                    <div>
                                        <span class="text-indigo-600 uppercase tracking-wider font-bold block text-[10px]">Total Volumetric Wt</span>
                                        <span class="text-base font-extrabold font-mono text-indigo-700" x-text="totalVolumetricWeight.toFixed(2)"></span> kg
                                    </div>
                                    <div>
                                        <span class="text-emerald-600 uppercase tracking-wider font-bold block text-[10px]">Total Volume (CBM)</span>
                                        <span class="text-base font-extrabold font-mono text-emerald-700" x-text="totalCbm.toFixed(3)"></span> m³
                                    </div>
                                </div>

                                <button type="button" @click="syncWeightFromPackages()" x-show="totalPackageGrossWeight > 0" class="inline-flex items-center px-2.5 py-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-800 rounded font-semibold text-xs transition">
                                    <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    Set as Total Gross Weight
                                </button>
                            </div>
                        </div>

                        <!-- Step 5: Shipment Method Costs with Rate / kg -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">5</span>
                                        Shipment Method Costs & Rate / KG
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Air freight & courier charges with rate per kg ($/kg or AED/kg).
                                    </p>
                                </div>
                                <div class="text-right">
                                    <span class="text-[11px] text-gray-500 block">Chargeable Wt for Air/DHL:</span>
                                    <span class="font-mono font-bold text-sm text-indigo-700" x-text="`${chargeableWeight.toFixed(2)} kg`"></span>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-4 py-2.5 text-left w-36">Carrier Method</th>
                                            <th class="px-3 py-2.5 text-right w-28">Checked Wt (kg)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Rate / kg (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2.5 text-right w-32">System Amount (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2.5 text-right w-28">Added Amount (<span x-text="currency"></span>)</th>
                                            <th class="px-3 py-2.5 text-right w-36">Given Amount (<span x-text="currency"></span>)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <!-- DHL -->
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-amber-700">DHL Express</td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[dhl][checked_weight]" x-model.number="carriers.dhl.checked_weight" @input="recalcCarrier('dhl')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][rate_per_kg]" x-model.number="carriers.dhl.rate_per_kg" @input="recalcCarrier('dhl')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][system_amount]" x-model.number="carriers.dhl.system_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][added_amount]" x-model.number="carriers.dhl.added_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[dhl][given_amount]" x-model.number="carriers.dhl.given_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                        </tr>

                                        <!-- Air Freight -->
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-blue-700">Air Freight</td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[air_freight][checked_weight]" x-model.number="carriers.air_freight.checked_weight" @input="recalcCarrier('air_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][rate_per_kg]" x-model.number="carriers.air_freight.rate_per_kg" @input="recalcCarrier('air_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][system_amount]" x-model.number="carriers.air_freight.system_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][added_amount]" x-model.number="carriers.air_freight.added_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][given_amount]" x-model.number="carriers.air_freight.given_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                        </tr>

                                        <!-- Sea Freight -->
                                        <tr>
                                            <td class="px-4 py-2.5 font-bold text-emerald-700">Sea Freight</td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.001" min="0" name="shipment_costs[sea_freight][checked_weight]" x-model.number="carriers.sea_freight.checked_weight" @input="recalcCarrier('sea_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][rate_per_kg]" x-model.number="carriers.sea_freight.rate_per_kg" @input="recalcCarrier('sea_freight')" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][system_amount]" x-model.number="carriers.sea_freight.system_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][added_amount]" x-model.number="carriers.sea_freight.added_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                            <td class="px-3 py-2.5">
                                                <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][given_amount]" x-model.number="carriers.sea_freight.given_amount" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Step 5: Version Creation & Notes -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="p-4 bg-indigo-50/70 border border-indigo-200 rounded-xl space-y-3">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" name="create_new_version" value="1" checked class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                    <div>
                                        <span class="text-sm font-bold text-indigo-900">Create New Version Snapshot (Version {{ $document->current_version + 1 }})</span>
                                        <p class="text-xs text-indigo-700">Recommended. Allows tracking changes and one-click restoring if items are added or removed.</p>
                                    </div>
                                </label>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Change Summary / Version Notes</label>
                                    <input type="text" name="change_summary" placeholder="e.g. Added item SKU-99, updated freight rates" class="w-full text-xs rounded-lg border-gray-300">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center pt-2">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Notes / Remarks
                                    </label>
                                    <textarea name="notes" rows="3" class="w-full text-sm rounded-lg border-gray-300">{{ old('notes', $document->notes) }}</textarea>
                                </div>

                                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-right space-y-2">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                        Final Total (<span x-text="currency"></span>)
                                    </label>
                                    <div class="flex items-center justify-end space-x-2">
                                        <span class="text-sm font-mono font-bold text-gray-500" x-text="currency"></span>
                                        <input type="number" step="0.01" min="0" name="final_total" x-model.number="finalTotal" class="w-48 text-right font-mono text-2xl font-black text-indigo-900 rounded-lg border-gray-300">
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- Right Column: Verification Checklist Panel (4 Cols, Sticky) -->
                    <div class="lg:col-span-4 sticky top-6 space-y-6">

                        <!-- Interactive Session Checklist Drawer -->
                        <div class="bg-white rounded-xl shadow-md border-2 border-indigo-100 p-5 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div class="flex items-center space-x-2">
                                    <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                    </div>
                                    <div>
                                        <h4 class="font-bold text-sm text-gray-900">Verification Checklist</h4>
                                        <p class="text-[11px] text-gray-500" x-text="checklistHeading"></p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold font-mono"
                                      :class="allChecked ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                                      x-text="`${checkedCount} / ${checklists.length}`">
                                </span>
                            </div>

                            <div class="text-[11px] bg-slate-50 text-slate-600 p-2.5 rounded-lg border border-slate-200">
                                Check off verified items to ensure document correctness before saving.
                            </div>

                            <!-- Progress Bar -->
                            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-300"
                                     :style="`width: ${checklists.length > 0 ? (checkedCount / checklists.length) * 100 : 0}%`">
                                </div>
                            </div>

                            <!-- Checklist Items -->
                            <div class="space-y-2 max-h-[380px] overflow-y-auto pr-1">
                                <template x-for="(item, idx) in checklists" :key="item.id || idx">
                                    <label class="flex items-start space-x-3 p-2.5 rounded-lg border cursor-pointer transition select-none"
                                           :class="isItemChecked(item.id) ? 'bg-emerald-50/60 border-emerald-200' : 'bg-white border-gray-200 hover:bg-slate-50'">
                                        <input type="checkbox"
                                               class="mt-0.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                                               :checked="isItemChecked(item.id)"
                                               @change="toggleCheckItem(item.id)">
                                        <div class="flex-1 text-xs">
                                            <div class="font-medium text-gray-800"
                                                 :class="{'line-through text-gray-400': isItemChecked(item.id)}"
                                                 x-text="item.item_text"></div>
                                            <div class="text-[10px] text-gray-500 mt-0.5" x-text="item.hint" x-show="item.hint"></div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <!-- Action Card -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                            <button type="submit" class="w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold text-sm rounded-lg shadow-sm flex items-center justify-center space-x-2 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                <span>Save & Release Lock</span>
                            </button>
                            <a href="{{ route('documents.show', $document) }}" class="block text-center py-2 text-xs font-semibold text-gray-500 hover:text-gray-700 transition">
                                Cancel & Discard
                            </a>
                        </div>

                    </div>

                </div>
            </div>
        </form>
    </div>

    <!-- Alpine.js Document Editor Component -->
    <script>
        function documentEditor(initialItems, initialCurrency, initialType, initialPackages, initialCarriers, initialGrossWeight) {
            return {
                documentType: initialType || '',
                currency: initialCurrency || 'USD',
                subtotal: 0,
                finalTotal: {{ $document->final_total ?? 0 }},
                grossWeight: initialGrossWeight || null,
                checklists: [],
                checkedItems: {},
                docNumber: '{{ $document->document_number }}',

                items: initialItems && initialItems.length > 0 ? initialItems.map(it => ({
                    item_code: it.item_code,
                    description: it.description || '',
                    unit_amount: parseFloat(it.unit_amount) || 1,
                    unit_price: parseFloat(it.unit_price) || 0,
                    total_amount: parseFloat(it.total_amount) || 0,
                })) : [
                    { item_code: '', description: '', unit_amount: 1, unit_price: 0, total_amount: 0 }
                ],

                packages: initialPackages && initialPackages.length > 0 ? initialPackages.map(p => ({
                    package_type: p.package_type || 'Carton',
                    dimension_type: p.dimension_type || 'standard',
                    length_cm: p.length_cm ? parseFloat(p.length_cm) : null,
                    width_cm: p.width_cm ? parseFloat(p.width_cm) : null,
                    height_cm: p.height_cm ? parseFloat(p.height_cm) : null,
                    diameter_cm: p.diameter_cm ? parseFloat(p.diameter_cm) : null,
                    quantity: parseInt(p.quantity) || 1,
                    gross_weight_per_pkg_kg: p.gross_weight_per_pkg_kg ? parseFloat(p.gross_weight_per_pkg_kg) : null,
                    volumetric_weight_kg: parseFloat(p.volumetric_weight_kg) || 0,
                    cbm: parseFloat(p.cbm) || 0
                })) : [
                    { package_type: 'Carton', dimension_type: 'standard', length_cm: null, width_cm: null, height_cm: null, diameter_cm: null, quantity: 1, gross_weight_per_pkg_kg: null, volumetric_weight_kg: 0, cbm: 0 }
                ],

                carriers: {
                    dhl: {
                        checked_weight: initialCarriers?.dhl?.checked_weight ? parseFloat(initialCarriers.dhl.checked_weight) : null,
                        rate_per_kg: initialCarriers?.dhl?.rate_per_kg ? parseFloat(initialCarriers.dhl.rate_per_kg) : null,
                        system_amount: initialCarriers?.dhl?.system_amount ? parseFloat(initialCarriers.dhl.system_amount) : null,
                        added_amount: initialCarriers?.dhl?.added_amount ? parseFloat(initialCarriers.dhl.added_amount) : null,
                        given_amount: initialCarriers?.dhl?.given_amount ? parseFloat(initialCarriers.dhl.given_amount) : null
                    },
                    air_freight: {
                        checked_weight: initialCarriers?.air_freight?.checked_weight ? parseFloat(initialCarriers.air_freight.checked_weight) : null,
                        rate_per_kg: initialCarriers?.air_freight?.rate_per_kg ? parseFloat(initialCarriers.air_freight.rate_per_kg) : null,
                        system_amount: initialCarriers?.air_freight?.system_amount ? parseFloat(initialCarriers.air_freight.system_amount) : null,
                        added_amount: initialCarriers?.air_freight?.added_amount ? parseFloat(initialCarriers.air_freight.added_amount) : null,
                        given_amount: initialCarriers?.air_freight?.given_amount ? parseFloat(initialCarriers.air_freight.given_amount) : null
                    },
                    sea_freight: {
                        checked_weight: initialCarriers?.sea_freight?.checked_weight ? parseFloat(initialCarriers.sea_freight.checked_weight) : null,
                        rate_per_kg: initialCarriers?.sea_freight?.rate_per_kg ? parseFloat(initialCarriers.sea_freight.rate_per_kg) : null,
                        system_amount: initialCarriers?.sea_freight?.system_amount ? parseFloat(initialCarriers.sea_freight.system_amount) : null,
                        added_amount: initialCarriers?.sea_freight?.added_amount ? parseFloat(initialCarriers.sea_freight.added_amount) : null,
                        given_amount: initialCarriers?.sea_freight?.given_amount ? parseFloat(initialCarriers.sea_freight.given_amount) : null
                    }
                },

                get totalPackagesCount() {
                    return this.packages.reduce((sum, p) => sum + (parseInt(p.quantity) || 0), 0);
                },

                get totalPackageGrossWeight() {
                    return this.packages.reduce((sum, p) => {
                        const wt = parseFloat(p.gross_weight_per_pkg_kg) || 0;
                        const qty = parseInt(p.quantity) || 1;
                        return sum + (wt * qty);
                    }, 0);
                },

                get totalVolumetricWeight() {
                    return this.packages.reduce((sum, p) => sum + (parseFloat(p.volumetric_weight_kg) || 0), 0);
                },

                get totalCbm() {
                    return this.packages.reduce((sum, p) => sum + (parseFloat(p.cbm) || 0), 0);
                },

                get chargeableWeight() {
                    const actual = parseFloat(this.grossWeight) || 0;
                    return Math.max(actual, this.totalVolumetricWeight);
                },

                addPackage() {
                    this.packages.push({
                        package_type: 'Carton',
                        dimension_type: 'standard',
                        length_cm: null,
                        width_cm: null,
                        height_cm: null,
                        diameter_cm: null,
                        quantity: 1,
                        gross_weight_per_pkg_kg: null,
                        volumetric_weight_kg: 0,
                        cbm: 0
                    });
                },

                removePackage(index) {
                    if (this.packages.length > 1) {
                        this.packages.splice(index, 1);
                        this.recalcAllCarriers();
                    }
                },

                recalcPackage(pkg) {
                    const qty = Math.max(1, parseInt(pkg.quantity) || 1);
                    const h = parseFloat(pkg.height_cm) || 0;

                    if (pkg.dimension_type === 'diameter') {
                        const dia = parseFloat(pkg.diameter_cm) || 0;
                        if (dia > 0 && h > 0) {
                            pkg.volumetric_weight_kg = Math.round(((dia * dia * h) / 5000) * qty * 1000) / 1000;
                            const r = dia / 2;
                            pkg.cbm = Math.round((Math.PI * r * r * h / 1000000) * qty * 10000) / 10000;
                        } else {
                            pkg.volumetric_weight_kg = 0;
                            pkg.cbm = 0;
                        }
                    } else {
                        const l = parseFloat(pkg.length_cm) || 0;
                        const w = parseFloat(pkg.width_cm) || 0;
                        if (l > 0 && w > 0 && h > 0) {
                            pkg.volumetric_weight_kg = Math.round(((l * w * h) / 5000) * qty * 1000) / 1000;
                            pkg.cbm = Math.round(((l * w * h) / 1000000) * qty * 10000) / 10000;
                        } else {
                            pkg.volumetric_weight_kg = 0;
                            pkg.cbm = 0;
                        }
                    }

                    this.recalcAllCarriers();
                },

                syncWeightFromPackages() {
                    if (this.totalPackageGrossWeight > 0) {
                        this.grossWeight = Math.round(this.totalPackageGrossWeight * 1000) / 1000;
                        this.recalcAllCarriers();
                    }
                },

                recalcCarrier(method) {
                    const c = this.carriers[method];
                    if (!c) return;
                    const rate = parseFloat(c.rate_per_kg);
                    if (rate > 0) {
                        const wt = parseFloat(c.checked_weight) || this.chargeableWeight;
                        c.system_amount = Math.round(wt * rate * 100) / 100;
                    }
                },

                recalcAllCarriers() {
                    ['dhl', 'air_freight', 'sea_freight'].forEach(m => this.recalcCarrier(m));
                },

                init() {
                    this.recalcTotals();
                    this.loadChecklistsForType(this.documentType);
                },

                get checklistHeading() {
                    return (this.documentType || '').replace('_', ' ').toUpperCase();
                },

                get checkedCount() {
                    let count = 0;
                    this.checklists.forEach(item => {
                        if (this.checkedItems[this.sessionKey(item.id)]) {
                            count++;
                        }
                    });
                    return count;
                },

                get allChecked() {
                    return this.checklists.length > 0 && this.checkedCount === this.checklists.length;
                },

                sessionKey(itemId) {
                    return `chk_${this.docNumber}_${itemId}`;
                },

                isItemChecked(itemId) {
                    return !!this.checkedItems[this.sessionKey(itemId)];
                },

                toggleCheckItem(itemId) {
                    const key = this.sessionKey(itemId);
                    const newVal = !this.checkedItems[key];
                    this.checkedItems[key] = newVal;
                    sessionStorage.setItem(key, newVal ? '1' : '0');
                },

                loadChecklistSession() {
                    this.checklists.forEach(item => {
                        const key = this.sessionKey(item.id);
                        const val = sessionStorage.getItem(key);
                        if (val === '1') {
                            this.checkedItems[key] = true;
                        }
                    });
                },

                async loadChecklistsForType(type) {
                    if (!type) return;
                    try {
                        const response = await fetch(`/api/checklists/${type}`);
                        const data = await response.json();
                        this.checklists = data.items || [];
                        this.loadChecklistSession();
                    } catch (e) {
                        console.error('Checklist error', e);
                    }
                },

                addItem() {
                    this.items.push({
                        item_code: '',
                        description: '',
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0
                    });
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                        this.recalcTotals();
                    }
                },

                recalcItem(item) {
                    const qty = parseFloat(item.unit_amount) || 0;
                    const price = parseFloat(item.unit_price) || 0;
                    item.total_amount = Math.round(qty * price * 100) / 100;
                    this.recalcTotals();
                },

                recalcTotals() {
                    let sum = 0;
                    this.items.forEach(it => {
                        sum += parseFloat(it.total_amount) || 0;
                    });
                    this.subtotal = Math.round(sum * 100) / 100;
                    if (!this.finalTotal || this.finalTotal === 0) {
                        this.finalTotal = this.subtotal;
                    }
                },

                formatNumber(val) {
                    return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            };
        }
    </script>
</x-app-layout>
