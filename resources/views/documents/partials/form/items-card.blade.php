                        <!-- Step 3: Line Items (Item code, unit amount, unit price/weight, total amount/weight) -->
                        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-4">
                            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                                <div>
                                    <h3 class="font-bold text-lg text-gray-800 flex items-center">
                                        <span class="flex items-center justify-center w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold me-2">3</span>
                                        <span x-text="isQuantityOnly ? (documentType === 'supplier_order' ? 'Supplier Purchase Order Items (Quantity & Optional Prices)' : 'Factory Invoice / Receipt Items (Quantity & Optional Prices)') : (isWeightOnly ? (documentType === 'delivery_note' ? 'Delivery Note & Weight Breakdown' : (documentType === 'reserve' ? 'Warehouse Reserve & Weight Breakdown' : 'Packing List & Weight Breakdown')) : 'Document Line Items & Pricing')"></span>
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5" x-text="isQuantityOnly ? (documentType === 'supplier_order' ? 'Supplier order sheet: Item code, description, and quantity. Prices are optional.' : 'Factory invoice: Item code, description, quantity, optional prices, and related order sheet reference.') : (isWeightOnly ? 'Item code, description, quantity, unit net weight (kg), and calculated total net weight. Prices are omitted for weight-focused documents (packing lists, reserves, and delivery notes).' : 'Item code, description, quantity, unit price, discounts (-) and additions (+).')"></p>
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <div x-show="documentType === 'factory_invoice'" class="flex items-center space-x-1.5 bg-purple-50 border border-purple-200 rounded-lg p-1">
                                        <button type="button" @click="groupByOrderSheet = false" :class="!groupByOrderSheet ? 'bg-white shadow-2xs font-bold text-purple-900' : 'text-purple-600 hover:text-purple-800'" class="px-2.5 py-1 text-xs rounded-md transition flex items-center space-x-1">
                                            <span>📋 Flat List</span>
                                        </button>
                                        <button type="button" @click="groupByOrderSheet = true" :class="groupByOrderSheet ? 'bg-white shadow-2xs font-bold text-purple-900' : 'text-purple-600 hover:text-purple-800'" class="px-2.5 py-1 text-xs rounded-md transition flex items-center space-x-1">
                                            <span>📦 Grouped by Order Sheet</span>
                                            <span class="px-1.5 py-0.2 rounded-full text-[10px] bg-purple-200 text-purple-900 font-mono font-bold" x-text="orderSheetGroupList.length"></span>
                                        </button>
                                    </div>
                                    <template x-if="documentType !== 'factory_invoice' || !groupByOrderSheet">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" @click="addItem()" class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 rounded-lg text-xs font-bold transition">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Line Item
                                            </button>
                                            <button type="button" x-show="!isWeightOnly" @click="addDiscount()" class="inline-flex items-center px-3 py-1.5 bg-rose-50 text-rose-700 hover:bg-rose-100 rounded-lg text-xs font-bold transition" title="Add a discount line (% or fixed minus from total)">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                                Add Discount (-)
                                            </button>
                                            <button type="button" x-show="!isWeightOnly" @click="addTax()" class="inline-flex items-center px-3 py-1.5 bg-amber-50 text-amber-800 hover:bg-amber-100 rounded-lg text-xs font-bold transition" title="Add VAT or tax line (% or fixed plus to total)">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Tax / VAT (+)
                                            </button>
                                            <button type="button" x-show="!isWeightOnly" @click="addAddition()" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 rounded-lg text-xs font-bold transition" title="Add extra charge, freight, or surcharge line (plus to total)">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Addition (+)
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Factory Invoice: Order Sheet Selector / Adder Toolbar -->
                            <div x-show="documentType === 'factory_invoice' && groupByOrderSheet" class="p-4 bg-gradient-to-r from-purple-50 via-indigo-50/40 to-white rounded-xl border border-purple-200 shadow-2xs space-y-3">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                    <div>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-purple-900 flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                            <span>Add Order Sheet Group</span>
                                        </h4>
                                        <p class="text-[11px] text-gray-500 mt-0.5">
                                            Add an Order Sheet (e.g. <strong class="font-mono text-purple-800">B26001</strong>) to receive items against it. Select items to auto-fill quantities & prices, or edit manually.
                                        </p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="addDirectItem()" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-2xs transition">
                                            <svg class="w-3.5 h-3.5 me-1 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                            + Direct Item (No Order Sheet)
                                        </button>
                                    </div>
                                </div>

                                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 pt-1">
                                    <div class="flex-1 relative">
                                        <input type="text"
                                               x-model="newOrderSheetRef"
                                               @keydown.enter.prevent="addOrderSheetGroup(newOrderSheetRef)"
                                               list="orderSheetSelectionList"
                                               placeholder="Type or select Order Sheet code (e.g. B26001, B26002)..."
                                               class="w-full text-xs font-mono font-bold rounded-lg border-purple-200 py-2 px-3 focus:border-purple-500 focus:ring-purple-500 bg-white placeholder:text-gray-400">
                                        <datalist id="orderSheetSelectionList">
                                            @foreach($availableSourceDocs as $avail)
                                                @if($avail->document_type === 'supplier_order' || str_starts_with(strtoupper($avail->document_number), 'B'))
                                                    <option value="{{ $avail->document_number }}">
                                                        {{ $avail->document_number }} &mdash; {{ $avail->company_name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </datalist>
                                    </div>
                                    <button type="button"
                                            @click="addOrderSheetGroup(newOrderSheetRef)"
                                            :disabled="!newOrderSheetRef || !newOrderSheetRef.trim()"
                                            class="inline-flex items-center justify-center px-4 py-2 bg-purple-600 hover:bg-purple-700 disabled:opacity-50 text-white font-bold text-xs rounded-lg shadow-xs transition">
                                        <svg class="w-3.5 h-3.5 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        <span>Add Order Sheet Group</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Price Tracker Tier Selection Bar (Shown only when financial pricing applies) -->
                            <div x-show="!isWeightOnly" class="bg-slate-50 p-3.5 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                                <div class="flex flex-wrap items-center gap-3">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-gray-700 flex items-center text-xs">
                                            <svg class="w-4 h-4 text-indigo-600 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                                            Price List:
                                        </span>
                                        <select x-model="selectedPriceList" @change="onPriceTierChanged()" class="text-xs rounded-lg border-gray-300 py-1 px-2 font-semibold focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                                            <option value="">(All Price Lists)</option>
                                            <template x-for="list in filteredPriceLists" :key="list">
                                                <option :value="list" x-text="list"></option>
                                            </template>
                                        </select>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-gray-700 flex items-center text-xs">
                                            Price Label / Tier:
                                        </span>
                                        <select x-model="selectedPriceLabel" @change="onPriceTierChanged()" class="text-xs rounded-lg border-gray-300 py-1 px-2.5 font-bold focus:ring-indigo-500 focus:border-indigo-500 bg-white" :class="selectedPriceLabel ? 'text-indigo-700 font-extrabold ring-1 ring-indigo-500' : 'text-gray-600'">
                                            <option value="">-- No Auto-Pricing --</option>
                                            <template x-for="lbl in filteredPriceLabels" :key="lbl">
                                                <option :value="lbl" x-text="lbl"></option>
                                            </template>
                                        </select>
                                        <template x-if="latestPiDoc && latestPiDoc.price_label">
                                            <div class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-bold bg-amber-100 text-amber-950 border border-amber-300 shadow-2xs">
                                                <span>Customer's last PI used: <strong class="underline decoration-amber-400 font-extrabold text-black" x-text="latestPiDoc.price_label"></strong></span>
                                                <button type="button"
                                                        x-show="selectedPriceLabel !== latestPiDoc.price_label"
                                                        @click="applyLatestPiPriceLabel()"
                                                        class="ml-1.5 px-1.5 py-0.5 bg-amber-700 hover:bg-amber-800 text-white rounded text-[10px] font-extrabold shadow-2xs transition cursor-pointer"
                                                        title="Select this price label and reprice items">
                                                    Apply
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                    <template x-if="isAdditionalCurrency">
                                        <button type="button"
                                                @click="convertPricesToCurrency()"
                                                :disabled="isConvertingCurrency"
                                                class="inline-flex items-center px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold shadow-xs transition gap-1.5"
                                                :title="`Convert all USD base prices to ${currency}`">
                                            <svg class="w-3.5 h-3.5" :class="isConvertingCurrency ? 'animate-spin' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                            <span x-text="`Convert to ${currency}`"></span>
                                        </button>
                                    </template>
                                </div>

                                <div class="flex items-center space-x-2">
                                    <span class="text-[11px] text-gray-400 font-medium flex items-center" x-show="selectedPriceLabel || selectedPriceList">
                                        <span x-show="isRepricing" class="inline-flex items-center text-indigo-600 font-bold me-1.5 animate-pulse">
                                            <svg class="animate-spin -ml-1 mr-1 h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                            Updating prices...
                                        </span>
                                        <span x-show="!isRepricing">Auto-updates prices across all items on selection</span>
                                    </span>
                                </div>
                            </div>

                            <!-- Factory Invoice: Grouped by Order Sheet View -->
                            <template x-if="documentType === 'factory_invoice' && groupByOrderSheet">
                                <div class="space-y-5">
                                    <!-- Iteration through Order Sheet Groups -->
                                    <template x-for="grp in orderSheetGroupList" :key="grp.ref">
                                        <div class="bg-white rounded-xl border-2 border-purple-200/90 shadow-2xs overflow-hidden transition">
                                            <!-- Group Header Bar -->
                                            <div class="bg-gradient-to-r from-purple-100/90 via-purple-50 to-indigo-50/40 px-4 py-3 border-b border-purple-200 flex flex-wrap items-center justify-between gap-3">
                                                <div class="flex flex-wrap items-center gap-2.5">
                                                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-purple-600 text-white text-sm shadow-2xs font-bold">
                                                        📦
                                                    </span>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <span class="font-mono font-black text-sm text-purple-950" x-text="grp.ref"></span>
                                                            <template x-if="grp.data && grp.data.company_name">
                                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-200/80 text-purple-900" x-text="grp.data.company_name"></span>
                                                            </template>
                                                            <template x-if="grp.loading">
                                                                <span class="inline-flex items-center text-xs text-purple-600 font-medium">
                                                                    <svg class="animate-spin w-3 h-3 me-1 text-purple-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                                    Loading details...
                                                                </span>
                                                            </template>
                                                        </div>
                                                        <div class="text-[11px] text-gray-500 font-mono mt-0.5">
                                                            <span x-text="getGroupSummaryText(grp.ref)"></span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                            @click="addItemToGroup(grp.ref)"
                                                            class="inline-flex items-center px-2.5 py-1.5 bg-purple-600 hover:bg-purple-700 active:bg-purple-800 text-white text-xs font-bold rounded-lg shadow-2xs transition">
                                                        <svg class="w-3.5 h-3.5 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                        Add Item
                                                    </button>

                                                    <button type="button"
                                                            @click="addAllRemainingFromOrderSheet(grp.ref)"
                                                            class="inline-flex items-center px-2.5 py-1.5 bg-white border border-purple-300 hover:bg-purple-50 text-purple-800 text-xs font-bold rounded-lg shadow-2xs transition"
                                                            title="Add all remaining unfulfilled items from this order sheet">
                                                        <svg class="w-3.5 h-3.5 me-1 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                        Add All Pending Items
                                                    </button>

                                                    <button type="button"
                                                            @click="removeOrderSheetGroup(grp.ref)"
                                                            class="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition"
                                                            title="Remove this Order Sheet group and its items">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Group Items Table -->
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                                    <thead class="bg-gray-50/90 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                                                        <tr>
                                                            <th class="px-2 py-2 text-center w-12 text-gray-400">#</th>
                                                            <th class="px-3 py-2 text-left w-56">Item / Record Code</th>
                                                            <th class="px-3 py-2 text-left min-w-[200px]">Description</th>
                                                            <th class="px-3 py-2 text-right w-28">Received Qty</th>
                                                            <th class="px-3 py-2 text-right w-40">Unit Price (<span x-text="currency"></span>) <span class="text-[10px] font-normal text-gray-400 block -mt-0.5">(Optional)</span></th>
                                                            <th class="px-3 py-2 text-right w-32">Total Amount</th>
                                                            <th class="px-3 py-2 text-right w-28">Unit Wt (kg)</th>
                                                            <th class="px-3 py-2 text-right w-32">Total Wt (kg)</th>
                                                            <th class="px-2 py-2 text-center w-10"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <template x-for="entry in getItemsForGroup(grp.ref)" :key="entry.index">
                                                            <tr class="hover:bg-purple-50/30 transition">
                                                                <td class="px-2 py-2 text-center align-middle font-mono text-gray-400 text-[11px]" x-text="entry.index + 1"></td>
                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="hidden" :name="`items[${entry.index}][order_sheet_reference]`" :value="entry.item.order_sheet_reference">
                                                                    <input type="hidden" :name="`items[${entry.index}][price_list]`" :value="entry.item.price_list || ''">
                                                                    <input type="hidden" :name="`items[${entry.index}][is_fallback]`" :value="entry.item.is_fallback ? '1' : '0'">

                                                                    <div class="relative">
                                                                        <input type="text"
                                                                               :name="`items[${entry.index}][item_code]`"
                                                                               x-model="entry.item.item_code"
                                                                               :list="`os-sug-${grp.ref}-${entry.index}`"
                                                                               @input="onOrderSheetItemCodeSelected(entry.item, grp.ref)"
                                                                               @change="onOrderSheetItemCodeSelected(entry.item, grp.ref)"
                                                                               placeholder="Select or enter item..."
                                                                               required
                                                                               autocomplete="off"
                                                                               class="w-full text-xs font-mono font-bold rounded border-gray-300 py-1.5 px-2.5 focus:border-purple-500 focus:ring-purple-500">
                                                                        <datalist :id="`os-sug-${grp.ref}-${entry.index}`">
                                                                            <template x-for="sug in (orderSheetsData[grp.ref]?.items || [])" :key="sug.item_code">
                                                                                <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description} (Ordered: ${sug.ordered_qty ?? sug.unit_amount}, Remaining: ${sug.remaining_qty})`"></option>
                                                                            </template>
                                                                        </datalist>

                                                                        <template x-if="getRemainingQtyForGroupItem(grp.ref, entry.item.item_code)">
                                                                            <div class="mt-1 flex items-center gap-1.5">
                                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-purple-100 text-purple-900 border border-purple-200">
                                                                                    Ordered: <strong class="ml-0.5" x-text="getRemainingQtyForGroupItem(grp.ref, entry.item.item_code).ordered"></strong>
                                                                                </span>
                                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-mono bg-emerald-100 text-emerald-900 border border-emerald-200 font-bold">
                                                                                    Pending: <strong class="ml-0.5" x-text="getRemainingQtyForGroupItem(grp.ref, entry.item.item_code).remaining"></strong>
                                                                                </span>
                                                                            </div>
                                                                        </template>
                                                                    </div>
                                                                </td>

                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text"
                                                                           :name="`items[${entry.index}][description]`"
                                                                           x-model="entry.item.description"
                                                                           placeholder="Item description"
                                                                           class="w-full text-xs rounded border-gray-300 py-1.5 px-2 focus:border-purple-500 focus:ring-purple-500">
                                                                </td>

                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text"
                                                                           inputmode="decimal"
                                                                           :name="`items[${entry.index}][unit_amount]`"
                                                                           x-model="entry.item.unit_amount"
                                                                           @input="onQuantityInput(entry.item)"
                                                                           @focus="$event.target.select()"
                                                                           placeholder="Qty"
                                                                           class="w-full text-xs font-mono text-right font-bold rounded border-gray-300 py-1.5 px-2 focus:border-purple-500 focus:ring-purple-500 text-gray-900">
                                                                </td>

                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text"
                                                                           inputmode="decimal"
                                                                           :name="`items[${entry.index}][unit_price]`"
                                                                           x-model="entry.item.unit_price"
                                                                           @input="onUnitPriceInput(entry.item)"
                                                                           @focus="$event.target.select()"
                                                                           placeholder="0.00"
                                                                           class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 focus:border-purple-500 focus:ring-purple-500">
                                                                </td>

                                                                <td class="px-3 py-2 align-middle text-right font-mono font-bold text-gray-800">
                                                                    <input type="hidden" :name="`items[${entry.index}][total_amount]`" :value="entry.item.total_amount">
                                                                    <span x-text="entry.item.total_amount > 0 ? (currency + ' ' + formatNumber(entry.item.total_amount)) : '—'"></span>
                                                                </td>

                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text"
                                                                           inputmode="decimal"
                                                                           :name="`items[${entry.index}][unit_weight]`"
                                                                           x-model="entry.item.unit_weight"
                                                                           @input="onWeightInput(entry.item)"
                                                                           placeholder="0.000"
                                                                           class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                                </td>

                                                                <td class="px-3 py-2 align-middle text-right font-mono text-gray-600">
                                                                    <input type="hidden" :name="`items[${entry.index}][total_weight]`" :value="entry.item.total_weight">
                                                                    <span x-text="entry.item.total_weight > 0 ? (formatWeight(entry.item.total_weight) + ' kg') : '—'"></span>
                                                                </td>

                                                                <td class="px-2 py-2 text-center align-middle">
                                                                    <button type="button"
                                                                            @click="updateItemPrice(entry.item)"
                                                                            x-show="!isWeightOnly && !isQuantityOnly && !isAdjustment(entry.item)"
                                                                            :disabled="entry.item.isUpdatingPrice"
                                                                            class="inline-flex items-center gap-1 px-1.5 py-1 text-[10px] font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition disabled:opacity-50"
                                                                            title="Update this item's price">
                                                                        <svg class="w-3.5 h-3.5" :class="entry.item.isUpdatingPrice ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.5 15A7 7 0 0017.9 17.9L20 15M18.5 9A7 7 0 006.1 6.1L4 9"></path></svg>
                                                                        <span>Price</span>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>

                                            <template x-if="getItemsForGroup(grp.ref).length === 0">
                                                <div class="p-6 text-center text-gray-500 bg-gray-50/50">
                                                    <p class="text-xs">No items added under Order Sheet <strong x-text="grp.ref"></strong> yet.</p>
                                                    <div class="mt-2 flex items-center justify-center gap-2">
                                                        <button type="button" @click="addItemToGroup(grp.ref)" class="px-3 py-1 bg-purple-600 hover:bg-purple-700 text-white rounded text-xs font-bold shadow-2xs">
                                                            + Add Item
                                                        </button>
                                                        <button type="button" @click="addAllRemainingFromOrderSheet(grp.ref)" class="px-3 py-1 bg-white border border-purple-300 hover:bg-purple-50 text-purple-700 rounded text-xs font-bold shadow-2xs">
                                                            + Add All Pending Items
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Direct / Unassigned Items Card -->
                                    <template x-if="getItemsForGroup('').length > 0 || showDirectItems">
                                        <div class="bg-white rounded-xl border border-gray-200 shadow-2xs overflow-hidden">
                                            <div class="bg-gray-50 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                                                <div class="flex items-center gap-2">
                                                    <span class="w-6 h-6 rounded bg-gray-200 text-gray-700 flex items-center justify-center text-xs font-bold">📝</span>
                                                    <span class="font-bold text-xs text-gray-800">Direct / Unassigned Items (Not linked to any Order Sheet)</span>
                                                </div>
                                                <button type="button" @click="addDirectItem()" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-lg transition">
                                                    + Add Direct Item
                                                </button>
                                            </div>
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200 text-xs">
                                                    <thead class="bg-gray-50/90 text-gray-600 font-bold uppercase tracking-wider text-[11px]">
                                                        <tr>
                                                            <th class="px-2 py-2 text-center w-12 text-gray-400">#</th>
                                                            <th class="px-3 py-2 text-left w-56">Item / Record Code</th>
                                                            <th class="px-3 py-2 text-left min-w-[200px]">Description</th>
                                                            <th class="px-3 py-2 text-right w-28">Received Qty</th>
                                                            <th class="px-3 py-2 text-right w-40">Unit Price (<span x-text="currency"></span>)</th>
                                                            <th class="px-3 py-2 text-right w-32">Total Amount</th>
                                                            <th class="px-3 py-2 text-right w-28">Unit Wt (kg)</th>
                                                            <th class="px-3 py-2 text-right w-32">Total Wt (kg)</th>
                                                            <th class="px-2 py-2 text-center w-10"></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <template x-for="entry in getItemsForGroup('')" :key="entry.index">
                                                            <tr class="hover:bg-slate-50 transition">
                                                                <td class="px-2 py-2 text-center align-middle font-mono text-gray-400 text-[11px]" x-text="entry.index + 1"></td>
                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="hidden" :name="`items[${entry.index}][order_sheet_reference]`" value="">
                                                                    <input type="hidden" :name="`items[${entry.index}][price_list]`" :value="entry.item.price_list || ''">
                                                                    <input type="hidden" :name="`items[${entry.index}][is_fallback]`" :value="entry.item.is_fallback ? '1' : '0'">

                                                                    <input type="text"
                                                                           :name="`items[${entry.index}][item_code]`"
                                                                           x-model="entry.item.item_code"
                                                                           :list="`item-datalist-${entry.index}`"
                                                                           @input.debounce.250ms="onItemCodeInput(entry.item, entry.index)"
                                                                           @change="lookupItemPrice(entry.item)"
                                                                           placeholder="Item code..."
                                                                           required
                                                                           class="w-full text-xs font-mono font-bold rounded border-gray-300 py-1.5 px-2.5">
                                                                    <datalist :id="`item-datalist-${entry.index}`">
                                                                        <template x-for="sug in (itemSuggestions[entry.index] || [])" :key="sug.item_code">
                                                                            <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description}`"></option>
                                                                        </template>
                                                                    </datalist>
                                                                </td>
                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text" :name="`items[${entry.index}][description]`" x-model="entry.item.description" placeholder="Description" class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                                </td>
                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text" inputmode="decimal" :name="`items[${entry.index}][unit_amount]`" x-model="entry.item.unit_amount" @input="onQuantityInput(entry.item)" class="w-full text-xs font-mono text-right font-bold rounded border-gray-300 py-1.5 px-2">
                                                                </td>
                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text" inputmode="decimal" :name="`items[${entry.index}][unit_price]`" x-model="entry.item.unit_price" @input="onUnitPriceInput(entry.item)" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                                </td>
                                                                <td class="px-3 py-2 align-middle text-right font-mono font-bold text-gray-800">
                                                                    <input type="hidden" :name="`items[${entry.index}][total_amount]`" :value="entry.item.total_amount">
                                                                    <span x-text="entry.item.total_amount > 0 ? (currency + ' ' + formatNumber(entry.item.total_amount)) : '—'"></span>
                                                                </td>
                                                                <td class="px-3 py-2 align-middle">
                                                                    <input type="text" inputmode="decimal" :name="`items[${entry.index}][unit_weight]`" x-model="entry.item.unit_weight" @input="onWeightInput(entry.item)" class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                                </td>
                                                                <td class="px-3 py-2 align-middle text-right font-mono text-gray-600">
                                                                    <input type="hidden" :name="`items[${entry.index}][total_weight]`" :value="entry.item.total_weight">
                                                                    <span x-text="entry.item.total_weight > 0 ? (formatWeight(entry.item.total_weight) + ' kg') : '—'"></span>
                                                                </td>
                                                                <td class="px-2 py-2 text-center align-middle">
                                                                    <button type="button"
                                                                            @click="updateItemPrice(entry.item)"
                                                                            x-show="!isWeightOnly && !isQuantityOnly && !isAdjustment(entry.item)"
                                                                            :disabled="entry.item.isUpdatingPrice"
                                                                            class="inline-flex items-center gap-1 px-1.5 py-1 text-[10px] font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition disabled:opacity-50"
                                                                            title="Update this item's price">
                                                                        <svg class="w-3.5 h-3.5" :class="entry.item.isUpdatingPrice ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.5 15A7 7 0 0017.9 17.9L20 15M18.5 9A7 7 0 006.1 6.1L4 9"></path></svg>
                                                                        <span>Price</span>
                                                                    </button>
                                                                </td>
                                                            </tr>
                                                        </template>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Empty state if no groups and no direct items -->
                                    <template x-if="orderSheetGroupList.length === 0 && getItemsForGroup('').length === 0">
                                        <div class="p-8 text-center border-2 border-dashed border-purple-200 rounded-xl bg-purple-50/30 space-y-3">
                                            <div class="w-12 h-12 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center mx-auto text-xl font-bold">
                                                📦
                                            </div>
                                            <h4 class="font-bold text-sm text-purple-950">No Order Sheets Added Yet</h4>
                                            <p class="text-xs text-gray-600 max-w-md mx-auto">
                                                Select or enter an Order Sheet code above (e.g. <strong class="font-mono text-purple-800">B26001</strong>) to receive items, or click below to add direct items.
                                            </p>
                                            <div class="flex items-center justify-center gap-3 pt-2">
                                                <button type="button" @click="addDirectItem()" class="px-3.5 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-semibold rounded-lg shadow-2xs transition">
                                                    + Add Direct Item
                                                </button>
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Totals summary strip for Grouped view -->
                                    <div class="bg-gradient-to-r from-slate-50 via-purple-50/40 to-slate-50 p-4 rounded-xl border border-gray-200 flex flex-wrap items-center justify-between gap-4 text-xs font-bold">
                                        <div class="text-gray-600 uppercase tracking-wider text-[11px]">
                                            Factory Invoice Totals Across All Groups:
                                        </div>
                                        <div class="flex flex-wrap items-center gap-6">
                                            <div>
                                                <span class="text-gray-500 block text-[10px] uppercase">Total Received Qty</span>
                                                <span class="text-indigo-700 font-mono font-black text-sm" x-text="formattedTotalQuantity"></span> <span class="text-xs font-normal text-gray-500">units</span>
                                            </div>
                                            <template x-if="!isWeightOnly && finalTotal > 0">
                                                <div>
                                                    <span class="text-gray-500 block text-[10px] uppercase">Total Value</span>
                                                    <span class="text-gray-900 font-mono font-black text-sm" x-text="currency + ' ' + formatNumber(finalTotal)"></span>
                                                </div>
                                            </template>
                                            <template x-if="calculatedItemsNetWeight > 0">
                                                <div>
                                                    <span class="text-gray-500 block text-[10px] uppercase">Total Net Weight</span>
                                                    <span class="text-gray-900 font-mono font-black text-sm" x-text="formatWeight(calculatedItemsNetWeight) + ' kg'"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <!-- Standard Flat List Table (Shown for all documents when not grouped, or for non-factory-invoice documents) -->
                            <template x-if="documentType !== 'factory_invoice' || !groupByOrderSheet">
                                <div class="overflow-x-auto">
                                    <table x-ref="itemsTable" class="min-w-full divide-y divide-gray-200 text-xs">
                                    <thead class="bg-gray-50 text-gray-600 font-bold uppercase tracking-wider">
                                        <tr>
                                            <th class="px-2 py-2.5 text-center w-14 text-gray-400">#</th>
                                            <th class="px-3 py-2.5 text-left w-44">Item / Record Code</th>
                                            <th x-show="documentType === 'factory_invoice'" class="px-3 py-2.5 text-left w-36">Order Sheet #</th>
                                            <th class="px-3 py-2.5 text-left min-w-[180px]">Description</th>
                                            <th class="px-3 py-2.5 text-right w-24">Quantity</th>
                                            <!-- Financial headers -->
                                            <th x-show="!isWeightOnly" class="px-3 py-2.5 text-right w-48">
                                                Unit Price (<span x-text="currency"></span>)
                                                <template x-if="isQuantityOnly"><span class="text-[10px] font-normal text-gray-400 block -mt-0.5">(Optional)</span></template>
                                            </th>
                                            <th x-show="!isWeightOnly" class="px-3 py-2.5 text-right w-32">
                                                Total Amount
                                                <template x-if="isQuantityOnly"><span class="text-[10px] font-normal text-gray-400 block -mt-0.5">(Optional)</span></template>
                                            </th>
                                            <!-- Weight headers (available for all documents, auto-populated from item manager with edit option) -->
                                            <th x-show="!isQuantityOnly" class="px-3 py-2.5 text-right w-28">Unit Net Wt (kg)</th>
                                            <th x-show="!isQuantityOnly" class="px-3 py-2.5 text-right w-32">Total Net Wt (kg)</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        <template x-for="(item, index) in items" :key="index">
                                            <tr class="hover:bg-slate-50 group transition duration-150"
                                                :class="{
                                                    'bg-rose-50/40': item.type === 'discount' || item.total_amount < 0,
                                                    'bg-amber-50/30': item.type === 'tax' || ['TAX', 'VAT'].includes((item.item_code || '').toUpperCase()),
                                                    'bg-emerald-50/30': item.type === 'addition',
                                                    'opacity-40 bg-indigo-50 border-2 border-dashed border-indigo-400': draggedRowIndex === index,
                                                    'border-t-2 border-indigo-500 bg-indigo-50/40': dragOverRowIndex === index && draggedRowIndex !== index
                                                }"
                                                @dragover.prevent="onRowDragOver($event, index)"
                                                @dragleave="onRowDragLeave($event, index)"
                                                @drop.prevent="onRowDrop($event, index)">
                                                <td class="px-1 py-2 text-center align-middle text-gray-400 select-none relative">
                                                    <div class="flex items-center justify-center space-x-1">
                                                        <span class="cursor-grab active:cursor-grabbing text-gray-400 hover:text-indigo-600 p-0.5 rounded transition"
                                                              draggable="true"
                                                              @dragstart="onRowDragStart($event, index)"
                                                              @dragend="onRowDragEnd()"
                                                              title="Drag to reorder row">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                                            </svg>
                                                        </span>
                                                        <span class="text-[10px] font-mono text-gray-500 font-bold" x-text="index + 1"></span>
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 align-middle">
                                                    <!-- Hidden fields to persist price_list and fallback flag -->
                                                    <input type="hidden" :name="`items[${index}][price_list]`" :value="item.price_list || ''">
                                                    <input type="hidden" :name="`items[${index}][is_fallback]`" :value="item.is_fallback ? '1' : '0'">

                                                    <div class="relative flex flex-col">
                                                        <!-- Union Fallback Badge on top of item -->
                                                        <template x-if="isUnionFallbackItem(item)">
                                                            <div class="mb-1 flex items-center">
                                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-200 text-amber-950 border border-amber-400 shadow-2xs" title="Price sourced from Union list as fallback">
                                                                    Union
                                                                </span>
                                                            </div>
                                                        </template>
                                                        <div class="relative flex items-center">
                                                            <input type="text"
                                                               :name="`items[${index}][item_code]`"
                                                               x-model="item.item_code"
                                                               :list="`item-datalist-${index}`"
                                                               @input.debounce.250ms="onItemCodeInput(item, index)"
                                                               @change="lookupItemPrice(item)"
                                                               @keydown="handleTableKeyNav($event, index, 0)"
                                                               @paste="handleItemCodePaste($event, index)"
                                                               data-grid-item="true"
                                                               :data-grid-row="index"
                                                               data-grid-col="0"
                                                               :placeholder="item.type === 'discount' ? 'DISCOUNT' : (item.type === 'tax' ? 'TAX' : (item.type === 'addition' ? 'ADDITION' : 'SKU-101'))"
                                                               autocomplete="off"
                                                               autocorrect="off"
                                                               autocapitalize="off"
                                                               spellcheck="false"
                                                               data-lpignore="true"
                                                               required
                                                               class="w-full text-xs font-mono font-semibold rounded border-gray-300 py-1.5 px-2.5 transition"
                                                               :class="{
                                                                   'font-bold text-rose-700 bg-rose-50 border-rose-300': item.type === 'discount' || item.total_amount < 0,
                                                                   'font-bold text-amber-800 bg-amber-50 border-amber-300': item.type === 'tax' || ['TAX', 'VAT'].includes((item.item_code || '').toUpperCase()),
                                                                   'font-bold text-emerald-700 bg-emerald-50 border-emerald-300': item.type === 'addition' || (item.item_code || '').toUpperCase() === 'ADDITION'
                                                               }">
                                                        <datalist :id="`item-datalist-${index}`">
                                                            <template x-for="sug in (itemSuggestions[index] || [])" :key="sug.item_code">
                                                                <option :value="sug.item_code" :label="`${sug.item_code} - ${sug.description} (${sug.currency || ''} ${sug.unit_price || ''})`"></option>
                                                            </template>
                                                        </datalist>
                                                    </div>
                                                    </div>
                                                </td>
                                                <td x-show="documentType === 'factory_invoice'" class="px-2 py-2 align-middle">
                                                    <div class="relative">
                                                        <input type="text"
                                                               :name="`items[${index}][order_sheet_reference]`"
                                                               x-model="item.order_sheet_reference"
                                                               placeholder="e.g. B26001"
                                                               title="Order Sheet reference (e.g. B26001)"
                                                               class="w-full text-xs font-mono rounded border-purple-200 py-1.5 px-2 bg-purple-50/40 focus:bg-white text-purple-950 font-bold focus:ring-1 focus:ring-purple-500 placeholder:text-gray-300">
                                                    </div>
                                                </td>
                                                <template x-if="documentType !== 'factory_invoice' && item.order_sheet_reference">
                                                    <input type="hidden" :name="`items[${index}][order_sheet_reference]`" :value="item.order_sheet_reference">
                                                </template>
                                                <td class="px-3 py-2 align-middle">
                                                    <input type="text"
                                                           :name="`items[${index}][description]`"
                                                           x-model="item.description"
                                                           @keydown="handleTableKeyNav($event, index, 1)"
                                                           data-grid-item="true"
                                                           :data-grid-row="index"
                                                           data-grid-col="1"
                                                           autocomplete="off"
                                                           autocorrect="off"
                                                           autocapitalize="off"
                                                           spellcheck="false"
                                                           data-lpignore="true"
                                                           :placeholder="item.type === 'discount' ? 'e.g. Special client discount (10%)' : (item.type === 'tax' ? 'e.g. VAT / Tax (5%)' : (item.type === 'addition' ? 'e.g. Freight charge, packing fee' : 'Item description / specs'))"
                                                           class="w-full text-xs rounded border-gray-300 py-1.5 px-2">
                                                </td>
                                                <td class="px-3 py-2 align-middle">
                                                    <template x-if="!isAdjustment(item)">
                                                        <input type="text"
                                                               inputmode="decimal"
                                                               :name="`items[${index}][unit_amount]`"
                                                               x-model="item.unit_amount"
                                                               @input="onQuantityInput(item)"
                                                               @focus="$event.target.select()"
                                                               @keydown="handleTableKeyNav($event, index, 2)"
                                                               @paste="handleQuantityPaste($event, index)"
                                                               data-grid-item="true"
                                                               :data-grid-row="index"
                                                               data-grid-col="2"
                                                               autocomplete="off"
                                                               autocorrect="off"
                                                               autocapitalize="off"
                                                               spellcheck="false"
                                                               data-lpignore="true"
                                                                data-1p-ignore="true"
                                                                placeholder="Qty"
                                                                class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2">
                                                    </template>
                                                    <template x-if="isAdjustment(item)">
                                                        <div class="flex items-center justify-center py-1.5" title="Quantity not applicable for adjustments">
                                                            <input type="hidden" :name="`items[${index}][unit_amount]`" value="1">
                                                            <span class="text-gray-400 font-mono font-bold text-xs select-none">—</span>
                                                        </div>
                                                    </template>
                                                </td>
                                                <!-- Financial mode inputs -->
                                                <td x-show="!isWeightOnly" class="px-3 py-2 align-middle">
                                                    <!-- Regular Line Item Unit Price with Safe Lock & Edit Icon -->
                                                    <template x-if="!isAdjustment(item)">
                                                        <div class="relative flex flex-col">
                                                            <!-- Union Fallback Badge on top of unit price -->
                                                            <template x-if="isUnionFallbackItem(item)">
                                                                <div class="mb-1 flex items-center justify-end">
                                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-black uppercase tracking-wider bg-amber-200 text-amber-950 border border-amber-400 shadow-2xs" title="Price sourced from Union list as fallback">
                                                                        Union Price
                                                                    </span>
                                                                </div>
                                                            </template>
                                                            <div class="relative flex items-center">
                                                                 <input type="text"
                                                                    inputmode="decimal"
                                                                    :name="`items[${index}][unit_price]`"
                                                                    x-model="item.unit_price"
                                                                    @input="onUnitPriceInput(item)"
                                                                    @focus="if (item.price_editable || isQuantityOnly) $event.target.select()"
                                                                    @keydown="handleTableKeyNav($event, index, 3)"
                                                                    data-grid-item="true"
                                                                    :data-grid-row="index"
                                                                    data-grid-col="3"
                                                                    autocomplete="off"
                                                                    autocorrect="off"
                                                                    autocapitalize="off"
                                                                    spellcheck="false"
                                                                    data-lpignore="true"
                                                                    :readonly="!item.price_editable && !isQuantityOnly"
                                                                    placeholder="0.00"
                                                                    :required="!isWeightOnly && !isQuantityOnly"
                                                                    class="w-full text-xs font-mono text-right rounded py-1.5 pl-2 pr-14 transition"
                                                                    :class="(!item.price_editable && !isQuantityOnly) ? 'bg-slate-100/80 text-slate-700 cursor-not-allowed border-gray-200 select-all' : 'bg-white text-gray-900 font-bold border-indigo-500 ring-2 ring-indigo-500/20 shadow-xs'"
                                                                    :ref="`priceInput_${index}`">

                                                            <!-- Price Tracker Indicator Dot -->
                                                            <span x-show="item.price_from_tracker" x-cloak class="absolute -top-1 -right-1 flex h-2 w-2 pointer-events-none" title="Price loaded from Item Price Tracker">
                                                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                                                <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                                            </span>

                                                             <!-- Inline Action Buttons (Never go under the input) -->
                                                            <div class="absolute right-1 flex items-center space-x-0.5">
                                                                <!-- % Discount Button -->
                                                                <button type="button"
                                                                        x-show="parseFloat(item.unit_price) > 0"
                                                                        @click="applyLineDiscount(item)"
                                                                        class="px-1.5 py-0.5 text-[10px] font-bold rounded text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 transition"
                                                                        title="Apply % discount to this unit price">
                                                                    -%
                                                                </button>
                                                                <!-- Unlock/Lock Price Edit Toggle -->
                                                                <button type="button"
                                                                        @click="togglePriceEdit(item)"
                                                                        class="p-1 rounded transition"
                                                                        :class="item.price_editable ? 'text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50' : 'text-gray-400 hover:text-gray-600 hover:bg-gray-100'"
                                                                        :title="item.price_editable ? 'Lock price' : 'Unlock to edit price manually'">
                                                                    <!-- Unlocked Icon -->
                                                                    <svg x-show="item.price_editable" class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                                    </svg>
                                                                    <!-- Locked Icon (Default) -->
                                                                    <svg x-show="!item.price_editable" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                                    </svg>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        </div>
                                                    </template>
                                                    <!-- Adjustment / Discount / Tax Unit Price & Percentage Mode -->
                                                    <template x-if="isAdjustment(item)">
                                                        <div>
                                                            <!-- Fixed Input Mode -->
                                                            <div x-show="item.calc_mode !== 'percentage'" class="flex items-center space-x-1">
                                                                <button type="button"
                                                                        @click="setCalcMode(item, 'percentage')"
                                                                        class="px-1.5 py-1 rounded text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 transition shrink-0"
                                                                        title="Switch to % percentage mode">
                                                                    %
                                                                </button>
                                                                <input type="text"
                                                                       inputmode="decimal"
                                                                       :name="`items[${index}][unit_price]`"
                                                                       x-model="item.unit_price"
                                                                       @input="onUnitPriceInput(item)"
                                                                       @keydown="handleTableKeyNav($event, index, 3)"
                                                                       data-grid-item="true"
                                                                       :data-grid-row="index"
                                                                       data-grid-col="3"
                                                                       autocomplete="off"
                                                                       autocorrect="off"
                                                                       autocapitalize="off"
                                                                       spellcheck="false"
                                                                       data-lpignore="true"
                                                                       placeholder="0.00"
                                                                       class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2"
                                                                       :class="item.type === 'discount' || item.total_amount < 0 ? 'text-rose-600 font-bold' : (item.type === 'tax' ? 'text-amber-700 font-bold' : 'text-emerald-700 font-bold')">
                                                            </div>

                                                            <!-- Percentage Input Mode -->
                                                            <div x-show="item.calc_mode === 'percentage'" class="flex items-center space-x-1">
                                                                <button type="button"
                                                                        @click="setCalcMode(item, 'fixed')"
                                                                        class="px-1.5 py-1 rounded text-[10px] font-bold bg-gray-100 hover:bg-gray-200 text-gray-600 transition shrink-0"
                                                                        title="Switch to fixed amount mode">
                                                                    <span x-text="currency === 'AED' ? 'AED' : '$'"></span>
                                                                </button>
                                                                <div class="relative flex items-center flex-1">
                                                                    <input type="number"
                                                                           step="any"
                                                                           min="0"
                                                                           max="100"
                                                                           x-model.number="item.percentage"
                                                                           @input="recalcItem(item)"
                                                                           @keydown="handleTableKeyNav($event, index, 3)"
                                                                           data-grid-item="true"
                                                                           :data-grid-row="index"
                                                                           data-grid-col="3"
                                                                           autocomplete="off"
                                                                           autocorrect="off"
                                                                           autocapitalize="off"
                                                                           spellcheck="false"
                                                                           data-lpignore="true"
                                                                           placeholder="0.0"
                                                                           class="w-full text-xs font-mono font-bold text-right rounded border-gray-300 py-1.5 pl-2 pr-6 focus:ring-indigo-500 focus:border-indigo-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                                    <span class="absolute right-2 text-xs font-black text-gray-500 pointer-events-none">%</span>
                                                                </div>
                                                                <input type="hidden" :name="`items[${index}][unit_price]`" :value="item.unit_price">
                                                            </div>
                                                        </div>
                                                    </template>
                                                </td>
                                                <td x-show="!isWeightOnly" class="px-3 py-2 align-middle text-right font-mono font-bold" :class="item.total_amount < 0 ? 'text-rose-600' : (item.type === 'tax' ? 'text-amber-700' : 'text-gray-800')">
                                                    <span x-text="currency"></span> <span x-text="item.total_amount < 0 ? `-${formatNumber(Math.abs(item.total_amount))}` : formatNumber(item.total_amount)"></span>
                                                </td>
                                                <!-- Weight-only mode fallback unit price -->
                                                <template x-if="isWeightOnly">
                                                    <input type="hidden" :name="`items[${index}][unit_price]`" value="0">
                                                </template>
                                                <template x-if="isQuantityOnly">
                                                    <div>
                                                        <input type="hidden" :name="`items[${index}][unit_weight]`" value="0">
                                                        <input type="hidden" :name="`items[${index}][total_weight]`" value="0">
                                                    </div>
                                                </template>
                                                <!-- Unit Net Weight (editable) -->
                                                <td x-show="!isQuantityOnly" class="px-3 py-2 align-middle">
                                                    <template x-if="!isAdjustment(item)">
                                                        <input type="text"
                                                                inputmode="decimal"
                                                                :name="`items[${index}][unit_weight]`"
                                                                x-model="item.unit_weight"
                                                                @input="onWeightInput(item)"
                                                                @focus="$event.target.select()"
                                                                @keydown="handleTableKeyNav($event, index, isWeightOnly ? 3 : 4)"
                                                                data-grid-item="true"
                                                                :data-grid-row="index"
                                                                :data-grid-col="isWeightOnly ? 3 : 4"
                                                                autocomplete="off"
                                                                autocorrect="off"
                                                                autocapitalize="off"
                                                                spellcheck="false"
                                                                data-lpignore="true"
                                                                placeholder="0.000"
                                                                title="Unit Net Weight in kg (from item master, editable)"
                                                                class="w-full text-xs font-mono text-right rounded border-gray-300 py-1.5 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                                    </template>
                                                    <template x-if="isAdjustment(item)">
                                                        <div class="flex items-center justify-center py-1.5" title="Weight not applicable for adjustments">
                                                            <input type="hidden" :name="`items[${index}][unit_weight]`" value="0">
                                                            <span class="text-gray-400 font-mono font-bold text-xs select-none">—</span>
                                                        </div>
                                                    </template>
                                                </td>
                                                <!-- Total Net Weight (computed) -->
                                                <td x-show="!isQuantityOnly" class="px-3 py-2 align-middle text-right font-mono font-bold text-gray-800 relative">
                                                    <input type="hidden" :name="`items[${index}][total_weight]`" :value="item.total_weight">
                                                    <div class="flex items-center justify-end space-x-1.5">
                                                        <template x-if="!isAdjustment(item)">
                                                            <span><span x-text="formatWeight(item.total_weight)"></span> kg</span>
                                                        </template>
                                                        <template x-if="isAdjustment(item)">
                                                            <span class="text-gray-400 font-mono font-bold text-xs select-none">—</span>
                                                        </template>

                                                        <button type="button"
                                                                @click="updateItemPrice(item)"
                                                                x-show="!isWeightOnly && !isQuantityOnly && !isAdjustment(item)"
                                                                :disabled="item.isUpdatingPrice"
                                                                class="inline-flex items-center gap-1 px-1.5 py-1 text-[10px] font-bold text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded transition disabled:opacity-50"
                                                                title="Update this item's price">
                                                            <svg class="w-3.5 h-3.5" :class="item.isUpdatingPrice ? 'animate-spin' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M5.5 15A7 7 0 0017.9 17.9L20 15M18.5 9A7 7 0 006.1 6.1L4 9"></path></svg>
                                                            <span>Price</span>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-slate-50 font-bold border-t-2 border-gray-200 text-xs">
                                        <tr>
                                            <td :colspan="documentType === 'factory_invoice' ? 4 : 3" class="px-3 py-2.5 text-right uppercase text-gray-500 font-semibold tracking-wider">
                                                Total Quantity:
                                            </td>
                                            <td class="px-3 py-2.5 text-right font-mono font-black text-indigo-700 text-sm">
                                                <span x-text="formattedTotalQuantity"></span>
                                            </td>
                                            <!-- Financial footer -->
                                            <td x-show="!isWeightOnly" class="px-3 py-2.5 text-right font-mono text-gray-400 text-xs">—</td>
                                            <td x-show="!isWeightOnly" class="px-3 py-2.5 text-right font-mono font-black text-sm text-gray-900">
                                                <div><span x-text="currency"></span> <span x-text="formatNumber(finalTotal)"></span></div>
                                                <template x-if="appliedFreightAmount > 0">
                                                    <div class="text-[10px] font-normal text-indigo-600">
                                                        (incl. +<span x-text="currency"></span> <span x-text="formatNumber(appliedFreightAmount)"></span> <span x-text="selectedCarrierName"></span>)
                                                    </div>
                                                </template>
                                            </td>
                                            <!-- Weight footer -->
                                            <td x-show="!isQuantityOnly" class="px-3 py-2.5 text-right font-mono text-gray-400 text-xs">—</td>
                                            <td x-show="!isQuantityOnly" class="px-3 py-2.5 text-right font-mono font-black text-sm text-gray-900">
                                                <span x-text="formatWeight(calculatedItemsNetWeight)"></span> kg
                                            </td>
                                        </tr>
                                    </tfoot>
                                 </table>
                            </div>
                            </template>

                            <!-- Bottom Items Action Bar -->
                            <div class="px-5 py-3 bg-slate-50 border-t border-gray-200 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <template x-if="documentType !== 'factory_invoice' || !groupByOrderSheet">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" @click="addItem()" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                                                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Line Item
                                            </button>
                                            <button type="button" @click="openBulkPasteModal('add_items')" class="inline-flex items-center px-3.5 py-2 bg-slate-800 hover:bg-slate-900 active:bg-black text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition" title="Paste multiple items or quantities at once">
                                                <svg class="w-4 h-4 me-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                                                Bulk Paste Items / Qty
                                            </button>
                                            <button type="button" x-show="!isWeightOnly" @click="addDiscount()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-rose-50 border border-rose-200 text-rose-700 rounded-xl text-xs font-bold transition shadow-2xs" title="Add a discount line (% or fixed minus from total)">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path></svg>
                                                Add Discount (-)
                                            </button>
                                            <button type="button" x-show="!isWeightOnly" @click="addTax()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-amber-50 border border-amber-200 text-amber-800 rounded-xl text-xs font-bold transition shadow-2xs" title="Add VAT or tax line (% or fixed plus to total)">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Tax / VAT (+)
                                            </button>
                                            <button type="button" x-show="!isWeightOnly" @click="addAddition()" class="inline-flex items-center px-3 py-2 bg-white hover:bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-xl text-xs font-bold transition shadow-2xs" title="Add extra charge, freight, or surcharge line (plus to total)">
                                                <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                Add Addition (+)
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="documentType === 'factory_invoice' && groupByOrderSheet">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <button type="button" @click="addDirectItem()" class="inline-flex items-center px-3.5 py-2 bg-white hover:bg-gray-50 border border-gray-300 text-gray-700 rounded-xl text-xs font-bold transition shadow-2xs">
                                                <svg class="w-4 h-4 me-1.5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                                + Add Direct / Unassigned Item
                                            </button>
                                        </div>
                                    </template>
                                </div>
                                <div class="text-xs text-gray-500 font-medium flex items-center space-x-2">
                                    <span><strong class="text-gray-700" x-text="items.length"></strong> line item(s) in document</span>
                                    <span>&bull;</span>
                                    <span>Total Quantity: <strong class="text-indigo-700 font-mono font-bold" x-text="`${formattedTotalQuantity} units`"></strong></span>
                                </div>
                            </div>

                            <!-- Weights & Subtotal Bar with Live Weight Check -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 pt-4 border-t border-gray-100 bg-slate-50 p-4 rounded-lg">
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Quantity
                                    </label>
                                    <div class="h-10 px-3 bg-white border border-gray-300 rounded-lg flex items-center justify-between font-mono font-black text-indigo-700 text-sm">
                                        <span x-text="formattedTotalQuantity"></span>
                                        <span class="text-xs text-gray-400 font-sans font-normal">units</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider">
                                            Total Net Weight (kg)
                                        </label>
                                        <button type="button" @click="syncWeightFromItems()" x-show="calculatedItemsNetWeight > 0" class="text-[10px] text-indigo-600 hover:text-indigo-800 font-semibold underline">
                                            Sync from Items (<span x-text="formatWeight(calculatedItemsNetWeight)"></span> kg)
                                        </button>
                                    </div>
                                    <input type="number" step="0.001" min="0" name="total_net_weight" x-model.number="netWeight" placeholder="0.000" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-wider mb-1">
                                        Total Gross Weight (kg)
                                    </label>
                                    <input type="number" step="0.001" min="0" name="total_gross_weight" x-model.number="grossWeight" @input="recalcAllCarriers()" placeholder="0.000" class="w-full text-sm font-mono rounded-lg border-gray-300">
                                </div>
                                <div class="text-right flex flex-col justify-center">
                                    <div x-show="!isWeightOnly && (!isQuantityOnly || subtotal > 0)" class="space-y-1">
                                        <div x-show="discountsTotal > 0 || taxesTotal > 0 || additionsTotal > 0" class="text-[11px] text-gray-500 space-y-0.5 border-b border-gray-200 pb-1.5 mb-1">
                                            <div class="flex items-center justify-end space-x-2">
                                                <span>Items Subtotal:</span>
                                                <span class="font-mono font-bold text-gray-800"><span x-text="currency"></span> <span x-text="formatNumber(itemsBaseTotal)"></span></span>
                                            </div>
                                            <div x-show="discountsTotal > 0" class="flex items-center justify-end space-x-2 text-rose-600">
                                                <span>Discounts:</span>
                                                <span class="font-mono font-bold">-<span x-text="currency"></span> <span x-text="formatNumber(discountsTotal)"></span></span>
                                            </div>
                                            <div x-show="taxesTotal > 0" class="flex items-center justify-end space-x-2 text-amber-700">
                                                <span>Tax / VAT:</span>
                                                <span class="font-mono font-bold">+<span x-text="currency"></span> <span x-text="formatNumber(taxesTotal)"></span></span>
                                            </div>
                                            <div x-show="additionsTotal > 0" class="flex items-center justify-end space-x-2 text-emerald-700">
                                                <span>Additions:</span>
                                                <span class="font-mono font-bold">+<span x-text="currency"></span> <span x-text="formatNumber(additionsTotal)"></span></span>
                                            </div>
                                        </div>
                                        <span class="text-xs uppercase tracking-wider font-semibold text-gray-500">Calculated Subtotal</span>
                                        <span class="text-xl font-mono font-extrabold text-indigo-700 block">
                                            <span x-text="currency"></span> <span x-text="formatNumber(subtotal)"></span>
                                        </span>
                                    </div>
                                    <div x-show="isWeightOnly" class="space-y-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                            Weight-Only Document
                                        </span>
                                        <p class="text-[11px] text-gray-500">Prices omitted (Packing List / Reserve)</p>
                                    </div>
                                    <div x-show="isQuantityOnly && subtotal == 0" class="space-y-1">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-100 text-purple-800 border border-purple-200">
                                            Quantity-Only Mode
                                        </span>
                                        <p class="text-[11px] text-gray-500">Optional prices not entered</p>
                                    </div>
                                </div>

                                <!-- Live Weight Validation Warning -->
                                <div x-show="netWeight > 0 && grossWeight > 0 && netWeight > grossWeight" x-transition class="md:col-span-3 text-xs text-amber-800 bg-amber-50 p-2.5 rounded-lg border border-amber-200 flex items-center">
                                    <svg class="w-4 h-4 me-2 text-amber-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                                    <span><strong>Weight Alert:</strong> Net Weight (<span x-text="netWeight"></span> kg) is greater than Gross Weight (<span x-text="grossWeight"></span> kg). Ensure packaging is factored into gross weight.</span>
                                </div>
                            </div>
                        </div>

                        <!-- Bulk Paste Items & Quantities Modal -->
                        <div x-show="bulkPasteModalOpen"
                             x-cloak
                             class="fixed inset-0 z-50 overflow-y-auto"
                             aria-labelledby="modal-title"
                             role="dialog"
                             aria-modal="true">
                            <!-- Backdrop with blur -->
                            <div x-show="bulkPasteModalOpen"
                                 x-transition:enter="ease-out duration-200"
                                 x-transition:enter-start="opacity-0"
                                 x-transition:enter-end="opacity-100"
                                 x-transition:leave="ease-in duration-150"
                                 x-transition:leave-start="opacity-100"
                                 x-transition:leave-end="opacity-0"
                                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                                 @click="bulkPasteModalOpen = false"></div>

                            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                                <div x-show="bulkPasteModalOpen"
                                     x-transition:enter="ease-out duration-200"
                                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave="ease-in duration-150"
                                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                                     class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-gray-200">

                                    <!-- Modal Header -->
                                    <div class="px-6 py-4 bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 text-white flex items-center justify-between">
                                        <div class="flex items-center space-x-2.5">
                                            <div class="p-2 bg-white/10 rounded-xl">
                                                <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="text-base font-black tracking-tight" id="modal-title">Bulk Paste Items & Quantities</h3>
                                                <p class="text-xs text-slate-300">Paste comma-separated or newline-separated lists from Excel, ERP, or chat</p>
                                            </div>
                                        </div>
                                        <button type="button" @click="bulkPasteModalOpen = false" class="text-slate-400 hover:text-white transition p-1 rounded-lg">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </div>

                                    <!-- Tab Switcher -->
                                    <div class="flex border-b border-gray-200 bg-gray-50/80 px-6 pt-3 gap-2">
                                        <button type="button"
                                                @click="bulkPasteTab = 'add_items'"
                                                class="pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5"
                                                :class="bulkPasteTab === 'add_items' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                            <span>➕ Add New Items (+ Quantities)</span>
                                        </button>
                                        <button type="button"
                                                @click="bulkPasteTab = 'update_quantities'"
                                                class="pb-2.5 px-3 text-xs font-bold border-b-2 transition flex items-center gap-1.5"
                                                :class="bulkPasteTab === 'update_quantities' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700'">
                                            <span>🔢 Update Quantities for Existing Items</span>
                                            <span class="ml-1 px-1.5 py-0.5 rounded-full text-[10px] bg-slate-200 text-slate-700 font-mono font-bold" x-text="items.filter(it => !isAdjustment(it)).length"></span>
                                        </button>
                                    </div>

                                    <!-- Modal Body -->
                                    <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                                        <!-- TAB 1: ADD NEW ITEMS -->
                                        <div x-show="bulkPasteTab === 'add_items'" class="space-y-4">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <!-- Item Codes Textarea -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                            Item Codes <span class="text-rose-500">*</span>
                                                        </label>
                                                        <span class="text-[11px] text-gray-400 font-mono" x-text="`${bulkPastePreviewItems.length} code(s)`"></span>
                                                    </div>
                                                    <textarea x-model="bulkPasteItemsText"
                                                              rows="6"
                                                              placeholder="Paste item codes separated by comma or new line:&#10;SKU-001, SKU-002, SKU-003&#10;or&#10;SKU-001&#10;SKU-002&#10;or Excel 2-column data"
                                                              class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 shadow-2xs"></textarea>
                                                    <p class="mt-1 text-[11px] text-gray-500">Supports: Comma (<kbd>,</kbd>), Enter/Newline, or Tabs</p>
                                                </div>

                                                <!-- Quantities Textarea -->
                                                <div>
                                                    <div class="flex items-center justify-between mb-1">
                                                        <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                            Quantities <span class="text-gray-400 font-normal text-[11px]">(Optional)</span>
                                                        </label>
                                                        <span class="text-[11px] text-gray-400 font-mono" x-text="`${parseDelimitedList(bulkPasteQuantitiesText).length} qty(s)`"></span>
                                                    </div>
                                                    <textarea x-model="bulkPasteQuantitiesText"
                                                              rows="6"
                                                              placeholder="Paste quantities corresponding to items:&#10;10, 25, 50&#10;or&#10;10&#10;25&#10;50"
                                                              class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 shadow-2xs"></textarea>
                                                    <p class="mt-1 text-[11px] text-gray-500">1st qty matches 1st item, 2nd matches 2nd, etc.</p>
                                                </div>
                                            </div>

                                            <!-- Live Match Preview -->
                                            <div x-show="bulkPastePreviewItems.length > 0" class="border border-indigo-100 bg-indigo-50/40 rounded-xl p-3">
                                                <div class="flex items-center justify-between mb-2">
                                                    <span class="text-xs font-bold text-indigo-900 uppercase tracking-wider flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        Matched Items Preview
                                                    </span>
                                                    <span class="text-xs text-indigo-700 font-semibold" x-text="`${bulkPastePreviewItems.length} item(s) to add`"></span>
                                                </div>
                                                <div class="max-h-40 overflow-y-auto border border-indigo-100 rounded-lg bg-white divide-y divide-gray-100">
                                                    <template x-for="(pv, idx) in bulkPastePreviewItems" :key="idx">
                                                        <div class="px-3 py-1.5 flex items-center justify-between text-xs font-mono">
                                                            <div class="flex items-center space-x-2">
                                                                <span class="text-gray-400 text-[11px]" x-text="`#${idx + 1}`"></span>
                                                                <span class="font-bold text-gray-800" x-text="pv.code"></span>
                                                            </div>
                                                            <div class="flex items-center space-x-1.5">
                                                                <span class="text-gray-500 text-[11px]">Qty:</span>
                                                                <span class="font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-800" x-text="pv.qty !== '' ? pv.qty : '—'"></span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB 2: UPDATE EXISTING ITEMS' QUANTITIES -->
                                        <div x-show="bulkPasteTab === 'update_quantities'" class="space-y-4">
                                            <div>
                                                <div class="flex items-center justify-between mb-1">
                                                    <label class="text-xs font-bold text-gray-700 uppercase tracking-wider">
                                                        Quantities List <span class="text-rose-500">*</span>
                                                    </label>
                                                    <span class="text-[11px] text-gray-400 font-mono" x-text="`${parseDelimitedList(bulkPasteQuantitiesText).length} qty(s)`"></span>
                                                </div>
                                                <textarea x-model="bulkPasteQuantitiesText"
                                                          rows="5"
                                                          placeholder="Paste quantity list separated by comma or new line:&#10;5, 12, 30, 8, 14&#10;or&#10;5&#10;12&#10;30"
                                                          class="w-full text-xs font-mono rounded-xl border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 py-2 px-3 shadow-2xs"></textarea>
                                                <p class="mt-1 text-[11px] text-gray-500">Quantities will be assigned sequentially to the existing line items in the table below.</p>
                                            </div>

                                            <!-- Target Mapping Preview -->
                                            <div class="border border-gray-200 bg-slate-50/60 rounded-xl p-3">
                                                <span class="text-xs font-bold text-gray-700 uppercase tracking-wider block mb-2">
                                                    Existing Items & New Quantity Mapping
                                                </span>
                                                <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-lg bg-white divide-y divide-gray-100">
                                                    <template x-for="(it, idx) in items.filter(x => !isAdjustment(x))" :key="idx">
                                                        <div class="px-3 py-1.5 flex items-center justify-between text-xs font-mono">
                                                            <div class="flex items-center space-x-2 truncate pr-2">
                                                                <span class="text-gray-400 text-[11px]" x-text="`#${idx + 1}`"></span>
                                                                <span class="font-bold text-gray-800" x-text="it.item_code || '(No Code)'"></span>
                                                                <span class="text-gray-500 truncate max-w-[180px]" x-text="it.description"></span>
                                                            </div>
                                                            <div class="flex items-center space-x-2 shrink-0">
                                                                <span class="text-gray-400 text-[11px]">Current: <span x-text="it.unit_amount || '—'"></span></span>
                                                                <span>→</span>
                                                                <span class="font-bold px-2 py-0.5 rounded text-xs"
                                                                      :class="bulkPasteQtyForIndex(idx) !== null ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-400'"
                                                                      x-text="bulkPasteQtyForIndex(idx) !== null ? bulkPasteQtyForIndex(idx) : 'No change'">
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Modal Footer -->
                                    <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                                        <button type="button" @click="bulkPasteModalOpen = false" class="px-4 py-2 text-xs font-bold text-gray-600 hover:text-gray-800 transition">
                                            Cancel
                                        </button>
                                        <div class="flex items-center space-x-2">
                                            <button type="button"
                                                    x-show="bulkPasteTab === 'add_items'"
                                                    @click="applyBulkAddItems()"
                                                    :disabled="bulkPastePreviewItems.length === 0"
                                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                                                Add <span x-text="bulkPastePreviewItems.length"></span> Item(s) to Document
                                            </button>
                                            <button type="button"
                                                    x-show="bulkPasteTab === 'update_quantities'"
                                                    @click="applyBulkUpdateQuantities()"
                                                    :disabled="parseDelimitedList(bulkPasteQuantitiesText).length === 0"
                                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed text-white rounded-xl text-xs font-bold shadow-xs hover:shadow transition">
                                                Update Quantities
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

