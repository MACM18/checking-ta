@props([
    'position' => 'sidebar', // 'sidebar' | 'mobile' | 'topbar'
])

<div x-data="{ isHovered: false, isOpen: false }"
     @mouseenter="isHovered = true"
     @mouseleave="isHovered = false"
     @click.outside="isOpen = false"
     @keydown.escape.window="isOpen = false; isHovered = false"
     class="relative inline-block text-left z-30">

    <!-- Trigger Button -->
    <button type="button"
            @click="isOpen = !isOpen"
            class="p-1.5 rounded-lg text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 border border-transparent hover:border-indigo-100 focus:outline-none transition shadow-2xs group flex items-center justify-center"
            title="Keyboard Shortcuts Guide (Hover or click to view)">
        <svg class="w-4 h-4 text-gray-500 group-hover:text-indigo-600 transition" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
            <rect x="2" y="5" width="20" height="14" rx="2.5" stroke="currentColor" stroke-width="1.8" fill="none"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 9h.01M10 9h.01M14 9h.01M18 9h.01M6 12h.01M18 12h.01M9 15h6"/>
        </svg>
    </button>

    <!-- Hover / Click Expansion Popover -->
    <div x-show="isHovered || isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute {{ $position === 'sidebar' ? 'left-full top-0 ml-2' : ($position === 'mobile' ? 'right-0 top-full mt-2' : 'right-0 top-full mt-2') }} w-72 bg-white rounded-2xl shadow-2xl border border-gray-200/90 p-3.5 space-y-2.5 z-50 text-xs"
         style="display: none;">

        <!-- Popover Header -->
        <div class="flex items-center justify-between border-b border-gray-100 pb-2">
            <div class="flex items-center space-x-2">
                <div class="w-6 h-6 rounded-md bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-xs">
                    ⌨️
                </div>
                <div>
                    <h4 class="font-bold text-gray-900 text-xs leading-tight">Shortcuts Guide</h4>
                    <p class="text-[10px] text-gray-400">System hotkeys</p>
                </div>
            </div>
            <span class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded border border-indigo-100">
                Active
            </span>
        </div>

        <!-- Shortcuts List -->
        <div class="space-y-1.5 text-[11px]">
            <!-- Quick Save -->
            <div class="p-1.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition">
                <span class="text-gray-700 font-medium">Quick Save Form</span>
                <div class="flex items-center space-x-1">
                    <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-[10px] text-slate-700 shadow-2xs">Ctrl</kbd>
                    <span class="text-gray-400 text-[10px]">+</span>
                    <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-[10px] text-slate-700 shadow-2xs">S</kbd>
                </div>
            </div>

            <!-- Focus Search -->
            <div class="p-1.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition">
                <span class="text-gray-700 font-medium">Focus Search Bar</span>
                <kbd class="px-2 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-[10px] text-slate-700 shadow-2xs">/</kbd>
            </div>

            <!-- Transfer Mode -->
            <div class="p-1.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition">
                <span class="text-gray-700 font-medium">Split-Screen Transfer</span>
                <kbd class="px-2 py-0.5 bg-indigo-50 border border-indigo-200 text-indigo-700 rounded font-mono font-bold text-[10px] shadow-2xs">T</kbd>
            </div>

            <!-- Close / Unfocus -->
            <div class="p-1.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition">
                <span class="text-gray-700 font-medium">Close / Cancel</span>
                <kbd class="px-1.5 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-[10px] text-slate-700 shadow-2xs">Esc</kbd>
            </div>

            <!-- Full Help Modal -->
            <div class="p-1.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition">
                <span class="text-gray-700 font-medium">Toggle Shortcuts Help</span>
                <kbd class="px-2 py-0.5 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-[10px] text-slate-700 shadow-2xs">?</kbd>
            </div>
        </div>

        <!-- Footer Action -->
        <div class="pt-2 border-t border-gray-100 flex items-center justify-between text-[11px]">
            <span class="text-gray-400">Press <kbd class="font-mono font-bold text-gray-600">?</kbd> anytime</span>
            <button type="button"
                    @click="$dispatch('open-shortcuts-modal'); isOpen = false; isHovered = false"
                    class="text-indigo-600 hover:text-indigo-800 font-bold hover:underline">
                Full Dialog &rarr;
            </button>
        </div>
    </div>
</div>
