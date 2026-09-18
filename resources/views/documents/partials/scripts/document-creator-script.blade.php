    <!-- Alpine.js Document Creation & Checklist State Management -->
    <script>
        function documentCreator(initial = {}) {
            const initialItems = (initial && initial.items && initial.items.length > 0)
                ? initial.items.map(it => {
                    const price = parseFloat(it.unit_price) || 0;
                    const code = (it.item_code || '').toUpperCase();
                    const isDisc = price < 0 || code === 'DISCOUNT';
                    const isTax = code === 'TAX' || code === 'VAT';
                    const isAdd = code === 'ADDITION';
                    const desc = it.description || '';
                    const pctMatch = desc.match(/(\d+(?:\.\d+)?)\s*%/);
                    const pct = pctMatch ? parseFloat(pctMatch[1]) : (isTax ? 5 : null);
                    return {
                        type: isDisc ? 'discount' : (isTax ? 'tax' : (isAdd ? 'addition' : 'item')),
                        item_code: it.item_code || '',
                        order_sheet_reference: it.order_sheet_reference || it.source_order || '',
                        description: it.description || '',
                        calc_mode: pct ? 'percentage' : 'fixed',
                        percentage: pct,
                        unit_amount: (it.unit_amount !== undefined && it.unit_amount !== null && it.unit_amount !== '') ? it.unit_amount : '',
                        unit_price: (it.unit_price !== undefined && it.unit_price !== null && it.unit_price !== '') ? it.unit_price : '',
                        total_amount: parseFloat(it.total_amount) || 0,
                        unit_weight: parseFloat(it.unit_weight) || 0,
                        total_weight: parseFloat(it.total_weight) || 0,
                        price_from_tracker: false,
                        price_editable: false,
                        price_list: it.price_list || '',
                        is_fallback: Boolean(it.is_fallback)
                    };
                })
                : [
                    { type: 'item', item_code: '', order_sheet_reference: '', description: '', calc_mode: 'fixed', percentage: null, unit_amount: '', unit_price: '', total_amount: 0, unit_weight: 0, total_weight: 0, price_from_tracker: false, price_editable: false, price_list: '', is_fallback: false }
                ];

            const initialPackages = (initial && initial.packages && initial.packages.length > 0)
                ? initial.packages.map(p => ({
                    package_type: p.package_type || 'Carton',
                    dimension_type: p.dimension_type || 'standard',
                    length_cm: p.length_cm ?? null,
                    width_cm: p.width_cm ?? null,
                    height_cm: p.height_cm ?? null,
                    diameter_cm: p.diameter_cm ?? null,
                    quantity: parseInt(p.quantity) || 1,
                    gross_weight_per_pkg_kg: p.gross_weight_per_pkg_kg ?? null,
                    volumetric_weight_kg: parseFloat(p.volumetric_weight_kg) || 0,
                    cbm: parseFloat(p.cbm) || 0
                }))
                : [
                    {
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
                    }
                ];

            return {
                groupByOrderSheet: true,
                newOrderSheetRef: '',
                showDirectItems: false,
                activeOrderSheetGroups: [],
                orderSheetsData: {},
                loadingOrderSheets: {},
                sourceDocumentId: initial.sourceDocumentId || initial.source_document_id || null,
                sourceDocumentNumber: initial.sourceDocumentNumber || initial.source_document_number || '',
                companyName: initial.companyName || initial.company_name || '',
                country: initial.country || '',
                address: initial.address || '',
                contactDetails: initial.contactDetails || initial.contact_details || '',
                documentNumber: initial.documentNumber || initial.document_number || '',
                documentType: initial.documentType || initial.document_type || '{{ old('document_type', $targetType ?? '') }}',
                currency: initial.currency || 'USD',
                ruleMatched: '',
                isDetecting: false,
                subtotal: 0,
                finalTotal: 0,
                selectedCarrier: initial.selectedCarrier || null,
                netWeight: initial.netWeight ?? initial.total_net_weight ?? null,
                grossWeight: initial.grossWeight ?? initial.total_gross_weight ?? null,
                checklists: [],
                checkedItems: {},

                draftKey: 'doc_draft_create' + (initial.sourceDocumentId ? '_' + initial.sourceDocumentId : ''),
                hasDraft: false,
                draftSavedAt: null,
                lastAutoSavedAt: null,
                autoSaveTimer: null,
                savedDraft: null,

                sourceInput: initial.sourceInput || initial.sourceDocumentNumber || initial.source_document_number || '',
                isImporting: false,
                importMessage: initial.importMessage || '',
                importError: '',

                selectedPriceList: '{{ old('price_list', '') }}',
                selectedPriceLabel: '{{ old('price_label', '') }}' || ((initial.currency || '{{ old('currency', 'USD') }}') === 'AED' ? 'AED 30%' : 'USD 30%'),
                availablePriceLists: ['Price List', 'Union', 'Union Special'],
                availablePriceLabels: ['AED 30%', 'AED 40%', 'AED 50%', 'USD 30%', 'USD 40%', 'USD 50%'],
                itemSuggestions: {},
                isRepricing: false,
                latestPiDoc: null,
                isCheckingPi: false,

                currenciesMap: @json($currencies->keyBy('code')),
                isConvertingCurrency: false,
                conversionMessage: '',

                bulkPasteModalOpen: false,
                bulkPasteTab: 'add_items',
                bulkPasteItemsText: '',
                bulkPasteQuantitiesText: '',
                draggedRowIndex: null,
                dragOverRowIndex: null,

                items: initialItems,
                packages: initialPackages,

                carriers: {
                    dhl: {
                        checked_weight: initial?.carriers?.dhl?.checked_weight ?? null,
                        rate_per_kg: initial?.carriers?.dhl?.rate_per_kg ?? null,
                        system_amount: initial?.carriers?.dhl?.system_amount ?? null,
                        added_amount: initial?.carriers?.dhl?.added_amount ?? null,
                        given_amount: initial?.carriers?.dhl?.given_amount ?? null
                    },
                    air_freight: {
                        checked_weight: initial?.carriers?.air_freight?.checked_weight ?? null,
                        rate_per_kg: initial?.carriers?.air_freight?.rate_per_kg ?? null,
                        system_amount: initial?.carriers?.air_freight?.system_amount ?? null,
                        added_amount: initial?.carriers?.air_freight?.added_amount ?? null,
                        given_amount: initial?.carriers?.air_freight?.given_amount ?? null
                    },
                    sea_freight: {
                        checked_weight: initial?.carriers?.sea_freight?.checked_weight ?? null,
                        rate_per_kg: initial?.carriers?.sea_freight?.rate_per_kg ?? null,
                        system_amount: initial?.carriers?.sea_freight?.system_amount ?? null,
                        added_amount: initial?.carriers?.sea_freight?.added_amount ?? null,
                        given_amount: initial?.carriers?.sea_freight?.given_amount ?? null
                    }
                },

                get isAdditionalCurrency() {
                    const c = (this.currency || 'USD').toUpperCase();
                    return c !== 'USD' && c !== 'AED';
                },

                get currentCurrencyRate() {
                    const c = (this.currency || 'USD').toUpperCase();
                    return this.currenciesMap[c]?.exchange_rate || 1.0;
                },

                get currentCurrencyRateText() {
                    const c = (this.currency || 'USD').toUpperCase();
                    const r = this.currentCurrencyRate;
                    return `1 USD = ${Number(r).toFixed(4)} ${c}`;
                },

                get isWeightOnly() {
                    return this.documentType === 'packing_list' || this.documentType === 'reserve' || this.documentType === 'delivery_note';
                },

                get isQuantityOnly() {
                    return this.documentType === 'supplier_order' || this.documentType === 'factory_invoice' || (this.documentNumber && this.documentNumber.toUpperCase().startsWith('B'));
                },

                get orderSheetGroups() {
                    const map = {};
                    this.items.forEach(it => {
                        if (!it.item_code && !it.description) return;
                        const ref = (it.order_sheet_reference || '').trim() || 'Direct / Unassigned';
                        if (!map[ref]) {
                            map[ref] = {
                                ref: ref,
                                isDirect: !it.order_sheet_reference || !it.order_sheet_reference.trim(),
                                count: 0,
                                totalQty: 0,
                                totalAmount: 0
                            };
                        }
                        map[ref].count++;
                        map[ref].totalQty += parseFloat(it.unit_amount) || 0;
                        map[ref].totalAmount += parseFloat(it.total_amount) || 0;
                    });
                    return Object.values(map);
                },

                get orderSheetGroupList() {
                    const refs = new Set();
                    (this.activeOrderSheetGroups || []).forEach(r => {
                        const trimmed = (r || '').trim();
                        if (trimmed) refs.add(trimmed);
                    });
                    (this.items || []).forEach(it => {
                        const trimmed = (it.order_sheet_reference || '').trim();
                        if (trimmed) refs.add(trimmed);
                    });
                    return Array.from(refs).map(ref => ({
                        ref: ref,
                        data: this.orderSheetsData[ref] || null,
                        loading: Boolean(this.loadingOrderSheets[ref])
                    }));
                },

                getItemsForGroup(ref) {
                    const list = [];
                    (this.items || []).forEach((item, index) => {
                        const itemRef = (item.order_sheet_reference || '').trim();
                        if (!ref) {
                            if (!itemRef) list.push({ index, item });
                        } else {
                            if (itemRef.toUpperCase() === ref.toUpperCase()) list.push({ index, item });
                        }
                    });
                    return list;
                },

                getGroupStats(ref) {
                    const entries = this.getItemsForGroup(ref);
                    let totalQty = 0;
                    let totalAmount = 0;
                    let totalWeight = 0;
                    entries.forEach(e => {
                        totalQty += parseFloat(e.item.unit_amount) || 0;
                        totalAmount += parseFloat(e.item.total_amount) || 0;
                        totalWeight += parseFloat(e.item.total_weight) || 0;
                    });
                    return { count: entries.length, totalQty, totalAmount, totalWeight };
                },

                getGroupSummaryText(ref) {
                    const stats = this.getGroupStats(ref);
                    const parts = [`${stats.count} item(s)`, `${stats.totalQty.toLocaleString('en-US')} units`];
                    if (stats.totalAmount > 0) {
                        parts.push(`${this.currency} ${Number(stats.totalAmount).toFixed(2)}`);
                    }
                    return parts.join(' • ');
                },

                getRemainingQtyForGroupItem(ref, itemCode) {
                    if (!ref || !itemCode) return null;
                    const os = this.orderSheetsData[ref];
                    if (!os || !os.items) return null;
                    const match = os.items.find(i => (i.item_code || '').trim().toUpperCase() === (itemCode || '').trim().toUpperCase());
                    return match ? {
                        remaining: (match.remaining_qty !== undefined && match.remaining_qty !== null) ? match.remaining_qty : match.unit_amount,
                        ordered: (match.ordered_qty !== undefined && match.ordered_qty !== null) ? match.ordered_qty : match.unit_amount
                    } : null;
                },

                async fetchOrderSheetData(ref) {
                    ref = (ref || '').trim();
                    if (!ref || this.orderSheetsData[ref] || this.loadingOrderSheets[ref]) return;
                    this.loadingOrderSheets[ref] = true;
                    try {
                        const res = await fetch(`/api/documents/source-data/${encodeURIComponent(ref)}`);
                        if (res.ok) {
                            const data = await res.json();
                            this.orderSheetsData[ref] = data;
                            if (!this.companyName && data.company_name) {
                                this.companyName = data.company_name;
                                if (typeof this.fetchLatestPiForCustomer === 'function') {
                                    this.fetchLatestPiForCustomer();
                                }
                            }
                            if (!this.country && data.country) this.country = data.country;
                            if (!this.address && data.address) this.address = data.address;
                            if (!this.contactDetails && data.contact_details) this.contactDetails = data.contact_details;
                        }
                    } catch (e) {
                        console.error('Failed to fetch order sheet data:', e);
                    } finally {
                        this.loadingOrderSheets[ref] = false;
                    }
                },

                async addOrderSheetGroup(ref) {
                    ref = (ref || '').trim();
                    if (!ref) {
                        await window.systemAlert('Please enter or select an Order Sheet number.', { title: 'Missing Order Sheet', type: 'warning' });
                        return;
                    }
                    if (!this.activeOrderSheetGroups.map(r => r.toUpperCase()).includes(ref.toUpperCase())) {
                        this.activeOrderSheetGroups.push(ref);
                    }
                    this.newOrderSheetRef = '';
                    await this.fetchOrderSheetData(ref);

                    const existing = this.getItemsForGroup(ref);
                    if (existing.length === 0) {
                        this.addItemToGroup(ref);
                    }
                },

                addItemToGroup(ref) {
                    this.items.push({
                        type: 'item',
                        item_code: '',
                        order_sheet_reference: (ref || '').trim(),
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: '',
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false,
                        price_editable: false,
                        price_list: '',
                        is_fallback: false
                    });
                },

                addDirectItem() {
                    this.showDirectItems = true;
                    this.items.push({
                        type: 'item',
                        item_code: '',
                        order_sheet_reference: '',
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: '',
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false,
                        price_editable: false,
                        price_list: '',
                        is_fallback: false
                    });
                },

                async addAllRemainingFromOrderSheet(ref) {
                    ref = (ref || '').trim();
                    if (!ref) return;
                    if (!this.orderSheetsData[ref]) {
                        await this.fetchOrderSheetData(ref);
                    }
                    const osData = this.orderSheetsData[ref];
                    if (!osData || !osData.items || osData.items.length === 0) {
                        await window.systemAlert(`No items found for Order Sheet ${ref}.`, { title: 'No Items Found', type: 'info' });
                        return;
                    }

                    // Remove blank rows in this group if any
                    for (let i = this.items.length - 1; i >= 0; i--) {
                        const it = this.items[i];
                        if ((it.order_sheet_reference || '').trim().toUpperCase() === ref.toUpperCase() && !it.item_code && !it.description) {
                            this.items.splice(i, 1);
                        }
                    }

                    let addedCount = 0;
                    osData.items.forEach(osItem => {
                        const remQty = (osItem.remaining_qty !== undefined && osItem.remaining_qty !== null && osItem.remaining_qty !== '')
                            ? parseFloat(osItem.remaining_qty)
                            : (parseFloat(osItem.ordered_qty) || parseFloat(osItem.unit_amount) || 1);
                        const qty = remQty > 0 ? remQty : (parseFloat(osItem.ordered_qty) || parseFloat(osItem.unit_amount) || 1);
                        const price = (osItem.unit_price !== undefined && osItem.unit_price !== null && osItem.unit_price !== '') ? osItem.unit_price : '';
                        const weight = parseFloat(osItem.unit_weight) || 0;

                        this.items.push({
                            type: 'item',
                            item_code: osItem.item_code,
                            order_sheet_reference: ref,
                            description: osItem.description || '',
                            calc_mode: 'fixed',
                            percentage: null,
                            unit_amount: qty,
                            unit_price: price,
                            total_amount: (price !== '') ? (qty * parseFloat(price)) : 0,
                            unit_weight: weight,
                            total_weight: weight * qty,
                            price_from_tracker: false,
                            price_editable: false,
                            price_list: osItem.price_list || '',
                            is_fallback: Boolean(osItem.is_fallback)
                        });
                        addedCount++;
                    });

                    this.recalcTotals();
                    window.showToast?.(`Added ${addedCount} item(s) from Order Sheet ${ref}`, 'success');
                },

                async removeOrderSheetGroup(ref) {
                    ref = (ref || '').trim();
                    const groupItems = this.getItemsForGroup(ref);
                    if (groupItems.length > 0) {
                        const confirmed = await window.systemConfirm({
                            title: `Remove Order Sheet ${ref}?`,
                            message: `This will remove Order Sheet ${ref} and all its ${groupItems.length} item(s) from this factory invoice. Are you sure?`,
                            confirmText: 'Remove Group',
                            type: 'danger'
                        });
                        if (!confirmed) return;
                    }

                    for (let i = this.items.length - 1; i >= 0; i--) {
                        if ((this.items[i].order_sheet_reference || '').trim().toUpperCase() === ref.toUpperCase()) {
                            this.items.splice(i, 1);
                        }
                    }

                    this.activeOrderSheetGroups = this.activeOrderSheetGroups.filter(r => r.toUpperCase() !== ref.toUpperCase());

                    if (this.items.length === 0) {
                        this.addItem();
                    }

                    this.recalcTotals();
                },

                onOrderSheetItemCodeSelected(item, ref) {
                    ref = (ref || '').trim();
                    const osData = this.orderSheetsData[ref];
                    if (!osData || !osData.items) return;
                    const match = osData.items.find(i => (i.item_code || '').trim().toUpperCase() === (item.item_code || '').trim().toUpperCase());
                    if (match) {
                        if (!item.description || item.description.trim() === '') {
                            item.description = match.description || '';
                        }
                        if (item.unit_amount === '' || item.unit_amount === null || parseFloat(item.unit_amount) === 0) {
                            item.unit_amount = (match.remaining_qty !== undefined && match.remaining_qty !== null && match.remaining_qty !== '')
                                ? match.remaining_qty
                                : (match.ordered_qty ?? match.unit_amount ?? '');
                        }
                        if ((item.unit_price === '' || item.unit_price === null) && match.unit_price !== undefined) {
                            item.unit_price = match.unit_price;
                        }
                        if (match.unit_weight) {
                            item.unit_weight = match.unit_weight;
                        }
                        this.recalcItem(item);
                    }
                },

                get filteredPriceLists() {
                    const docCurr = (this.currency || 'USD').toUpperCase();
                    return this.availablePriceLists.filter(list => {
                        if (!list) return false;
                        const upper = list.toUpperCase();
                        if (docCurr === 'AED') {
                            if (upper.includes('USD')) return false;
                        } else {
                            if (upper.includes('AED')) return false;
                        }
                        return true;
                    });
                },

                get filteredPriceLabels() {
                    const docCurr = (this.currency || 'USD').toUpperCase();
                    return this.availablePriceLabels.filter(lbl => {
                        if (!lbl) return false;
                        const upper = lbl.toUpperCase();
                        if (docCurr === 'AED') {
                            if (upper.includes('USD')) return false;
                        } else {
                            if (upper.includes('AED')) return false;
                        }
                        return true;
                    });
                },

                get calculatedItemsNetWeight() {
                    return this.items.reduce((sum, it) => sum + (parseFloat(it.total_weight) || 0), 0);
                },

                get totalQuantity() {
                    return this.items.reduce((sum, it) => {
                        if (this.isAdjustment(it)) return sum;
                        const qty = parseFloat(it.unit_amount) || 0;
                        return sum + qty;
                    }, 0);
                },

                get formattedTotalQuantity() {
                    const qty = Math.round(this.totalQuantity * 1000) / 1000;
                    return (Math.floor(qty) === qty) ? qty.toLocaleString('en-US') : qty.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                syncWeightFromItems() {
                    if (this.calculatedItemsNetWeight > 0) {
                        this.netWeight = Math.round(this.calculatedItemsNetWeight * 1000) / 1000;
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
                    return parseFloat(this.grossWeight) || 0;
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
                    pkg.volumetric_weight_kg = 0;

                    if (pkg.dimension_type === 'diameter') {
                        const dia = parseFloat(pkg.diameter_cm) || 0;
                        if (dia > 0 && h > 0) {
                            const r = dia / 2;
                            pkg.cbm = Math.round((Math.PI * r * r * h / 1000000) * qty * 10000) / 10000;
                        } else {
                            pkg.cbm = 0;
                        }
                    } else {
                        const l = parseFloat(pkg.length_cm) || 0;
                        const w = parseFloat(pkg.width_cm) || 0;
                        if (l > 0 && w > 0 && h > 0) {
                            pkg.cbm = Math.round(((l * w * h) / 1000000) * qty * 10000) / 10000;
                        } else {
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

                recalcCarrier(method, isGivenAmountManual = false, isSystemAmountManual = false) {
                    const c = this.carriers[method];
                    if (!c) return;

                    if (!isSystemAmountManual) {
                        const rate = parseFloat(c.rate_per_kg);
                        if (rate > 0) {
                            const wt = (c.checked_weight !== null && c.checked_weight !== '')
                                ? parseFloat(c.checked_weight) || 0
                                : this.chargeableWeight;
                            c.system_amount = Math.round(wt * rate * 100) / 100;
                        }
                    }

                    const sys = parseFloat(c.system_amount) || 0;
                    const added = parseFloat(c.added_amount) || 0;

                    // Automatically fill given_amount with System Amount + Added Amount
                    if (!isGivenAmountManual) {
                        if (sys > 0 || added > 0) {
                            c.given_amount = Math.round((sys + added) * 100) / 100;
                        } else if (c.rate_per_kg !== null && c.rate_per_kg !== '') {
                            c.given_amount = 0;
                        }
                    }

                    const freightVal = (c.given_amount !== null && c.given_amount !== '' && !isNaN(c.given_amount))
                        ? (parseFloat(c.given_amount) || 0)
                        : Math.round((sys + added) * 100) / 100;

                    // Automatically apply this carrier when its freight values are entered/calculated
                    if (freightVal > 0) {
                        this.selectedCarrier = method;
                    }

                    this.recalcTotals();
                },

                recalcAllCarriers() {
                    const currentSelected = this.selectedCarrier;
                    ['dhl', 'air_freight', 'sea_freight'].forEach(m => {
                        const c = this.carriers[m];
                        if (!c) return;
                        const rate = parseFloat(c.rate_per_kg);
                        if (rate > 0) {
                            const wt = (c.checked_weight !== null && c.checked_weight !== '')
                                ? parseFloat(c.checked_weight) || 0
                                : this.chargeableWeight;
                            c.system_amount = Math.round(wt * rate * 100) / 100;
                        }
                        const sys = parseFloat(c.system_amount) || 0;
                        const added = parseFloat(c.added_amount) || 0;
                        if (sys > 0 || added > 0) {
                            c.given_amount = Math.round((sys + added) * 100) / 100;
                        }
                    });

                    if (currentSelected && this.carriers[currentSelected]) {
                        this.selectedCarrier = currentSelected;
                    } else {
                        for (const m of ['dhl', 'air_freight', 'sea_freight']) {
                            if ((parseFloat(this.carriers[m]?.given_amount) || 0) > 0) {
                                this.selectedCarrier = m;
                                break;
                            }
                        }
                    }

                    this.recalcTotals();
                },

                toggleCarrierFreight(carrier) {
                    if (this.selectedCarrier === carrier) {
                        this.selectedCarrier = null;
                    } else {
                        this.selectedCarrier = carrier;
                        const c = this.carriers[carrier];
                        if (c) {
                            const sys = parseFloat(c.system_amount) || 0;
                            const added = parseFloat(c.added_amount) || 0;
                            if (sys > 0 || added > 0) {
                                c.given_amount = Math.round((sys + added) * 100) / 100;
                            }
                        }
                    }
                    this.recalcTotals();
                },

                applyFreightToTotal(amount, carrier) {
                    this.selectedCarrier = carrier;
                    if (amount !== undefined && amount !== null && this.carriers[carrier]) {
                        this.carriers[carrier].given_amount = amount;
                    }
                    this.recalcTotals();
                },

                async triggerImport() {
                    const docCode = (this.sourceInput || '').trim();
                    if (!docCode) {
                        await window.systemAlert('Please select or enter a source document code to import.', { title: 'Missing Source Document', type: 'warning' });
                        return;
                    }

                    const hasExistingItems = this.items.some(it => (it.item_code || '').trim().length > 0);
                    let appendMode = false;

                    if (hasExistingItems) {
                        const choice = await window.systemConfirm({
                            title: 'Combine or Replace Items?',
                            message: `You already have items entered. Would you like to Append items from ${docCode} (ideal for combining multiple order sheets into 1 factory invoice), or Replace all existing items?`,
                            confirmText: 'Append Items',
                            cancelText: 'Replace All',
                            type: 'primary'
                        });
                        appendMode = choice;
                    } else {
                        const confirmed = await window.systemConfirm({
                            title: 'Import Document Details',
                            message: `Are you sure you want to import details from ${docCode}? This will populate customer details, shipment charges, line items, and packaging.`,
                            confirmText: 'Import Details',
                            type: 'primary'
                        });
                        if (!confirmed) {
                            return;
                        }
                    }

                    this.isImporting = true;
                    this.importError = '';
                    this.importMessage = '';

                    try {
                        const res = await fetch(`/api/documents/source-data/${encodeURIComponent(docCode)}`);
                        if (!res.ok) {
                            const errData = await res.json();
                            throw new Error(errData.error || 'Failed to find source document');
                        }
                        const data = await res.json();

                        // Import customer / recipient data if not already set or not appending
                        if (!appendMode || !this.companyName) {
                            if (data.company_name !== undefined && data.company_name !== null) {
                                this.companyName = data.company_name;
                                this.fetchLatestPiForCustomer();
                            }
                            if (data.country !== undefined && data.country !== null) this.country = data.country;
                            if (data.address !== undefined && data.address !== null) this.address = data.address;
                            if (data.contact_details !== undefined && data.contact_details !== null) this.contactDetails = data.contact_details;
                            if (data.currency) this.currency = data.currency;
                        }

                        // Maintain multi-order references in sourceDocumentNumber
                        if (appendMode && this.sourceDocumentNumber) {
                            const currentSources = this.sourceDocumentNumber.split(',').map(s => s.trim());
                            if (!currentSources.includes(data.document_number)) {
                                currentSources.push(data.document_number);
                                this.sourceDocumentNumber = currentSources.join(', ');
                            }
                        } else {
                            this.sourceDocumentId = data.id;
                            this.sourceDocumentNumber = data.document_number;
                        }

                        if (data.document_type === 'supplier_order' || (data.document_number && data.document_number.toUpperCase().startsWith('B'))) {
                            if (!this.activeOrderSheetGroups.map(r => r.toUpperCase()).includes(data.document_number.toUpperCase())) {
                                this.activeOrderSheetGroups.push(data.document_number);
                            }
                            this.orderSheetsData[data.document_number] = data;
                        }

                        // Import shipment charges
                        if (!appendMode && data.shipment_costs) {
                            ['dhl', 'air_freight', 'sea_freight'].forEach(m => {
                                const sc = data.shipment_costs[m];
                                if (sc) {
                                    this.carriers[m].checked_weight = sc.checked_weight !== null ? sc.checked_weight : null;
                                    this.carriers[m].rate_per_kg = sc.rate_per_kg !== null ? sc.rate_per_kg : null;
                                    this.carriers[m].system_amount = sc.system_amount !== null ? sc.system_amount : null;
                                    this.carriers[m].added_amount = sc.added_amount !== null ? sc.added_amount : null;
                                    this.carriers[m].given_amount = sc.given_amount !== null ? sc.given_amount : null;
                                } else {
                                    this.carriers[m].checked_weight = null;
                                    this.carriers[m].rate_per_kg = null;
                                    this.carriers[m].system_amount = null;
                                    this.carriers[m].added_amount = null;
                                    this.carriers[m].given_amount = null;
                                }
                            });
                        }

                        // Import line items
                        if (data.items && data.items.length > 0) {
                            const mappedItems = data.items.map(it => {
                                const price = parseFloat(it.unit_price) || 0;
                                const code = (it.item_code || '').toUpperCase();
                                const isDisc = price < 0 || code === 'DISCOUNT';
                                const isTax = code === 'TAX' || code === 'VAT';
                                const isAdd = code === 'ADDITION';
                                const desc = it.description || '';
                                const pctMatch = desc.match(/(\d+(?:\.\d+)?)\s*%/);
                                const pct = pctMatch ? parseFloat(pctMatch[1]) : (isTax ? 5 : null);
                                return {
                                    type: isDisc ? 'discount' : (isTax ? 'tax' : (isAdd ? 'addition' : 'item')),
                                    item_code: it.item_code || '',
                                    order_sheet_reference: it.order_sheet_reference || (data.document_type === 'supplier_order' || (data.document_number && data.document_number.toUpperCase().startsWith('B')) ? data.document_number : (it.source_order || '')),
                                    description: it.description || '',
                                    calc_mode: pct ? 'percentage' : 'fixed',
                                    percentage: pct,
                                    unit_amount: (it.unit_amount !== undefined && it.unit_amount !== null && it.unit_amount !== '') ? it.unit_amount : '',
                                    unit_price: (it.unit_price !== undefined && it.unit_price !== null && it.unit_price !== '') ? it.unit_price : '',
                                    unit_weight: it.unit_weight || 0,
                                    total_weight: it.total_weight || (it.unit_weight * it.unit_amount) || 0,
                                    total_amount: it.total_amount || (it.unit_amount * it.unit_price) || 0,
                                    price_from_tracker: false,
                                    price_editable: false,
                                    price_list: it.price_list || '',
                                    is_fallback: Boolean(it.is_fallback)
                                };
                            });

                            if (appendMode) {
                                const existing = this.items.filter(it => (it.item_code || '').trim().length > 0);
                                this.items = [...existing, ...mappedItems];
                            } else {
                                this.items = mappedItems;
                            }

                            this.items.forEach(it => this.recalcItem(it));
                        }

                        // Import packages if present
                        if (data.packages && data.packages.length > 0) {
                            const mappedPkgs = data.packages.map(p => ({
                                package_type: p.package_type || 'Carton',
                                dimension_type: p.dimension_type || 'standard',
                                length_cm: p.length_cm,
                                width_cm: p.width_cm,
                                height_cm: p.height_cm,
                                diameter_cm: p.diameter_cm,
                                quantity: p.quantity || 1,
                                gross_weight_per_pkg_kg: p.gross_weight_per_pkg_kg,
                                volumetric_weight_kg: p.volumetric_weight_kg || 0,
                                cbm: p.cbm || 0
                            }));

                            if (appendMode) {
                                this.packages = [...this.packages, ...mappedPkgs];
                            } else {
                                this.packages = mappedPkgs;
                            }

                            this.packages.forEach(p => this.recalcPackage(p));
                        }

                        if (!appendMode) {
                            if (data.total_net_weight) this.netWeight = data.total_net_weight;
                            if (data.total_gross_weight) this.grossWeight = data.total_gross_weight;
                        }

                        this.recalcTotals();
                        this.recalcAllCarriers();
                        const itemsCount = data.items ? data.items.length : 0;
                        this.importMessage = appendMode
                            ? `Successfully appended ${itemsCount} items from ${data.document_number}. Total items: ${this.items.length}.`
                            : `Successfully imported ${itemsCount} items, packaging & shipment charges from ${data.document_number} (${data.company_name})!`;
                    } catch (err) {
                        this.importError = err.message || 'Error importing source document.';
                    } finally {
                        this.isImporting = false;
                    }
                },

                async fetchLatestPiForCustomer() {
                    const name = this.companyName ? this.companyName.trim() : '';
                    if (name.length < 2) {
                        this.latestPiDoc = null;
                        return;
                    }

                    this.isCheckingPi = true;
                    try {
                        const params = new URLSearchParams({
                            company_name: name,
                            price_list: this.selectedPriceList || '',
                            price_label: this.selectedPriceLabel || ''
                        });
                        const res = await fetch(`/api/documents/latest-pi?${params.toString()}`);
                        const data = await res.json();
                        if (data.found) {
                            this.latestPiDoc = data;
                            if (data.price_label && this.selectedPriceLabel !== data.price_label) {
                                const filtered = this.filteredPriceLabels;
                                if (filtered.includes(data.price_label) && (!this.selectedPriceLabel || this.selectedPriceLabel === 'USD 30%' || this.selectedPriceLabel === 'AED 30%')) {
                                    this.selectedPriceLabel = data.price_label;
                                    this.batchRepriceAllItems(true);
                                }
                            }
                        } else {
                            this.latestPiDoc = null;
                        }
                    } catch (e) {
                        console.error('Failed to fetch latest PI for customer', e);
                        this.latestPiDoc = null;
                    } finally {
                        this.isCheckingPi = false;
                    }
                },

                importFromLatestPi() {
                    if (!this.latestPiDoc) return;
                    if (this.latestPiDoc.country) {
                        this.country = this.latestPiDoc.country;
                    }
                    if (this.latestPiDoc.currency && this.currency !== this.latestPiDoc.currency) {
                        this.currency = this.latestPiDoc.currency;
                        this.onCurrencyChanged();
                    }
                    if (this.latestPiDoc.price_label) {
                        this.selectedPriceLabel = this.latestPiDoc.price_label;
                    }
                    if (this.latestPiDoc.price_list) {
                        this.selectedPriceList = this.latestPiDoc.price_list;
                    }
                    this.onPriceTierChanged();
                    window.showToast?.(`Applied settings from ${this.latestPiDoc.document_number} (Price Label: ${this.selectedPriceLabel || 'None'}, Country: ${this.country}, Currency: ${this.currency})!`, 'info');
                },

                applyLatestPiPriceLabel() {
                    if (!this.latestPiDoc) return;
                    if (this.latestPiDoc.price_label) {
                        this.selectedPriceLabel = this.latestPiDoc.price_label;
                    }
                    if (this.latestPiDoc.price_list) {
                        this.selectedPriceList = this.latestPiDoc.price_list;
                    }
                    this.onPriceTierChanged();
                    window.showToast?.(`Applied ${this.latestPiDoc.price_label || this.latestPiDoc.price_list} pricing from ${this.latestPiDoc.document_number}!`, 'info');
                },

                applyLatestPiPriceList() {
                    this.applyLatestPiPriceLabel();
                },

                init() {
                    this.items.forEach(it => this.recalcItem(it));
                    this.packages.forEach(p => this.recalcPackage(p));
                    this.recalcTotals();
                    this.initPriceLabels();
                    if (this.companyName && this.companyName.trim().length >= 2) {
                        this.fetchLatestPiForCustomer();
                    }
                    if (this.documentType) {
                        this.loadChecklistsForType(this.documentType);
                    }
                    if (this.documentType === 'factory_invoice') {
                        const refs = new Set();
                        if (this.sourceDocumentNumber) {
                            this.sourceDocumentNumber.split(',').map(s => s.trim()).filter(Boolean).forEach(r => refs.add(r));
                        }
                        this.items.forEach(it => {
                            const r = (it.order_sheet_reference || '').trim();
                            if (r) refs.add(r);
                        });
                        this.activeOrderSheetGroups = Array.from(refs);
                        this.activeOrderSheetGroups.forEach(ref => this.fetchOrderSheetData(ref));
                    }
                    this.checkSavedDraft();
                    this.autoSaveTimer = setInterval(() => {
                        this.saveDraft();
                    }, 8000);
                },

                async initPriceLabels() {
                    try {
                        const res = await fetch('/api/price-items/labels');
                        const data = await res.json();
                        if (data.price_labels && data.price_labels.length > 0) {
                            this.availablePriceLabels = data.price_labels;
                        }
                        if (data.price_lists && data.price_lists.length > 0) {
                            this.availablePriceLists = data.price_lists;
                        }
                        if (this.selectedPriceLabel) {
                            const filtered = this.filteredPriceLabels;
                            if (!filtered.includes(this.selectedPriceLabel)) {
                                this.selectedPriceLabel = filtered[0] || '';
                            }
                        }
                    } catch (e) {
                        console.error('Failed to load price labels', e);
                    }
                },

                onCurrencyChanged() {
                    const docCurr = (this.currency || 'USD').toUpperCase();
                    const labels = this.filteredPriceLabels;
                    if (this.selectedPriceLabel) {
                        const upper = this.selectedPriceLabel.toUpperCase();
                        const hasOtherCurr = docCurr === 'AED' ? upper.includes('USD') : upper.includes('AED');
                        if (hasOtherCurr) {
                            const pctMatch = this.selectedPriceLabel.match(/(\d+%)/);
                            let match = null;
                            if (pctMatch) {
                                match = labels.find(l => l.includes(pctMatch[1]));
                            }
                            this.selectedPriceLabel = match || labels[0] || '';
                        }
                    }
                    const lists = this.filteredPriceLists;
                    if (this.selectedPriceList && !lists.includes(this.selectedPriceList)) {
                        this.selectedPriceList = '';
                    }
                    this.conversionMessage = '';
                    if (docCurr === 'USD' || docCurr === 'AED') {
                        this.batchRepriceAllItems();
                    }
                },

                async convertPricesToCurrency() {
                    const targetCurr = (this.currency || 'USD').toUpperCase();
                    if (targetCurr === 'USD' || targetCurr === 'AED') {
                        return;
                    }

                    this.isConvertingCurrency = true;
                    this.conversionMessage = '';

                    try {
                        let rate = this.currentCurrencyRate;
                        if (!this.currenciesMap[targetCurr] || !rate || rate === 1.0) {
                            const res = await fetch(`/api/currencies/rate?from=USD&to=${targetCurr}`);
                            const data = await res.json();
                            if (data && data.rate) {
                                rate = data.rate;
                                if (this.currenciesMap[targetCurr]) {
                                    this.currenciesMap[targetCurr].exchange_rate = rate;
                                }
                            }
                        }

                        rate = parseFloat(rate) || 1.0;
                        let count = 0;
                        this.items.forEach(item => {
                            if (item.unit_price !== undefined && item.unit_price !== null && item.unit_price !== '') {
                                const currentPrice = parseFloat(item.unit_price) || 0;
                                if (item.base_usd_price === undefined || item.base_usd_price === null) {
                                    item.base_usd_price = currentPrice;
                                }
                                const newPrice = Math.round((item.base_usd_price * rate) * 100) / 100;
                                item.unit_price = newPrice.toFixed(2);
                                this.calculateItemTotal(item);
                                count++;
                            }
                        });

                        this.calculateTotals();
                        this.conversionMessage = `Converted ${count} item(s) to ${targetCurr} (Rate: 1 USD = ${Number(rate).toFixed(4)} ${targetCurr})`;
                    } catch (e) {
                        console.error('Currency conversion error', e);
                        this.conversionMessage = 'Failed to convert prices. Please try again.';
                    } finally {
                        this.isConvertingCurrency = false;
                    }
                },

                async onItemCodeInput(item, index) {
                    const q = item.item_code ? item.item_code.trim() : '';
                    if (q.length < 1) {
                        this.itemSuggestions[index] = [];
                        return;
                    }

                    try {
                        const params = new URLSearchParams({
                            q: q,
                            price_label: this.selectedPriceLabel || '',
                            price_list: this.selectedPriceList || '',
                            currency: this.currency || ''
                        });
                        const res = await fetch(`/api/price-items/search?${params.toString()}`);
                        const data = await res.json();
                        this.itemSuggestions[index] = data.items || [];
                    } catch (e) {
                        console.error('Item suggestions fetch error', e);
                    }

                    this.lookupItemPrice(item);
                },

                async lookupItemPrice(item) {
                    const code = item.item_code ? item.item_code.trim() : '';
                    if (!code) return;

                    try {
                        const params = new URLSearchParams({
                            item_code: code,
                            price_label: this.selectedPriceLabel || '',
                            price_list: this.selectedPriceList || '',
                            currency: this.currency || ''
                        });
                        const res = await fetch(`/api/price-items/lookup?${params.toString()}`);
                        const data = await res.json();

                        if (data.found) {
                            if (data.description && !item.description) {
                                item.description = data.description;
                            }
                            if (data.unit_weight !== null && data.unit_weight !== undefined && (!item.unit_weight || item.unit_weight === 0)) {
                                item.unit_weight = parseFloat(data.unit_weight);
                            }
                            if (!this.isWeightOnly && !this.isQuantityOnly && data.unit_price !== null && data.unit_price !== undefined) {
                                item.unit_price = parseFloat(data.unit_price);
                                item.price_from_tracker = true;
                            }
                            item.price_list = data.price_list || '';
                            item.is_fallback = Boolean(data.is_fallback);
                            this.recalcItem(item);
                        } else {
                            item.price_list = '';
                            item.is_fallback = false;
                        }
                    } catch (e) {
                        console.error('Item price lookup error', e);
                    }
                },

                async batchRepriceAllItems(silent = false) {
                    const codes = this.items
                        .map(it => (it.item_code || '').trim())
                        .filter(code => code.length > 0 && !this.isAdjustment({ item_code: code }));

                    if (codes.length === 0) return;

                    this.isRepricing = true;
                    try {
                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
                        const res = await fetch('/api/price-items/batch-lookup', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                item_codes: codes,
                                price_label: this.selectedPriceLabel || '',
                                price_list: this.selectedPriceList || '',
                                currency: this.currency || ''
                            })
                        });
                        const data = await res.json();
                        const results = data.results || {};

                        let updatedCount = 0;
                        this.items.forEach(item => {
                            if (this.isAdjustment(item)) return;
                            const code = (item.item_code || '').trim();
                            if (!code) return;

                            const match = results[code];
                            if (match && match.found) {
                                if (match.description && !item.description) {
                                    item.description = match.description;
                                }
                                if (match.unit_weight !== null && match.unit_weight !== undefined && (!item.unit_weight || item.unit_weight === 0)) {
                                    item.unit_weight = parseFloat(match.unit_weight);
                                }
                                if (!this.isWeightOnly && !this.isQuantityOnly && match.unit_price !== null && match.unit_price !== undefined) {
                                    if (!item.price_editable) {
                                        item.unit_price = parseFloat(match.unit_price);
                                        item.price_from_tracker = true;
                                    }
                                }
                                item.price_list = match.price_list || '';
                                item.is_fallback = Boolean(match.is_fallback);
                                this.recalcItem(item);
                                updatedCount++;
                            }
                        });

                        this.recalcTotals();
                        if (updatedCount > 0 && !silent) {
                            window.showToast?.(`Updated ${updatedCount} items with ${this.selectedPriceLabel || this.selectedPriceList || 'pricing & weights'}!`, 'info');
                        }
                    } catch (e) {
                        console.error('Batch reprice error', e);
                    } finally {
                        this.isRepricing = false;
                    }
                },

                async repriceAllLineItems() {
                    return this.batchRepriceAllItems();
                },

                onPriceTierChanged() {
                    this.batchRepriceAllItems();
                },

                isAdjustment(it) {
                    if (!it) return false;
                    const code = (it.item_code || '').trim().toUpperCase();
                    return it.type === 'discount' || it.type === 'tax' || it.type === 'addition' || ['DISCOUNT', 'DISC', 'TAX', 'VAT', 'TAX / VAT', 'TAX/VAT', 'ADDITION', 'ADD', 'SURCHARGE'].includes(code);
                },

                isUnionFallbackItem(item) {
                    if (!item || this.isAdjustment(item)) return false;
                    if (item.is_fallback) return true;
                    const itemPriceList = (item.price_list || '').toLowerCase();
                    const currentPriceList = (this.selectedPriceList || '').toLowerCase();
                    if (itemPriceList.includes('union') && (!currentPriceList || !currentPriceList.includes('union'))) {
                        return true;
                    }
                    return false;
                },

                get itemsBaseTotal() {
                    const sum = this.items
                        .filter(it => !this.isAdjustment(it) && (parseFloat(it.total_amount) || 0) > 0)
                        .reduce((acc, it) => acc + (parseFloat(it.total_amount) || 0), 0);
                    return Math.round(sum * 100) / 100;
                },

                get discountsTotal() {
                    return this.items
                        .filter(it => it.type === 'discount' || (parseFloat(it.total_amount) || 0) < 0)
                        .reduce((sum, it) => sum + Math.abs(parseFloat(it.total_amount) || 0), 0);
                },

                get taxesTotal() {
                    return this.items
                        .filter(it => it.type === 'tax' || (['TAX', 'VAT'].includes((it.item_code || '').toUpperCase()) && (parseFloat(it.total_amount) || 0) > 0))
                        .reduce((sum, it) => sum + (parseFloat(it.total_amount) || 0), 0);
                },

                get additionsTotal() {
                    return this.items
                        .filter(it => it.type === 'addition' || ((it.item_code || '').toUpperCase() === 'ADDITION' && (parseFloat(it.total_amount) || 0) > 0))
                        .reduce((sum, it) => sum + (parseFloat(it.total_amount) || 0), 0);
                },

                addItem() {
                    this.items.push({
                        type: 'item',
                        item_code: '',
                        order_sheet_reference: '',
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: '',
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false,
                        price_editable: false,
                        price_list: '',
                        is_fallback: false
                    });
                },

                togglePriceEdit(item, index) {
                    item.price_editable = !item.price_editable;
                    if (item.price_editable) {
                        this.$nextTick(() => {
                            const el = this.$refs['priceInput_' + index];
                            if (el) {
                                el.focus();
                                el.select();
                            }
                        });
                    }
                },

                addDiscount() {
                    const disc = {
                        type: 'discount',
                        item_code: 'DISCOUNT',
                        description: 'Discount (5%)',
                        calc_mode: 'percentage',
                        percentage: 5,
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    };
                    this.items.push(disc);
                    this.recalcItem(disc);
                },

                addTax() {
                    const tax = {
                        type: 'tax',
                        item_code: 'TAX',
                        description: 'VAT / Tax (5%)',
                        calc_mode: 'percentage',
                        percentage: 5,
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    };
                    this.items.push(tax);
                    this.recalcItem(tax);
                },

                addAddition() {
                    const add = {
                        type: 'addition',
                        item_code: 'ADDITION',
                        description: 'Surcharge (5%)',
                        calc_mode: 'percentage',
                        percentage: 5,
                        unit_amount: 1,
                        unit_price: 0,
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false
                    };
                    this.items.push(add);
                    this.recalcItem(add);
                },

                setCalcMode(item, mode) {
                    item.calc_mode = mode;
                    if (mode === 'percentage') {
                        if (!item.percentage || item.percentage <= 0) {
                            item.percentage = 5;
                        }
                    }
                    this.recalcItem(item);
                },

                async applyLineDiscount(item) {
                    const currentPrice = parseFloat(item.unit_price) || 0;
                    if (currentPrice <= 0) return;
                    const input = await window.systemPrompt(`Enter % discount to apply to unit price of ${item.item_code || 'this item'} (e.g. 10 for 10% off):`, {
                        title: 'Apply Unit Price Discount',
                        defaultValue: '10',
                        placeholder: '10'
                    });
                    if (input !== null) {
                        const pct = parseFloat(input);
                        if (!isNaN(pct) && pct > 0 && pct <= 100) {
                            const discounted = Math.round(currentPrice * (1 - (pct / 100)) * 100) / 100;
                            item.unit_price = discounted;
                            if (!item.description.includes(`(-${pct}%)`)) {
                                item.description = (item.description ? item.description + ` ` : '') + `(-${pct}%)`;
                            }
                            this.recalcItem(item);
                        }
                    }
                },

                removeItem(index) {
                    if (this.items.length > 1) {
                        this.items.splice(index, 1);
                    } else {
                        this.items = [{
                            type: 'item',
                            item_code: '',
                            order_sheet_reference: '',
                            description: '',
                            calc_mode: 'fixed',
                            percentage: null,
                            unit_amount: '',
                            unit_price: '',
                            total_amount: 0,
                            unit_weight: 0,
                            total_weight: 0,
                            price_from_tracker: false,
                            price_editable: false,
                            price_list: '',
                            is_fallback: false
                        }];
                    }
                    this.recalcTotals();
                },

                insertItemAfter(index) {
                    const prevRef = this.items[index]?.order_sheet_reference || '';
                    this.items.splice(index + 1, 0, {
                        type: 'item',
                        item_code: '',
                        order_sheet_reference: prevRef,
                        description: '',
                        calc_mode: 'fixed',
                        percentage: null,
                        unit_amount: '',
                        unit_price: '',
                        total_amount: 0,
                        unit_weight: 0,
                        total_weight: 0,
                        price_from_tracker: false,
                        price_editable: false
                    });
                    this.$nextTick(() => {
                        this.focusGridCell(index + 1, 0);
                    });
                    this.recalcTotals();
                },

                moveItemUp(index) {
                    if (index > 0) {
                        const item = this.items.splice(index, 1)[0];
                        this.items.splice(index - 1, 0, item);
                        this.recalcTotals();
                    }
                },

                moveItemDown(index) {
                    if (index < this.items.length - 1) {
                        const item = this.items.splice(index, 1)[0];
                        this.items.splice(index + 1, 0, item);
                        this.recalcTotals();
                    }
                },

                onRowDragStart(e, index) {
                    this.draggedRowIndex = index;
                    if (e.dataTransfer) {
                        e.dataTransfer.effectAllowed = 'move';
                        e.dataTransfer.setData('text/plain', String(index));
                    }
                },

                onRowDragOver(e, index) {
                    e.preventDefault();
                    if (this.draggedRowIndex === null) return;
                    if (e.dataTransfer) {
                        e.dataTransfer.dropEffect = 'move';
                    }
                    this.dragOverRowIndex = index;
                },

                onRowDragLeave(e, index) {
                    if (this.dragOverRowIndex === index) {
                        this.dragOverRowIndex = null;
                    }
                },

                onRowDragEnd() {
                    this.draggedRowIndex = null;
                    this.dragOverRowIndex = null;
                },

                onRowDrop(e, targetIndex) {
                    e.preventDefault();
                    if (this.draggedRowIndex !== null && this.draggedRowIndex !== targetIndex) {
                        const fromIdx = this.draggedRowIndex;
                        const item = this.items.splice(fromIdx, 1)[0];
                        this.items.splice(targetIndex, 0, item);
                        this.recalcTotals();
                        window.showToast?.(`Moved item from #${fromIdx + 1} to #${targetIndex + 1}`, 'info');
                    }
                    this.draggedRowIndex = null;
                    this.dragOverRowIndex = null;
                },

                onQuantityInput(item) {
                    if (item.unit_amount !== null && item.unit_amount !== undefined) {
                        let val = String(item.unit_amount).replace(/,/g, '.');
                        val = val.replace(/[^0-9.]/g, '');
                        const parts = val.split('.');
                        if (parts.length > 2) {
                            val = parts[0] + '.' + parts.slice(1).join('');
                        }
                        item.unit_amount = val;
                    }
                    this.recalcItem(item);
                },

                onUnitPriceInput(item) {
                    if (this.isAdjustment(item)) {
                        item.calc_mode = 'fixed';
                    }
                    if (item.unit_price !== null && item.unit_price !== undefined) {
                        let raw = String(item.unit_price).replace(/,/g, '.');
                        const isDiscount = item.type === 'discount';
                        const isNegative = isDiscount || raw.trim().startsWith('-');
                        let val = raw.replace(/[^0-9.]/g, '');
                        const parts = val.split('.');
                        if (parts.length > 2) {
                            val = parts[0] + '.' + parts.slice(1).join('');
                        }
                        if (val === '' && isNegative && !isDiscount) {
                            item.unit_price = '-';
                        } else {
                            item.unit_price = (isNegative && val !== '' ? '-' : '') + val;
                        }
                    }
                    this.recalcItem(item);
                },

                onWeightInput(item) {
                    if (item.unit_weight !== null && item.unit_weight !== undefined) {
                        let val = String(item.unit_weight).replace(/,/g, '.');
                        val = val.replace(/[^0-9.]/g, '');
                        const parts = val.split('.');
                        if (parts.length > 2) {
                            val = parts[0] + '.' + parts.slice(1).join('');
                        }
                        item.unit_weight = val;
                    }
                    this.recalcItem(item);
                },

                recalcItem(item) {
                    if (this.isAdjustment(item)) {
                        item.unit_amount = 1;
                    }
                    const rawQty = (item.unit_amount !== '' && item.unit_amount !== null) ? parseFloat(item.unit_amount) : (this.isAdjustment(item) ? 1 : 0);

                    if (item.calc_mode === 'percentage') {
                        const pct = parseFloat(item.percentage) || 0;
                        const base = this.itemsBaseTotal;
                        const val = Math.round((base * (pct / 100)) * 100) / 100;

                        if (item.type === 'discount') {
                            item.unit_price = -val;
                            if (!item.description || item.description.startsWith('Discount')) {
                                item.description = pct > 0 ? `Discount (${pct}%)` : 'Discount';
                            }
                        } else if (item.type === 'tax') {
                            item.unit_price = val;
                            if (!item.description || item.description.startsWith('VAT') || item.description.startsWith('Tax')) {
                                item.description = pct > 0 ? `VAT / Tax (${pct}%)` : 'VAT / Tax';
                            }
                        } else if (item.type === 'addition') {
                            item.unit_price = val;
                            if (!item.description || item.description.startsWith('Surcharge')) {
                                item.description = pct > 0 ? `Surcharge (${pct}%)` : 'Surcharge';
                            }
                        }
                    } else {
                        let price = (item.unit_price !== '' && item.unit_price !== null) ? parseFloat(item.unit_price) : 0;
                        if (item.type === 'discount' && price > 0) {
                            item.unit_price = -price;
                        }
                    }

                    const price = parseFloat(item.unit_price) || 0;
                    item.total_amount = Math.round(rawQty * price * 100) / 100;
                    const unitWt = parseFloat(item.unit_weight) || 0;
                    item.total_weight = Math.round(rawQty * unitWt * 1000) / 1000;
                    this.recalcTotals();
                },

                get appliedFreightAmount() {
                    if (!this.selectedCarrier || !this.carriers[this.selectedCarrier]) return 0;
                    const c = this.carriers[this.selectedCarrier];
                    const given = parseFloat(c.given_amount);
                    if (!isNaN(given) && given > 0) return Math.round(given * 100) / 100;
                    const sys = parseFloat(c.system_amount) || 0;
                    const added = parseFloat(c.added_amount) || 0;
                    return (sys + added > 0) ? Math.round((sys + added) * 100) / 100 : 0;
                },

                get selectedCarrierName() {
                    if (!this.selectedCarrier) return '';
                    if (this.selectedCarrier === 'dhl') return 'DHL Express';
                    if (this.selectedCarrier === 'air_freight') return 'Air Freight';
                    if (this.selectedCarrier === 'sea_freight') return 'Sea Freight';
                    return this.selectedCarrier;
                },

                recalcTotals() {
                    if (this.isQuantityOnly) {
                        let sum = 0;
                        this.items.forEach(it => {
                            sum += parseFloat(it.total_amount) || 0;
                        });
                        this.subtotal = Math.round(sum * 100) / 100;
                        this.finalTotal = this.subtotal;
                        return;
                    }
                    const base = this.itemsBaseTotal;

                    // Sync any percentage rows to the current itemsBaseTotal
                    this.items.forEach(it => {
                        if (it.calc_mode === 'percentage') {
                            const pct = parseFloat(it.percentage) || 0;
                            const val = Math.round((base * (pct / 100)) * 100) / 100;
                            const rawQty = (it.unit_amount !== '' && it.unit_amount !== null) ? parseFloat(it.unit_amount) : 1;
                            if (it.type === 'discount') {
                                it.unit_price = -val;
                                it.total_amount = -val * rawQty;
                            } else {
                                it.unit_price = val;
                                it.total_amount = val * rawQty;
                            }
                        }
                    });

                    let sum = 0;
                    this.items.forEach(it => {
                        sum += parseFloat(it.total_amount) || 0;
                    });
                    this.subtotal = Math.round(sum * 100) / 100;
                    const freight = this.appliedFreightAmount;
                    this.finalTotal = Math.round((this.subtotal + freight) * 100) / 100;
                    if (this.calculatedItemsNetWeight > 0 && !this.netWeight) {
                        this.netWeight = Math.round(this.calculatedItemsNetWeight * 1000) / 1000;
                    }
                },

                formatNumber(val) {
                    return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                },

                formatWeight(val) {
                    return Number(val || 0).toLocaleString(undefined, { minimumFractionDigits: 3, maximumFractionDigits: 3 });
                },

                get checklistHeading() {
                    if (!this.documentType) return 'Awaiting Document Type';
                    return this.documentType.replace('_', ' ').toUpperCase();
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
                    const docKey = this.documentNumber ? this.documentNumber.trim().toUpperCase() : 'NEW';
                    return `chk_${docKey}_${itemId}`;
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

                resetChecklistSession() {
                    this.checklists.forEach(item => {
                        const key = this.sessionKey(item.id);
                        sessionStorage.removeItem(key);
                        delete this.checkedItems[key];
                    });
                },

                async detectDocumentType() {
                    const num = this.documentNumber ? this.documentNumber.trim() : '';
                    if (!num) {
                        this.ruleMatched = '';
                        return;
                    }

                    this.isDetecting = true;
                    try {
                        const response = await fetch(`/api/documents/detect?number=${encodeURIComponent(num)}`);
                        const data = await response.json();

                        if (data.detected && data.type) {
                            this.documentType = data.type;
                            this.ruleMatched = data.rule_matched;
                            this.checklists = data.checklists || [];
                            this.loadChecklistSession();
                        } else {
                            this.ruleMatched = data.rule_matched || '';
                        }
                    } catch (e) {
                        console.error('Detection error', e);
                    } finally {
                        this.isDetecting = false;
                    }
                },

                async loadChecklistsForType(type) {
                    if (!type) {
                        this.checklists = [];
                        return;
                    }
                    try {
                        const response = await fetch(`/api/checklists/${type}`);
                        const data = await response.json();
                        this.checklists = data.items || [];
                        this.loadChecklistSession();
                    } catch (e) {
                        console.error('Checklist fetch error', e);
                    }
                },

                checkSavedDraft() {
                    try {
                        const raw = localStorage.getItem(this.draftKey);
                        if (!raw) return;
                        const draft = JSON.parse(raw);
                        // Only consider drafts saved within the last 7 days
                        if (!draft.timestamp || (Date.now() - draft.timestamp) > 7 * 86400000) {
                            localStorage.removeItem(this.draftKey);
                            return;
                        }
                        const hasContent = (draft.items && draft.items.some(i => i.item_code)) || draft.companyName || draft.documentNumber;
                        if (!hasContent) return;

                        this.hasDraft = true;
                        this.draftSavedAt = draft.savedAtFormatted || new Date(draft.timestamp).toLocaleTimeString();
                        this.savedDraft = draft;
                    } catch (e) {
                        console.warn('Failed to parse draft', e);
                    }
                },

                saveDraft() {
                    try {
                        const hasContent = (this.items && this.items.some(i => i.item_code)) || this.companyName || this.documentNumber;
                        if (!hasContent) return;

                        const timeStr = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit' });
                        const payload = {
                            timestamp: Date.now(),
                            savedAtFormatted: timeStr,
                            documentNumber: this.documentNumber,
                            documentType: this.documentType,
                            companyName: this.companyName,
                            country: this.country,
                            address: this.address,
                            contactDetails: this.contactDetails,
                            currency: this.currency,
                            selectedPriceList: this.selectedPriceList,
                            selectedPriceLabel: this.selectedPriceLabel,
                            netWeight: this.netWeight,
                            grossWeight: this.grossWeight,
                            selectedCarrier: this.selectedCarrier,
                            items: this.items,
                            packages: this.packages,
                            carriers: this.carriers,
                        };
                        localStorage.setItem(this.draftKey, JSON.stringify(payload));
                        this.lastAutoSavedAt = timeStr;
                    } catch (e) {
                        console.warn('Failed to auto-save draft', e);
                    }
                },

                restoreDraft() {
                    if (!this.savedDraft) return;
                    const d = this.savedDraft;
                    if (d.documentNumber) this.documentNumber = d.documentNumber;
                    if (d.documentType) this.documentType = d.documentType;
                    if (d.companyName) this.companyName = d.companyName;
                    if (d.country) this.country = d.country;
                    if (d.address) this.address = d.address;
                    if (d.contactDetails) this.contactDetails = d.contactDetails;
                    if (d.currency) this.currency = d.currency;
                    if (d.selectedPriceList !== undefined) this.selectedPriceList = d.selectedPriceList;
                    if (d.selectedPriceLabel !== undefined) this.selectedPriceLabel = d.selectedPriceLabel;
                    if (d.netWeight !== undefined) this.netWeight = d.netWeight;
                    if (d.grossWeight !== undefined) this.grossWeight = d.grossWeight;
                    if (d.selectedCarrier !== undefined) this.selectedCarrier = d.selectedCarrier;
                    if (d.carriers) this.carriers = d.carriers;
                    if (Array.isArray(d.items) && d.items.length > 0) this.items = d.items;
                    if (Array.isArray(d.packages) && d.packages.length > 0) this.packages = d.packages;

                    this.items.forEach(it => this.recalcItem(it));
                    this.packages.forEach(p => this.recalcPackage(p));
                    this.recalcTotals();
                    if (this.documentType) {
                        this.loadChecklistsForType(this.documentType);
                    }
                    this.hasDraft = false;
                    window.showToast?.('Local draft restored successfully!', 'success');
                },

                discardDraft() {
                    try {
                        localStorage.removeItem(this.draftKey);
                    } catch (e) {}
                    this.hasDraft = false;
                    this.savedDraft = null;
                    window.showToast?.('Saved draft discarded.', 'info');
                },

                clearDraft() {
                    try {
                        localStorage.removeItem(this.draftKey);
                    } catch (e) {}
                    if (this.autoSaveTimer) {
                        clearInterval(this.autoSaveTimer);
                    }
                },

                handleTableKeyNav(e, rowIdx, colIdx) {
                    if (e.key === 'ArrowUp') {
                        if (rowIdx > 0) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx - 1, colIdx, 'vertical');
                        } else if (e.target.type === 'number') {
                            e.preventDefault();
                        }
                    } else if (e.key === 'ArrowDown') {
                        if (rowIdx < this.items.length - 1) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx + 1, colIdx, 'vertical');
                        } else if (e.target.type === 'number') {
                            e.preventDefault();
                        }
                    } else if (e.altKey && e.key === 'ArrowLeft') {
                        if (colIdx > 0) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx, colIdx - 1, 'left');
                        }
                    } else if (e.altKey && e.key === 'ArrowRight') {
                        if (colIdx < 3) {
                            e.preventDefault();
                            this.focusGridCell(rowIdx, colIdx + 1, 'right');
                        }
                    }
                },

                focusGridCell(rowIdx, colIdx, direction = null) {
                    const table = this.$refs.itemsTable || document;
                    const findCell = (r, c) => {
                        const els = table.querySelectorAll(`[data-grid-item="true"][data-grid-row="${r}"][data-grid-col="${c}"]`);
                        for (let i = 0; i < els.length; i++) {
                            const el = els[i];
                            if (el && el.offsetParent !== null && el.type !== 'hidden' && !el.disabled) {
                                return el;
                            }
                        }
                        return null;
                    };

                    let target = findCell(rowIdx, colIdx);

                    if (!target) {
                        if (direction === 'right') {
                            for (let c = colIdx + 1; c <= 3; c++) {
                                target = findCell(rowIdx, c);
                                if (target) break;
                            }
                        } else if (direction === 'left') {
                            for (let c = colIdx - 1; c >= 0; c--) {
                                target = findCell(rowIdx, c);
                                if (target) break;
                            }
                        } else {
                            target = findCell(rowIdx, colIdx - 1) || findCell(rowIdx, colIdx + 1) || findCell(rowIdx, 0);
                        }
                    }

                    if (target) {
                        target.focus();
                        if (typeof target.select === 'function' && !target.readOnly) {
                            target.select();
                        }
                    }
                },

                openBulkPasteModal(tab = 'add_items') {
                    this.bulkPasteTab = tab;
                    this.bulkPasteModalOpen = true;
                },

                parseDelimitedList(text) {
                    if (!text || typeof text !== 'string') return [];
                    return text
                        .split(/[\r\n,;\t]+/)
                        .map(s => s.trim())
                        .filter(s => s.length > 0);
                },

                get bulkPastePreviewItems() {
                    const rawItems = (this.bulkPasteItemsText || '').split(/[\r\n]+/);
                    const separateQtys = this.parseDelimitedList(this.bulkPasteQuantitiesText);

                    const result = [];
                    rawItems.forEach(line => {
                        const trimmed = line.trim();
                        if (!trimmed) return;

                        // Check if line is tab-separated (e.g. Excel copied row: CODE \t QTY)
                        if (trimmed.includes('\t')) {
                            const parts = trimmed.split('\t').map(s => s.trim()).filter(s => s.length > 0);
                            if (parts.length >= 2) {
                                result.push({
                                    code: parts[0],
                                    qty: parts[1]
                                });
                                return;
                            }
                        }

                        // Check if comma-separated
                        const subItems = trimmed.split(',').map(s => s.trim()).filter(s => s.length > 0);
                        subItems.forEach(sub => {
                            result.push({
                                code: sub,
                                qty: ''
                            });
                        });
                    });

                    // If separate quantities were provided in the quantities box, map them 1-to-1
                    if (separateQtys.length > 0) {
                        result.forEach((item, idx) => {
                            if (separateQtys[idx] !== undefined) {
                                item.qty = separateQtys[idx];
                            }
                        });
                    }

                    return result;
                },

                bulkPasteQtyForIndex(idx) {
                    const qtys = this.parseDelimitedList(this.bulkPasteQuantitiesText);
                    return qtys[idx] !== undefined ? qtys[idx] : null;
                },

                async applyBulkAddItems() {
                    const preview = this.bulkPastePreviewItems;
                    if (preview.length === 0) return;

                    const isFirstEmpty = this.items.length === 1 && !this.items[0].item_code && !this.items[0].description && !this.items[0].unit_amount;
                    const itemsToReprice = [];

                    preview.forEach((pv, pIdx) => {
                        const rawQty = pv.qty !== '' ? parseFloat(pv.qty) : '';
                        const qtyVal = !isNaN(rawQty) && rawQty !== '' ? rawQty : (pv.qty !== '' ? pv.qty : '');

                        if (isFirstEmpty && pIdx === 0) {
                            this.items[0].item_code = pv.code;
                            if (qtyVal !== '') this.items[0].unit_amount = qtyVal;
                            this.recalcItem(this.items[0]);
                            itemsToReprice.push(this.items[0]);
                        } else {
                            const newItem = {
                                type: 'item',
                                item_code: pv.code,
                                description: '',
                                calc_mode: 'fixed',
                                percentage: null,
                                unit_amount: qtyVal,
                                unit_price: '',
                                total_amount: 0,
                                unit_weight: 0,
                                total_weight: 0,
                                price_from_tracker: false,
                                price_editable: false,
                                price_list: '',
                                is_fallback: false
                            };
                            this.items.push(newItem);
                            this.recalcItem(newItem);
                            itemsToReprice.push(newItem);
                        }
                    });

                    this.bulkPasteModalOpen = false;
                    this.bulkPasteItemsText = '';
                    this.bulkPasteQuantitiesText = '';

                    window.showToast?.(`Added ${preview.length} item(s). Fetching details...`, 'success');

                    await this.batchRepriceAllItems();
                },

                applyBulkUpdateQuantities() {
                    const qtys = this.parseDelimitedList(this.bulkPasteQuantitiesText);
                    if (qtys.length === 0) return;

                    let updatedCount = 0;
                    const regularItems = this.items.filter(it => !this.isAdjustment(it));

                    regularItems.forEach((it, idx) => {
                        if (qtys[idx] !== undefined) {
                            const val = parseFloat(qtys[idx]);
                            it.unit_amount = !isNaN(val) ? val : qtys[idx];
                            this.recalcItem(it);
                            updatedCount++;
                        }
                    });

                    this.bulkPasteModalOpen = false;
                    this.bulkPasteQuantitiesText = '';
                    this.recalcTotals();
                    window.showToast?.(`Updated quantities for ${updatedCount} item(s)!`, 'success');
                },

                async handleItemCodePaste(e, startRowIdx) {
                    const text = (e.clipboardData || window.clipboardData)?.getData('text') || '';
                    const isMulti = text.includes('\n') || text.includes(',') || text.includes('\t') || text.includes(';');
                    if (!isMulti) return;

                    e.preventDefault();
                    const codes = this.parseDelimitedList(text);
                    if (codes.length === 0) return;

                    codes.forEach((code, idx) => {
                        const rowIdx = startRowIdx + idx;
                        if (rowIdx < this.items.length) {
                            const existing = this.items[rowIdx];
                            if (!this.isAdjustment(existing)) {
                                existing.item_code = code;
                            }
                        } else {
                            const newItem = {
                                type: 'item',
                                item_code: code,
                                description: '',
                                calc_mode: 'fixed',
                                percentage: null,
                                unit_amount: '',
                                unit_price: '',
                                total_amount: 0,
                                unit_weight: 0,
                                total_weight: 0,
                                price_from_tracker: false,
                                price_editable: false
                            };
                            this.items.push(newItem);
                        }
                    });

                    await this.batchRepriceAllItems();
                    window.showToast?.(`Pasted ${codes.length} item codes across rows!`, 'success');
                },

                handleQuantityPaste(e, startRowIdx) {
                    const text = (e.clipboardData || window.clipboardData)?.getData('text') || '';
                    const isMulti = text.includes('\n') || text.includes(',') || text.includes('\t') || text.includes(';');
                    if (!isMulti) return;

                    e.preventDefault();
                    const qtys = this.parseDelimitedList(text);
                    if (qtys.length === 0) return;

                    let updated = 0;
                    qtys.forEach((qtyStr, idx) => {
                        const rowIdx = startRowIdx + idx;
                        if (rowIdx < this.items.length) {
                            const item = this.items[rowIdx];
                            if (!this.isAdjustment(item)) {
                                const num = parseFloat(qtyStr);
                                item.unit_amount = !isNaN(num) ? num : qtyStr;
                                this.recalcItem(item);
                                updated++;
                            }
                        }
                    });

                    this.recalcTotals();
                    window.showToast?.(`Pasted ${updated} quantities across rows!`, 'success');
                },

                prepareSubmit(e) {
                    this.clearDraft();
                    return true;
                }
            };
        }
    </script>
