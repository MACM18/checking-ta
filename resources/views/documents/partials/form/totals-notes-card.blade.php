@php
    $isEdit = isset($document);
@endphp

<!-- Step 6: Final Total & Notes -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
    @if($isEdit)
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
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center {{ $isEdit ? 'pt-2' : '' }}">
        <div>
            <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                {{ $isEdit ? 'Notes / Remarks' : 'Notes / Terms / Special Instructions' }}
            </label>
            <textarea name="notes"
                      rows="3"
                      placeholder="Payment terms, delivery schedule, bank details..."
                      class="w-full text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $isEdit ? $document->notes : '') }}</textarea>
        </div>

        <div x-show="!isWeightOnly && !isQuantityOnly" class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100 text-right space-y-2">
            <label class="block text-xs font-bold text-indigo-900 uppercase tracking-wider">
                Final Total Amount (<span x-text="currency"></span>)
            </label>
            <div class="flex items-center justify-end space-x-2">
                <span class="text-sm font-mono font-bold text-gray-500" x-text="currency"></span>
                <input type="number"
                       step="0.01"
                       name="final_total"
                       x-model.number="finalTotal"
                       :disabled="isWeightOnly || isQuantityOnly"
                       class="w-48 text-right font-mono text-2xl font-black text-indigo-900 rounded-lg border-indigo-200 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
            </div>
            <div x-show="selectedCarrier && carriers[selectedCarrier]" class="text-xs text-indigo-700 font-medium">
                Subtotal: <span class="font-mono" x-text="`${currency} ${formatNumber(subtotal)}`"></span>
                + Freight (<span class="font-bold uppercase" x-text="selectedCarrier === 'dhl' ? 'DHL Express' : (selectedCarrier === 'air_freight' ? 'Air Freight' : 'Sea Freight')"></span>): 
                <span class="font-mono font-bold" x-text="`${currency} ${formatNumber(carriers[selectedCarrier].given_amount || carriers[selectedCarrier].system_amount || 0)}`"></span>
            </div>
            <p class="text-[11px] text-indigo-600">Defaults to item sum plus applied freight. Can be manually adjusted.</p>
        </div>

        <div x-show="isWeightOnly" class="bg-slate-50 p-4 rounded-xl border border-slate-200 text-right space-y-2">
            <input type="hidden" name="final_total" value="0" :disabled="!isWeightOnly">
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-200 text-slate-700">
                Non-Commercial / No Financial Total
            </span>
            <p class="text-xs text-gray-600 font-medium">
                This document (<span class="font-bold uppercase text-indigo-700" x-text="documentType"></span>) only tracks weights, dimensions, and line items without financial pricing.
            </p>
        </div>

        <div x-show="isQuantityOnly" class="bg-purple-50/80 p-4 rounded-xl border border-purple-200 text-right space-y-2">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-purple-200 text-purple-800">
                    <span x-text="documentType === 'supplier_order' ? 'Supplier Order Sheet' : 'Factory Invoice'"></span>
                </span>
                <span class="text-xs font-mono font-bold text-purple-900">Total: <span x-text="formattedTotalQuantity"></span> units</span>
            </div>
            <div x-show="finalTotal > 0" class="pt-2 border-t border-purple-200/80">
                <label class="block text-xs font-bold text-purple-950 uppercase tracking-wider mb-1">
                    Optional Final Total (<span x-text="currency"></span>)
                </label>
                <div class="flex items-center justify-end space-x-2">
                    <span class="text-sm font-mono font-bold text-gray-500" x-text="currency"></span>
                    <input type="number" step="0.01" name="final_total" x-model.number="finalTotal" :disabled="isWeightOnly" class="w-48 text-right font-mono text-xl font-black text-purple-950 rounded-lg border-purple-300">
                </div>
                <p class="text-[11px] text-purple-700 mt-1">Calculated from optional unit prices entered on line items.</p>
            </div>
            <div x-show="!finalTotal || finalTotal <= 0">
                <input type="hidden" name="final_total" value="0" :disabled="!isQuantityOnly || finalTotal > 0">
                <p class="text-xs text-purple-900 font-medium">
                    Pricing is optional. Leaving unit prices empty tracks this order purely by quantity.
                </p>
            </div>
        </div>
    </div>
</div>
