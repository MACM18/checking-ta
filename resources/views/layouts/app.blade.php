<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Checking TA') }}</title>
        <link rel="icon" type="image/webp" href="https://storage.macm.dev/portfolio/favicons/cmj8uwynb0000nj0jnkb3tk15/1786703371734.webp">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-900 h-full">
        <div class="min-h-full">
            <!-- Sidebar Navigation (Desktop Fixed Sidebar & Mobile Slide-Over) -->
            @include('layouts.navigation')

            <!-- Main Content Area (Offset for Desktop 64-width Sidebar) -->
            <div class="md:pl-64 flex flex-col flex-1 min-h-screen bg-slate-50">

                <!-- Page Header -->
                @isset($header)
                    <header class="bg-white border-b border-gray-200/80 shadow-2xs">
                        <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main class="flex-1 pb-12">
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
