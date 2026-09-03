<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-bold text-2xl text-gray-900 leading-tight">
                    {{ __('Manage Permissions: ') }} <span class="text-indigo-600">{{ $user->name }}</span>
                </h2>
                <p class="text-xs text-gray-500 mt-1">
                    {{ __('Assign specific capability overrides on top of the user\'s base role.') }}
                </p>
            </div>
            <a href="{{ route('permissions.index') }}" class="text-xs font-semibold text-gray-600 hover:text-gray-900 transition">
                &larr; Back to Permissions Matrix
            </a>
        </div>
    </x-slot>

    <div class="py-8" x-data="{
        permissions: @js($userPermissions),
        toggleAll(state) {
            if (state) {
                this.permissions = @js(array_keys($availablePermissions));
            } else {
                this.permissions = [];
            }
        },
        has(perm) {
            return this.permissions.includes(perm);
        }
    }">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            <!-- User Context Header Card -->
            <div class="bg-white rounded-2xl shadow-xs border border-gray-100 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center space-x-4">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-indigo-600 to-blue-500 text-white font-bold flex items-center justify-center text-base">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="flex items-center space-x-2">
                            <h3 class="font-bold text-base text-gray-900">{{ $user->name }}</h3>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $user->isEditor() ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-700' }}">
                                Base Role: {{ ucfirst($user->role) }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $user->email }}</p>
                    </div>
                </div>

                <!-- Quick Selection Controls -->
                <div class="flex items-center space-x-2 text-xs">
                    <button type="button" @click="toggleAll(true)" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
                        Select All
                    </button>
                    <button type="button" @click="toggleAll(false)" class="px-3 py-1.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold transition">
                        Clear All
                    </button>
                </div>
            </div>

            <!-- Permissions Form -->
            <form action="{{ route('permissions.update', $user) }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($availablePermissions as $key => $meta)
                        <label class="relative flex items-start p-5 bg-white rounded-2xl border transition cursor-pointer shadow-xs hover:border-indigo-300"
                               :class="has('{{ $key }}') ? 'border-indigo-500 bg-indigo-50/20 ring-1 ring-indigo-500' : 'border-gray-200'">
                            <div class="flex h-6 items-center">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $key }}"
                                       x-model="permissions"
                                       class="h-4.5 w-4.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                            </div>
                            <div class="ml-3.5 flex-1">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-bold text-gray-900">{{ $meta['name'] }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-extrabold uppercase tracking-wider bg-gray-100 text-gray-600">
                                        {{ $meta['category'] }}
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                                    {{ $meta['description'] }}
                                </p>

                                @if($key === \App\Models\User::PERM_MANAGE_CHECKLISTS)
                                    <div class="mt-2.5 inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-800 border border-amber-200">
                                        Special: Grants access to /checklists to create & edit templates
                                    </div>
                                @endif
                            </div>
                        </label>
                    @endforeach
                </div>

                <!-- Form Submit Action -->
                <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                    <a href="{{ route('permissions.index') }}" class="px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-xs transition">
                        Save Permissions &rarr;
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
