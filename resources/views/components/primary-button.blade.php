<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-gradient-to-r from-amber-600 via-amber-700 to-yellow-700 hover:from-amber-700 hover:to-yellow-800 active:from-amber-800 active:to-yellow-900 border border-amber-600/30 rounded-md font-bold text-xs text-white uppercase tracking-widest shadow-xs hover:shadow focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
