<!-- Global Floating Toast Notifications (Alpine.js) -->
<div x-data="{
    toasts: [],
    add(message, type = 'success', duration = 3500) {
        const id = Date.now() + Math.random().toString(36).substring(2, 9);
        this.toasts.push({ id, message, type });
        if (duration > 0) {
            setTimeout(() => {
                this.remove(id);
            }, duration);
        }
    },
    remove(id) {
        this.toasts = this.toasts.filter(t => t.id !== id);
    },
    init() {
        window.showToast = (message, type = 'success', duration = 3500) => {
            this.add(message, type, duration);
        };

        window.addEventListener('toast', (e) => {
            if (e.detail && e.detail.message) {
                this.add(e.detail.message, e.detail.type || 'success', e.detail.duration || 3500);
            }
        });

        @if (session('success'))
            this.add(@json(session('success')), 'success', 4000);
        @endif
        @if (session('error'))
            this.add(@json(session('error')), 'error', 5000);
        @endif
        @if (session('warning'))
            this.add(@json(session('warning')), 'warning', 4500);
        @endif
        @if (session('info'))
            this.add(@json(session('info')), 'info', 3500);
        @endif
    }
}"
class="fixed bottom-5 right-5 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none sm:px-0 px-4">
    <template x-for="t in toasts" :key="t.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-3 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-y-0 scale-100"
             x-transition:leave-end="opacity-0 translate-y-2 scale-95"
             class="pointer-events-auto rounded-xl p-3.5 shadow-lg border flex items-start gap-3 backdrop-blur-md transition-all text-xs font-medium"
             :class="{
                 'bg-emerald-50/95 border-emerald-200 text-emerald-900 shadow-emerald-500/10': t.type === 'success',
                 'bg-rose-50/95 border-rose-200 text-rose-900 shadow-rose-500/10': t.type === 'error' || t.type === 'danger',
                 'bg-amber-50/95 border-amber-200 text-amber-900 shadow-amber-500/10': t.type === 'warning',
                 'bg-indigo-50/95 border-indigo-200 text-indigo-900 shadow-indigo-500/10': t.type === 'info'
             }">
            <!-- Icon -->
            <div class="shrink-0 mt-0.5">
                <template x-if="t.type === 'success'">
                    <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </template>
                <template x-if="t.type === 'error' || t.type === 'danger'">
                    <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </template>
                <template x-if="t.type === 'warning'">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </template>
                <template x-if="t.type === 'info'">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </template>
            </div>

            <!-- Message Content -->
            <div class="flex-1 leading-snug break-words" x-text="t.message"></div>

            <!-- Dismiss Button -->
            <button @click="remove(t.id)" type="button" class="shrink-0 text-gray-400 hover:text-gray-600 p-0.5 rounded transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </template>
</div>
