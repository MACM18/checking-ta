<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('price-tracker.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">← Price tracker</a>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">Edit {{ $item->item_code }}</h2>
                <p class="mt-1 text-sm text-gray-500">Edit item details and individual tiers, or preview prices generated from a saved USD base.</p>
            </div>
            <a href="{{ route('price-tracker.items.show', $item) }}" class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">View item history</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ session('error') }}</div>
            @endif
            @if($errors->any())
                <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">
                    <ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-2">
                <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-gray-900">Item details</h3>
                    <p class="mt-1 text-xs text-gray-500">The item code stays fixed so existing document references remain linked.</p>
                    <form method="POST" action="{{ route('price-tracker.items.update', $item) }}" class="mt-5 space-y-4">
                        @csrf @method('PATCH')
                        <div><label for="description" class="mb-1 block text-xs font-bold text-gray-700">Description</label>
                            <textarea id="description" name="description" rows="3" class="w-full rounded-xl border-gray-300 text-sm">{{ old('description', $item->description) }}</textarea></div>
                        <div><label for="net_weight" class="mb-1 block text-xs font-bold text-gray-700">Net weight (kg)</label>
                            <input id="net_weight" name="net_weight" type="number" min="0" step="0.001" value="{{ old('net_weight', $item->net_weight) }}" class="w-full rounded-xl border-gray-300 text-sm"></div>
                        <button class="rounded-xl bg-slate-800 px-4 py-2 text-sm font-bold text-white hover:bg-slate-900">Save item details</button>
                    </form>
                </section>

                <section class="rounded-2xl border border-indigo-200 bg-indigo-50/30 p-6 shadow-xs">
                    <div class="flex items-start justify-between gap-3">
                        <div><h3 class="text-base font-bold text-gray-900">Base price</h3>
                            <p class="mt-1 text-xs text-gray-600">Stored per item and price list. Saving a base does not change any recorded tier.</p></div>
                        <span class="rounded-full bg-white px-2 py-1 text-[11px] font-bold text-indigo-700">USD base</span>
                    </div>
                    <div class="mt-5">
                        <label for="selected_list" class="mb-1 block text-xs font-bold text-gray-700">Price list</label>
                        <select id="selected_list" onchange="window.location.search = '?price_list=' + encodeURIComponent(this.value)" class="w-full rounded-xl border-gray-300 bg-white text-sm">
                            @foreach($lists as $list)<option value="{{ $list }}" @selected($selectedList === $list)>{{ $list }}</option>@endforeach
                        </select>
                    </div>
                    <form method="POST" action="{{ route('price-tracker.items.base.save', $item) }}" class="mt-4 space-y-4">
                        @csrf
                        <input type="hidden" name="price_list" value="{{ $selectedList }}">
                        <div><label for="base_price_usd" class="mb-1 block text-xs font-bold text-gray-700">Base unit price (USD)</label>
                            <input id="base_price_usd" name="base_price_usd" type="number" min="0" max="10000000" step="0.0001" required value="{{ old('base_price_usd', $base?->base_price_usd) }}" placeholder="0.0000" class="w-full rounded-xl border-gray-300 bg-white text-sm"></div>
                        <div><label for="usd_to_aed_multiplier" class="mb-1 block text-xs font-bold text-gray-700">USD → AED multiplier</label>
                            <input id="usd_to_aed_multiplier" name="usd_to_aed_multiplier" type="number" min="0.000001" max="100" step="0.000001" required value="{{ old('usd_to_aed_multiplier', $base?->usd_to_aed_multiplier ?? '3.672500') }}" class="w-full rounded-xl border-gray-300 bg-white text-sm">
                            <p class="mt-1 text-xs text-gray-500">Example: 3.6725 means 1 USD becomes 3.6725 AED for generated tiers.</p></div>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-700">Save base and multiplier</button>
                    </form>
                </section>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                <h3 class="text-base font-bold text-gray-900">Generate percentage tiers</h3>
                <p class="mt-1 text-sm text-gray-600">Choose a markup or discount from the saved USD base. The AED price uses the USD result × the saved multiplier. Prices are rounded to four decimal places.</p>
                @if($base)
                    <form method="POST" action="{{ route('price-tracker.items.generate.preview', $item) }}" class="mt-5 flex flex-wrap items-end gap-4">
                        @csrf
                        <input type="hidden" name="price_list" value="{{ $selectedList }}">
                        <div class="min-w-48 flex-1"><label for="percentages" class="mb-1 block text-xs font-bold text-gray-700">Percentages</label>
                            <input id="percentages" name="percentages" value="{{ old('percentages', '30, 40, 50') }}" required placeholder="30, 40, 50" class="w-full rounded-xl border-gray-300 text-sm"></div>
                        <div class="min-w-44"><label for="calculation" class="mb-1 block text-xs font-bold text-gray-700">Calculation</label>
                            <select id="calculation" name="calculation" class="w-full rounded-xl border-gray-300 text-sm">
                                <option value="markup">Add percentage (markup)</option>
                                <option value="discount">Subtract percentage (discount)</option>
                            </select></div>
                        <div class="min-w-56"><label for="generation_mode" class="mb-1 block text-xs font-bold text-gray-700">Existing tiers</label>
                            <select id="generation_mode" name="mode" class="w-full rounded-xl border-gray-300 text-sm">
                                <option value="missing">Keep existing; add missing only</option>
                                <option value="replace">Replace existing values</option>
                            </select></div>
                        <button class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">Preview prices</button>
                    </form>
                @else
                    <p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Save a USD base for {{ $selectedList }} to preview generated tiers.</p>
                @endif

                @if($preview)
                    <div class="mt-6 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                        <h4 class="font-bold text-indigo-950">Preview for {{ $preview['price_list'] }}</h4>
                        <p class="mt-1 text-xs text-indigo-800">{{ ucfirst($preview['calculation']) }}: USD = base × (1 {{ $preview['calculation'] === 'markup' ? '+' : '−' }} percentage / 100); AED = USD × multiplier. Prices are saved only after you confirm.</p>
                        <div class="mt-4 overflow-x-auto"><table class="min-w-full text-left text-sm">
                            <thead><tr class="border-b border-indigo-200 text-xs uppercase text-indigo-900"><th class="p-2">Tier</th><th class="p-2 text-right">Current</th><th class="p-2 text-right">Generated</th><th class="p-2">Action</th></tr></thead>
                            <tbody>@foreach($preview['rows'] as $row)<tr class="border-b border-indigo-100"><td class="p-2 font-semibold">{{ $row['label'] }}</td><td class="p-2 text-right font-mono">{{ $row['existing'] === null ? '—' : number_format($row['existing'], 4) }}</td><td class="p-2 text-right font-mono">{{ number_format($row['generated'], 4) }}</td><td class="p-2 font-semibold {{ $row['action'] === 'Replace' ? 'text-rose-700' : 'text-indigo-800' }}">{{ $row['action'] }}</td></tr>@endforeach</tbody>
                        </table></div>
                        <form method="POST" action="{{ route('price-tracker.items.generate.apply', $item) }}" class="mt-5 space-y-3">
                            @csrf
                            @if($preview['mode'] === 'replace')
                                <label for="replace_confirmation" class="block text-sm font-semibold text-rose-800">Type REPLACE to confirm changing existing tier prices</label>
                                <input id="replace_confirmation" name="replace_confirmation" autocomplete="off" class="rounded-lg border-rose-300 text-sm" placeholder="REPLACE" required>
                            @endif
                            <label class="flex items-center gap-2 text-sm font-medium text-gray-800"><input type="checkbox" name="confirm_generation" value="1" required class="rounded border-gray-300">I reviewed these prices and want to apply the listed actions.</label>
                            <button class="rounded-xl px-4 py-2 text-sm font-bold text-white {{ $preview['mode'] === 'replace' ? 'bg-rose-700 hover:bg-rose-800' : 'bg-indigo-600 hover:bg-indigo-700' }}">Apply previewed prices</button>
                        </form>
                    </div>
                @endif
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white shadow-xs">
                <div class="border-b border-gray-100 p-6"><h3 class="text-base font-bold text-gray-900">Recorded tier prices</h3><p class="mt-1 text-xs text-gray-500">Save each price individually. Generated tiers never delete existing records.</p></div>
                @if($item->prices->isNotEmpty())
                    <div class="overflow-x-auto"><table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-5 py-3">Price list</th><th class="px-5 py-3">Tier</th><th class="px-5 py-3">Currency</th><th class="px-5 py-3 text-right">Current price</th><th class="px-5 py-3 text-right">Edit price</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">@foreach($item->prices as $price)
                            <tr><td class="px-5 py-3 font-semibold">{{ $price->price_list }}</td><td class="px-5 py-3">{{ $price->price_label }}</td><td class="px-5 py-3">{{ $price->currency }}</td><td class="px-5 py-3 text-right font-mono">{{ number_format((float) $price->price, 4) }}</td>
                                <td class="px-5 py-3"><form method="POST" action="{{ route('price-tracker.items.prices.update', [$item, $price]) }}" class="flex justify-end gap-2">@csrf @method('PATCH')<input type="number" name="price" min="0" max="9999999999" step="0.0001" required value="{{ $price->price }}" aria-label="{{ $price->price_label }} price in {{ $price->price_list }}" class="w-32 rounded-lg border-gray-300 text-right font-mono text-sm"><button class="rounded-lg border border-indigo-200 px-3 py-1 text-xs font-bold text-indigo-700 hover:bg-indigo-50">Save</button></form></td></tr>
                        @endforeach</tbody>
                    </table></div>
                @else
                    <p class="p-6 text-sm text-gray-500">No prices recorded yet. Add a tier below or generate tiers from a saved base.</p>
                @endif
                <form method="POST" action="{{ route('price-tracker.items.prices.store', $item) }}" class="grid gap-3 border-t border-gray-100 bg-gray-50 p-6 sm:grid-cols-5 sm:items-end">
                    @csrf
                    <div><label class="mb-1 block text-xs font-bold">Price list</label><select name="price_list" class="w-full rounded-lg border-gray-300 text-sm">@foreach($lists as $list)<option value="{{ $list }}" @selected($selectedList === $list)>{{ $list }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-bold">Tier label</label><input name="price_label" placeholder="USD 25%" required maxlength="50" class="w-full rounded-lg border-gray-300 text-sm"></div>
                    <div><label class="mb-1 block text-xs font-bold">Currency</label><select name="currency" class="w-full rounded-lg border-gray-300 text-sm">@foreach($currencies as $currency)<option value="{{ $currency->code }}">{{ $currency->code }}</option>@endforeach</select></div>
                    <div><label class="mb-1 block text-xs font-bold">Price</label><input name="price" type="number" min="0" max="9999999999" step="0.0001" required class="w-full rounded-lg border-gray-300 text-sm"></div>
                    <button class="rounded-lg bg-slate-800 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-900">Add tier</button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
