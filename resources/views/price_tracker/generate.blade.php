<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <a href="{{ route('price-tracker.index') }}" class="text-xs font-semibold text-indigo-600 hover:underline">← Price tracker</a>
                <h2 class="mt-1 text-2xl font-bold text-gray-900">Generate currency price lists</h2>
                <p class="mt-1 text-sm text-gray-500">Use saved USD bases or an existing USD tier to create margin prices in a destination list.</p>
            </div>
        </div>
    </x-slot>

    <div class="py-8" x-data="{ rateMode: @js(old('rate_mode', 'automatic')) }">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if(session('error'))<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{ session('error') }}</div>@endif
            @if($errors->any())<div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

            <div class="grid gap-6 lg:grid-cols-3">
                <form method="POST" action="{{ route('price-tracker.generate.preview') }}" class="space-y-6 lg:col-span-2">
                    @csrf
                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                        <div class="mb-5"><h3 class="text-base font-bold text-gray-900">1. Source and destination</h3><p class="mt-1 text-xs text-gray-500">The source price is a USD cost. A destination can be a new or existing price list; the source list is never changed.</p></div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div><label for="source_key" class="mb-1 block text-xs font-bold text-gray-700">USD base source</label>
                                <select id="source_key" name="source_key" required class="w-full rounded-xl border-gray-300 text-sm">
                                    <option value="">Choose a source...</option>
                                    @foreach($sources as $source)
                                        @php $key = base64_encode(json_encode(['type' => $source['type'], 'list' => $source['list'], 'label' => $source['label']])); @endphp
                                        <option value="{{ $key }}" @selected(old('source_key') === $key)>{{ $source['type'] === 'saved_base' ? 'Saved USD bases' : 'USD tier '.$source['label'] }} · {{ $source['list'] }} ({{ $source['count'] }} items)</option>
                                    @endforeach
                                </select>
                                @if(empty($sources))<p class="mt-2 text-xs text-amber-700">No USD sources yet. Save base prices on item edit pages or import a USD tier first.</p>@endif
                            </div>
                            <div><label for="target_list" class="mb-1 block text-xs font-bold text-gray-700">Destination price list name</label>
                                <input id="target_list" name="target_list" list="existing-lists" maxlength="50" required value="{{ old('target_list') }}" placeholder="e.g. Export Margin 2026" class="w-full rounded-xl border-gray-300 text-sm">
                                <datalist id="existing-lists">@foreach($targetLists as $list)<option value="{{ $list }}"></option>@endforeach</datalist>
                            </div>
                        </div>
                        <div class="mt-4"><label for="list_layout" class="mb-1 block text-xs font-bold text-gray-700">List arrangement</label>
                            <select id="list_layout" name="list_layout" class="w-full rounded-xl border-gray-300 text-sm">
                                <option value="combined" @selected(old("list_layout", "combined") === "combined")>One list with tiers for every selected currency</option>
                                <option value="per_currency" @selected(old("list_layout") === "per_currency")>Separate list per currency (adds USD, AED, EUR… to the name)</option>
                            </select></div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                        <h3 class="text-base font-bold text-gray-900">2. Profit margins and currencies</h3>
                        <p class="mt-1 text-xs text-gray-500">A 30% profit margin means USD selling price = USD base ÷ (1 − 0.30). Currency price = USD selling price × USD→currency rate.</p>
                        <div class="mt-5"><label for="margins" class="mb-1 block text-xs font-bold text-gray-700">Margins (%)</label>
                            <input id="margins" name="margins" required value="{{ old('margins', '30, 40, 50') }}" placeholder="30, 40, 50" class="w-full rounded-xl border-gray-300 text-sm"><p class="mt-1 text-xs text-gray-500">Up to 12 margins, each below 100%.</p></div>
                        <fieldset class="mt-5"><legend class="mb-2 text-xs font-bold text-gray-700">Currencies to generate</legend>
                            <div class="grid gap-2 sm:grid-cols-3">
                                @foreach($currencies as $currency)
                                    <label class="flex items-center gap-2 rounded-xl border border-gray-200 p-3 text-sm font-semibold text-gray-800 hover:bg-gray-50">
                                        <input type="checkbox" name="currencies[]" value="{{ $currency->code }}" @checked(in_array($currency->code, old('currencies', ['USD', 'AED']))) class="rounded border-gray-300 text-indigo-600">
                                        {{ $currency->code }} <span class="text-xs font-normal text-gray-500">{{ $currency->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                        <div class="mt-5"><label for="rate_mode" class="mb-1 block text-xs font-bold text-gray-700">Conversion rates</label>
                            <select id="rate_mode" name="rate_mode" x-model="rateMode" class="w-full rounded-xl border-gray-300 text-sm">
                                <option value="automatic">Automatic: live rates when available, saved rates as fallback</option>
                                <option value="manual">Enter my own USD conversion rates</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">The exact rates used are frozen in the preview. USD always uses 1.</p>
                        </div>
                        <div x-show="rateMode === 'manual'" x-cloak class="mt-4 grid gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-4 sm:grid-cols-3">
                            @foreach($currencies as $currency)
                                @if($currency->code !== 'USD')
                                    <div><label for="rate_{{ $currency->code }}" class="mb-1 block text-xs font-bold text-gray-700">1 USD → {{ $currency->code }}</label>
                                        <input id="rate_{{ $currency->code }}" name="manual_rates[{{ $currency->code }}]" type="number" min="0.000001" max="10000" step="0.000001" value="{{ old('manual_rates.'.$currency->code, $currency->exchange_rate) }}" class="w-full rounded-lg border-gray-300 bg-white text-sm"></div>
                                @endif
                            @endforeach
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-xs">
                        <h3 class="text-base font-bold text-gray-900">3. Existing prices</h3>
                        <div class="mt-3 space-y-2 text-sm">
                            <label class="flex items-start gap-2"><input type="radio" name="mode" value="missing" @checked(old('mode', 'missing') === 'missing') class="mt-1 text-indigo-600"><span><strong>Keep existing prices</strong> and add only missing tiers.</span></label>
                            <label class="flex items-start gap-2"><input type="radio" name="mode" value="replace" @checked(old('mode') === 'replace') class="mt-1 text-rose-600"><span><strong>Replace matching tiers</strong> after reviewing the preview and typing REPLACE.</span></label>
                        </div>
                        <button type="submit" @disabled(empty($sources)) class="mt-5 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300">Preview price list</button>
                    </section>
                </form>

                <aside class="space-y-4 lg:col-span-1">
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50 p-5">
                        <h3 class="text-sm font-bold text-indigo-950">How the list is saved</h3>
                        <p class="mt-2 text-sm text-indigo-900">Each generated tier becomes a recorded item price in the destination list. That list then appears in the tracker and price-list selectors.</p>
                        <p class="mt-2 text-xs text-indigo-800">Previewing never saves prices. Rates and existing tier values are checked again before applying.</p>
                    </div>
                    @if($preview)
                        <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-xs">
                            <h3 class="text-sm font-bold text-gray-900">Preview summary</h3>
                            <p class="mt-2 text-xs text-gray-700">Destination lists: {{ implode(", ", array_unique($preview["config"]["target_lists"])) }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ $preview['config']['source_list'] }} → {{ $preview['config']['target_list'] }}</p>
                            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm"><div><dt class="text-gray-500">Items</dt><dd class="font-bold">{{ number_format($preview['summary']['items']) }}</dd></div><div><dt class="text-gray-500">Prices</dt><dd class="font-bold">{{ number_format($preview['summary']['prices']) }}</dd></div><div><dt class="text-gray-500">Add</dt><dd class="font-bold text-indigo-700">{{ number_format($preview['summary']['add']) }}</dd></div><div><dt class="text-gray-500">Replace</dt><dd class="font-bold text-rose-700">{{ number_format($preview['summary']['replace']) }}</dd></div><div><dt class="text-gray-500">Keep</dt><dd class="font-bold">{{ number_format($preview['summary']['keep']) }}</dd></div></dl>
                            <h4 class="mt-5 text-xs font-bold uppercase tracking-wide text-gray-600">Rates used</h4>
                            <ul class="mt-2 space-y-1 text-xs font-mono text-gray-700">@foreach($preview['config']['rates'] as $code => $rate)<li>1 USD = {{ number_format($rate, 6) }} {{ $code }}</li>@endforeach</ul>
                        </div>
                    @endif
                </aside>
            </div>

            @if($preview)
                <section class="rounded-2xl border border-indigo-200 bg-white p-6 shadow-xs">
                    <h3 class="text-base font-bold text-gray-900">Review before saving</h3>
                    <p class="mt-1 text-sm text-gray-500">Showing the first {{ count($preview['summary']['sample']) }} of {{ number_format($preview['summary']['prices']) }} generated tiers. The summary above includes every item.</p>
                    <div class="mt-4 overflow-x-auto"><table class="min-w-full divide-y divide-gray-100 text-sm"><thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="p-3 text-left">Item</th><th class="p-3 text-left">Price list</th><th class="p-3 text-left">Tier</th><th class="p-3 text-right">Current</th><th class="p-3 text-right">New</th><th class="p-3 text-left">Action</th></tr></thead><tbody class="divide-y divide-gray-100">
                        @foreach($preview['summary']['sample'] as $row)<tr><td class="p-3 font-mono font-bold">{{ $row['item_code'] }}</td><td class="p-3 text-xs">{{ $row["price_list"] }}</td><td class="p-3">{{ $row['label'] }}</td><td class="p-3 text-right font-mono">{{ $row['current'] === null ? '—' : number_format($row['current'], 4) }}</td><td class="p-3 text-right font-mono">{{ number_format($row['generated'], 4) }}</td><td class="p-3 font-semibold {{ $row['action'] === 'Replace' ? 'text-rose-700' : 'text-indigo-700' }}">{{ $row['action'] }}</td></tr>@endforeach
                    </tbody></table></div>
                    <form method="POST" action="{{ route('price-tracker.generate.apply') }}" class="mt-5 space-y-3">
                        @csrf
                        @if($preview['config']['mode'] === 'replace')
                            <label for="replace_confirmation" class="block text-sm font-bold text-rose-800">Type REPLACE to confirm changing existing prices</label>
                            <input id="replace_confirmation" name="replace_confirmation" placeholder="REPLACE" autocomplete="off" required class="rounded-lg border-rose-300 text-sm">
                        @endif
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-800"><input type="checkbox" name="confirm_generation" value="1" required class="rounded border-gray-300">I reviewed the destination, rates, counts, and sample prices.</label>
                        <button type="submit" class="rounded-xl px-5 py-2.5 text-sm font-bold text-white {{ $preview['config']['mode'] === 'replace' ? 'bg-rose-700 hover:bg-rose-800' : 'bg-indigo-600 hover:bg-indigo-700' }}">Save destination price list</button>
                    </form>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
