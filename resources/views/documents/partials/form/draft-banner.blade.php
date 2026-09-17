<!-- Unsaved Draft Recovery Banner -->
<div x-show="hasDraft" x-cloak x-transition class="mb-6 bg-gradient-to-r from-amber-50 via-indigo-50/40 to-amber-50 border border-amber-300/80 rounded-2xl p-4 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 shadow-sm">
    <div class="flex items-center space-x-3">
        <div class="w-10 h-10 rounded-xl bg-amber-500 text-white flex items-center justify-center flex-shrink-0 shadow-xs">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        </div>
        <div>
            <h4 class="font-bold text-sm text-gray-900 leading-tight">Unsaved Local Draft Available</h4>
            <p class="text-xs text-gray-600 mt-0.5">
                We found an auto-saved draft from <strong class="text-amber-800" x-text="draftSavedAt"></strong>. Would you like to restore your work?
            </p>
        </div>
    </div>
    <div class="flex items-center space-x-2">
        <button type="button" @click="restoreDraft()" class="inline-flex items-center px-3.5 py-1.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-xl text-xs font-bold shadow-xs transition">
            <svg class="w-4 h-4 me-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Restore Draft
        </button>
        <button type="button" @click="discardDraft()" class="inline-flex items-center px-3 py-1.5 bg-white hover:bg-gray-100 text-gray-700 border border-gray-200 rounded-xl text-xs font-semibold shadow-2xs transition">
            Discard
        </button>
    </div>
</div>

<!-- Auto-save Status Badge (when auto-saved) -->
<div x-show="lastAutoSavedAt" x-cloak class="flex items-center justify-end mb-3">
    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-white text-gray-500 border border-gray-200 shadow-2xs">
        <span class="w-2 h-2 rounded-full bg-emerald-500 me-1.5 animate-pulse"></span>
        Draft auto-saved locally at <strong class="ml-1 font-mono text-gray-700" x-text="lastAutoSavedAt"></strong>
    </span>
</div>
