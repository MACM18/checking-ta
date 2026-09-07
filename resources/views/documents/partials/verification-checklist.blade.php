<div class="bg-white rounded-2xl shadow-sm border-2 {{ ($isTransferMode ?? false) ? 'border-indigo-200' : 'border-gray-200/90' }} p-5 space-y-4">
    <div class="flex items-center justify-between border-b border-gray-100 pb-3">
        <div class="flex items-center space-x-2.5">
            <div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
            </div>
            <div>
                <h4 class="font-bold text-sm text-gray-900">Verification Checklist</h4>
                <p class="text-[11px] text-gray-500">{{ $document->formatted_type }}</p>
            </div>
        </div>
        <span class="px-2.5 py-0.5 rounded-full text-xs font-bold font-mono"
              :class="getCheckedCount(checklists) === checklists.length && checklists.length > 0 ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-200'"
              x-text="`${getCheckedCount(checklists)} / ${checklists.length}`">
        </span>
    </div>

    <!-- Live Lock Independence Notice for Operator -->
    <div class="text-[11px] bg-slate-50 text-slate-600 p-2.5 rounded-xl border border-slate-200/80 flex items-start space-x-2">
        <svg class="w-4 h-4 text-emerald-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <span>
            <strong>Operator Verification:</strong> Mark these checklist items while transferring data to the other system. Checklist marks are independent of document edit locks.
        </span>
    </div>

    <!-- Progress Bar -->
    <div class="space-y-1">
        <div class="flex justify-between text-[11px] font-bold text-gray-500">
            <span>Progress</span>
            <span x-text="`${checklists.length > 0 ? Math.round((getCheckedCount(checklists) / checklists.length) * 100) : 0}% Verified`"></span>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2.5 overflow-hidden">
            <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-300"
                 :style="`width: ${checklists.length > 0 ? (getCheckedCount(checklists) / checklists.length) * 100 : 0}%`">
            </div>
        </div>
    </div>

    <!-- All Checked Celebration Banner -->
    <div x-show="checklists.length > 0 && getCheckedCount(checklists) === checklists.length" x-transition class="p-3 bg-emerald-50 text-emerald-800 rounded-xl text-xs font-bold flex items-center justify-center space-x-2 border border-emerald-300 shadow-2xs">
        <svg class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <span>All Checklist Tasks Verified!</span>
    </div>

    <!-- Checklist Tasks List -->
    <div class="space-y-2.5 max-h-[480px] overflow-y-auto pr-1">
        <template x-if="checklists.length === 0">
            <div class="text-center py-6 text-gray-400 text-xs bg-gray-50 rounded-xl border border-dashed border-gray-200">
                No checklist items configured for this document type.
            </div>
        </template>

        <template x-for="(item, idx) in checklists" :key="item.id || idx">
            <label class="flex items-start space-x-3 p-3 rounded-xl border cursor-pointer transition select-none"
                   :class="isItemChecked(item.id) ? 'bg-emerald-50/70 border-emerald-300 shadow-2xs' : 'bg-white border-gray-200 hover:bg-slate-50/80 hover:border-slate-300'">
                <input type="checkbox"
                       class="mt-0.5 w-4 h-4 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500"
                       :checked="isItemChecked(item.id)"
                       @change="toggleCheck(item.id)">
                <div class="flex-1 text-xs">
                    <div class="font-bold text-gray-900"
                         :class="{'line-through text-gray-400': isItemChecked(item.id)}"
                         x-text="item.item_text"></div>
                    <div class="text-[11px] text-gray-500 mt-0.5" x-text="item.hint" x-show="item.hint"></div>
                    <span x-show="item.is_required" class="inline-block mt-1 text-[9px] uppercase font-bold text-rose-600 bg-rose-50 px-1.5 py-0.2 rounded border border-rose-200">Required</span>
                </div>
            </label>
        </template>
    </div>

    <div class="pt-2 flex items-center justify-between text-xs border-t border-gray-100">
        <button type="button" @click="resetChecklist(checklists.length)" class="text-gray-400 hover:text-gray-600 font-semibold underline">
            Reset Checklist
        </button>
        <span class="text-[11px] text-gray-400">Stored in browser</span>
    </div>
</div>
