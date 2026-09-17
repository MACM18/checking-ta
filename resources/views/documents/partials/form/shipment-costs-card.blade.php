<!-- Step 5: Shipment Method Costs (DHL, Air freight, Sea freight) with Rate / kg (Hidden for Packing List, Reserve, and Supplier Orders) -->
<div x-show="!isWeightOnly && !isQuantityOnly" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
    <input type="hidden" name="selected_shipment_method" :value="selectedCarrier">
    <div class="border-b border-gray-100 pb-3 flex items-center justify-between">
        <div>
            <h3 class="font-bold text-lg text-gray-800 flex items-center">
                <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">5</span>
                Shipment Method Costs Comparison & Rate / KG
            </h3>
            <p class="text-xs text-gray-500 mt-0.5">
                Track air freight or DHL rate per kg ($/kg or AED/kg). Click "+ Apply" to include a shipping carrier in the Final Total.
            </p>
        </div>

        <div class="text-right">
            <span class="text-[11px] text-gray-500 block">Chargeable Wt for Air/DHL:</span>
            <span class="font-mono font-bold text-sm text-indigo-700" x-text="`${chargeableWeight.toFixed(2)} kg`"></span>
            <span class="text-[10px] text-gray-400 block">(Actual Gross Weight)</span>
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
                    <th class="px-3 py-2.5 text-right w-40">Given Amount & Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <!-- DHL -->
                <tr class="hover:bg-amber-50/40" :class="selectedCarrier === 'dhl' ? 'bg-amber-50/60' : ''">
                    <td class="px-4 py-2.5 font-bold text-amber-700 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-amber-500 me-2"></span>
                        DHL Express
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.001" min="0" name="shipment_costs[dhl][checked_weight]" x-model.number="carriers.dhl.checked_weight" @input="recalcCarrier('dhl')" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[dhl][rate_per_kg]" x-model.number="carriers.dhl.rate_per_kg" @input="recalcCarrier('dhl')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[dhl][system_amount]" x-model.number="carriers.dhl.system_amount" @input="recalcCarrier('dhl', false, true)" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[dhl][added_amount]" x-model.number="carriers.dhl.added_amount" @input="recalcCarrier('dhl')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center space-x-1.5">
                            <input type="number" step="0.01" min="0" name="shipment_costs[dhl][given_amount]" x-model.number="carriers.dhl.given_amount" @input="recalcCarrier('dhl', true)" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                            <button type="button" @click="toggleCarrierFreight('dhl')" :class="selectedCarrier === 'dhl' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-amber-100 hover:bg-amber-200 text-amber-900 border border-amber-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'dhl' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                <span x-text="selectedCarrier === 'dhl' ? '✓ Applied' : '+ Apply'"></span>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Air Freight -->
                <tr class="hover:bg-blue-50/40" :class="selectedCarrier === 'air_freight' ? 'bg-blue-50/60' : ''">
                    <td class="px-4 py-2.5 font-bold text-blue-700 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-blue-500 me-2"></span>
                        Air Freight
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.001" min="0" name="shipment_costs[air_freight][checked_weight]" x-model.number="carriers.air_freight.checked_weight" @input="recalcCarrier('air_freight')" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][rate_per_kg]" x-model.number="carriers.air_freight.rate_per_kg" @input="recalcCarrier('air_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][system_amount]" x-model.number="carriers.air_freight.system_amount" @input="recalcCarrier('air_freight', false, true)" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][added_amount]" x-model.number="carriers.air_freight.added_amount" @input="recalcCarrier('air_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center space-x-1.5">
                            <input type="number" step="0.01" min="0" name="shipment_costs[air_freight][given_amount]" x-model.number="carriers.air_freight.given_amount" @input="recalcCarrier('air_freight', true)" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                            <button type="button" @click="toggleCarrierFreight('air_freight')" :class="selectedCarrier === 'air_freight' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-blue-100 hover:bg-blue-200 text-blue-900 border border-blue-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'air_freight' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                <span x-text="selectedCarrier === 'air_freight' ? '✓ Applied' : '+ Apply'"></span>
                            </button>
                        </div>
                    </td>
                </tr>

                <!-- Sea Freight -->
                <tr class="hover:bg-emerald-50/40" :class="selectedCarrier === 'sea_freight' ? 'bg-emerald-50/60' : ''">
                    <td class="px-4 py-2.5 font-bold text-emerald-700 flex items-center">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 me-2"></span>
                        Sea Freight
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.001" min="0" name="shipment_costs[sea_freight][checked_weight]" x-model.number="carriers.sea_freight.checked_weight" @input="recalcCarrier('sea_freight')" placeholder="0.000" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][rate_per_kg]" x-model.number="carriers.sea_freight.rate_per_kg" @input="recalcCarrier('sea_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][system_amount]" x-model.number="carriers.sea_freight.system_amount" @input="recalcCarrier('sea_freight', false, true)" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 bg-slate-50">
                    </td>
                    <td class="px-3 py-2.5">
                        <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][added_amount]" x-model.number="carriers.sea_freight.added_amount" @input="recalcCarrier('sea_freight')" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                    </td>
                    <td class="px-3 py-2.5">
                        <div class="flex items-center space-x-1.5">
                            <input type="number" step="0.01" min="0" name="shipment_costs[sea_freight][given_amount]" x-model.number="carriers.sea_freight.given_amount" @input="recalcCarrier('sea_freight', true)" placeholder="0.00" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                            <button type="button" @click="toggleCarrierFreight('sea_freight')" :class="selectedCarrier === 'sea_freight' ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' : 'bg-emerald-100 hover:bg-emerald-200 text-emerald-900 border border-emerald-300'" class="text-[10px] whitespace-nowrap font-bold px-2 py-1 rounded transition cursor-pointer" :title="selectedCarrier === 'sea_freight' ? 'Freight included in total. Click to remove.' : 'Include this freight in Final Total'">
                                <span x-text="selectedCarrier === 'sea_freight' ? '✓ Applied' : '+ Apply'"></span>
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
