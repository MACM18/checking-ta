<!-- In-Site Confirmation Dialog Component (Alpine.js) -->
<div x-data="{
    isOpen: false,
    title: 'Confirm Action',
    message: 'Are you sure you want to proceed?',
    confirmText: 'Confirm',
    cancelText: 'Cancel',
    type: 'danger', // 'danger' | 'warning' | 'primary'
    callback: null,

    init() {
        window.addEventListener('open-confirm-dialog', (e) => {
            this.title = e.detail.title || 'Confirm Action';
            this.message = e.detail.message || 'Are you sure you want to proceed?';
            this.confirmText = e.detail.confirmText || (e.detail.type === 'danger' ? 'Yes, Delete' : 'Confirm');
            this.cancelText = e.detail.cancelText || 'Cancel';
            this.type = e.detail.type || 'danger';
            this.callback = e.detail.onConfirm || null;
            this.isOpen = true;
        });

        // Global form submission interceptor for data-confirm attributes
        document.addEventListener('submit', (e) => {
            const form = e.target;
            if (!form || !form.hasAttribute('data-confirm')) return;

            if (form.dataset.confirmed === 'true') {
                return; // Allow submission
            }

            e.preventDefault();
            const message = form.getAttribute('data-confirm');
            const title = form.getAttribute('data-confirm-title') || (form.querySelector('button[type=submit]')?.textContent.trim() || 'Confirm Action');
            const type = form.getAttribute('data-confirm-type') || 'danger';
            const confirmText = form.getAttribute('data-confirm-button') || (type === 'danger' ? 'Yes, Delete' : 'Confirm');

            this.title = title;
            this.message = message;
            this.confirmText = confirmText;
            this.type = type;
            this.callback = () => {
                form.dataset.confirmed = 'true';
                form.submit();
            };
            this.isOpen = true;
        });
    },

    confirm() {
        this.isOpen = false;
        if (typeof this.callback === 'function') {
            this.callback();
        }
    },

    cancel() {
        this.isOpen = false;
        this.callback = null;
    }
}"
x-show="isOpen"
x-cloak
class="fixed inset-0 z-50 overflow-y-auto"
style="display: none;">

    <!-- Background Backdrop with Blur -->
    <div x-show="isOpen"
         x-transition:enter="ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity"
         @click="cancel()"></div>

    <!-- Dialog Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div x-show="isOpen"
             x-transition:enter="ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             @keydown.escape.window="cancel()"
             class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-gray-100">

            <div class="bg-white p-6 sm:p-7">
                <div class="sm:flex sm:items-start space-y-3 sm:space-y-0 sm:space-x-4">
                    
                    <!-- Icon based on type -->
                    <template x-if="type === 'danger'">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-red-100 text-red-600 sm:mx-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                            </svg>
                        </div>
                    </template>

                    <template x-if="type === 'warning'">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-600 sm:mx-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                        </div>
                    </template>

                    <template x-if="type === 'primary'">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-indigo-600 sm:mx-0">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                        </div>
                    </template>

                    <!-- Text Content -->
                    <div class="text-center sm:text-left flex-1">
                        <h3 class="text-base font-bold leading-6 text-gray-900" x-text="title"></h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 leading-relaxed" x-text="message"></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Buttons -->
            <div class="bg-gray-50/80 px-6 py-4 sm:flex sm:flex-row-reverse sm:px-6 border-t border-gray-100 gap-3">
                <button type="button"
                        @click="confirm()"
                        :class="{
                            'bg-red-600 hover:bg-red-700 active:bg-red-800 text-white': type === 'danger',
                            'bg-amber-600 hover:bg-amber-700 active:bg-amber-800 text-white': type === 'warning',
                            'bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white': type === 'primary'
                        }"
                        class="inline-flex w-full justify-center rounded-xl px-4 py-2.5 text-sm font-bold shadow-xs transition sm:w-auto"
                        x-text="confirmText">
                </button>
                <button type="button"
                        @click="cancel()"
                        class="mt-3 sm:mt-0 inline-flex w-full justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-2xs ring-1 ring-inset ring-gray-300 hover:bg-gray-50 transition sm:w-auto"
                        x-text="cancelText">
                </button>
            </div>

        </div>
    </div>
</div>
