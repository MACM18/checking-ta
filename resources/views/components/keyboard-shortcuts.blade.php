<!-- Global Keyboard Shortcuts Manager (Alpine.js) -->
<div x-data="globalKeyboardShortcuts()" x-cloak>
    <!-- Keyboard Shortcuts Help Modal -->
    <div x-show="showHelpModal"
         x-transition.opacity
         class="fixed inset-0 z-50 overflow-y-auto bg-gray-900/60 backdrop-blur-xs flex items-center justify-center p-4"
         style="display: none;"
         @click.self="showHelpModal = false">
        
        <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 max-w-md w-full p-6 space-y-5" @click.outside="showHelpModal = false">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <div class="flex items-center space-x-2.5">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold text-sm">
                        ⌨️
                    </div>
                    <h3 class="font-bold text-base text-gray-900">
                        Keyboard Shortcuts
                    </h3>
                </div>
                <button type="button" @click="showHelpModal = false" class="text-gray-400 hover:text-gray-600 text-lg font-bold">
                    &times;
                </button>
            </div>

            <div class="divide-y divide-gray-100 text-xs">
                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Quick Save Form / Document</span>
                    <div class="flex items-center space-x-1">
                        <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-slate-700 text-[11px] shadow-2xs">Ctrl</kbd>
                        <span class="text-gray-400">+</span>
                        <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-slate-700 text-[11px] shadow-2xs">S</kbd>
                    </div>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Focus Search Bar</span>
                    <kbd class="px-2.5 py-1 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-slate-700 text-[11px] shadow-2xs">/</kbd>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Close Modal / Unfocus</span>
                    <kbd class="px-2 py-1 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-slate-700 text-[11px] shadow-2xs">Esc</kbd>
                </div>

                <div class="py-2.5 flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Toggle Shortcuts Help</span>
                    <kbd class="px-2.5 py-1 bg-slate-100 border border-slate-300 rounded font-mono font-bold text-slate-700 text-[11px] shadow-2xs">?</kbd>
                </div>
            </div>

            <div class="pt-2 flex justify-end">
                <button type="button" @click="showHelpModal = false" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    Got it
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function globalKeyboardShortcuts() {
        return {
            showHelpModal: false,

            init() {
                window.addEventListener('keydown', (e) => {
                    const isEditingText = ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName) ||
                                          document.activeElement?.isContentEditable;

                    // 1. Ctrl/Cmd + S : Quick Save Form
                    if ((e.ctrlKey || e.metaKey) && (e.key === 's' || e.key === 'S')) {
                        e.preventDefault();
                        this.handleQuickSave();
                        return;
                    }

                    // 2. / (Slash) : Focus Global Search Input
                    if (e.key === '/' && !isEditingText && !e.ctrlKey && !e.metaKey && !e.altKey) {
                        const searchInput = document.querySelector('input[type="search"], input[name="search"], input[placeholder*="Search" i], input[x-model*="search" i]');
                        if (searchInput) {
                            e.preventDefault();
                            searchInput.focus();
                            if (searchInput.select) {
                                searchInput.select();
                            }
                            window.showToast?.('Search focused', 'info', 1000);
                        }
                        return;
                    }

                    // 3. Escape : Close open modals or blur focused input
                    if (e.key === 'Escape') {
                        if (this.showHelpModal) {
                            this.showHelpModal = false;
                            e.preventDefault();
                            return;
                        }
                        if (isEditingText && document.activeElement) {
                            document.activeElement.blur();
                        }
                        return;
                    }

                    // 4. ? (Shift + /) : Toggle Keyboard Shortcuts Guide
                    if (e.key === '?' && !isEditingText && !e.ctrlKey && !e.metaKey && !e.altKey) {
                        e.preventDefault();
                        this.showHelpModal = !this.showHelpModal;
                        return;
                    }
                });
            },

            handleQuickSave() {
                const activeEl = document.activeElement;
                let form = activeEl ? activeEl.closest('form') : null;

                if (!form) {
                    const forms = Array.from(document.querySelectorAll('form[method="POST"], form[method="post"]'))
                        .filter(f => !f.action.includes('logout') && !f.getAttribute('data-confirm')?.includes('delete') && !f.action.includes('delete'));

                    if (forms.length > 0) {
                        form = forms[0];
                    }
                }

                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        window.showToast?.('Saving changes...', 'info', 1500);
                        submitBtn.click();
                        return;
                    } else if (submitBtn?.disabled) {
                        window.showToast?.('Action in progress. Please wait...', 'info', 1500);
                        return;
                    } else {
                        window.showToast?.('Submitting form...', 'info', 1500);
                        form.requestSubmit ? form.requestSubmit() : form.submit();
                        return;
                    }
                }

                window.showToast?.('No editable form on current page', 'info', 1500);
            }
        };
    }
</script>

