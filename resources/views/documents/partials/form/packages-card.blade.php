<!-- Step 4: Package Dimensions & Diameters Breakdown -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div>
            <h3 class="font-bold text-lg text-gray-800 flex items-center">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">4</span>
                Package Dimensions & Diameter Breakdown
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
                Specify dimensions for multiple packages. Supports rectangular (L × W × H) or cylindrical (Diameter × Height) packaging.
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
                    <th class="px-3 py-2 text-right w-24">CBM (m³)</th>
                    <th class="sticky right-0 z-20 bg-gray-50 px-2 py-2 text-center w-10 shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-200"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <template x-for="(pkg, pIndex) in packages" :key="pIndex">
                    <tr class="hover:bg-slate-50 group">
                        <!-- Package Type -->
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

                        <!-- Dimension Type (Standard vs Diameter) -->
                        <td class="px-3 py-2">
                            <select :name="`packages[${pIndex}][dimension_type]`" x-model="pkg.dimension_type" @change="recalcPackage(pkg)" class="w-full text-xs font-semibold rounded border-gray-300 py-1.5 px-2 text-indigo-700 bg-indigo-50/50">
                                <option value="standard">Box (L×W×H)</option>
                                <option value="diameter">Cylinder (Ø×H)</option>
                            </select>
                        </td>

                        <!-- Dimensions Inputs -->
                        <td class="px-3 py-2">
                            <!-- Standard Box Inputs -->
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

                            <!-- Cylinder / Diameter Inputs -->
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

                        <!-- Quantity (number of packages with these dimensions) -->
                        <td class="px-3 py-2">
                            <input type="number" min="1" :name="`packages[${pIndex}][quantity]`" x-model.number="pkg.quantity" @input="recalcPackage(pkg)" required autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" data-lpignore="true" data-1p-ignore="true" class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 px-2">
                        </td>

                        <!-- Gross Weight per Package -->
                        <td class="px-3 py-2">
                            <input type="number" step="0.001" min="0" :name="`packages[${pIndex}][gross_weight_per_pkg_kg]`" x-model.number="pkg.gross_weight_per_pkg_kg" @input="recalcPackage(pkg)" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                        </td>

                        <!-- CBM (computed) -->
                        <td class="px-3 py-2 text-right font-mono text-gray-600">
                            <span x-text="pkg.cbm ? pkg.cbm.toFixed(3) : '0.000'"></span> m³
                        </td>

                        <!-- Remove Row -->
                        <td class="sticky right-0 z-10 bg-white group-hover:bg-slate-50 transition px-2 py-2 text-center shadow-[-8px_0_12px_-4px_rgba(0,0,0,0.06)] border-l border-gray-100">
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
