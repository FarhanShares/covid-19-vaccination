<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Covid-19 Vaccination Program') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    @livewireStyles
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <styles>
        {{-- --}}
    </styles>
</head>
<body class="font-sans antialiased text-gray-900">
    <img class="hidden dark:block absolute -left-20 top-0 max-w-[877px] max-h-screen" src="https://laravel.com/assets/img/welcome/background.svg" />
    <div class="absolute inset-0 dark:hidden bg-gradient-to-r from-rose-100 to-teal-100"></div>

    <div class="w-full bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
        <div class="w-full relative min-h-screen flex flex-col items-center justify-center selection:bg-[#FF2D20] selection:text-white">
            <div class="relative w-full max-w-2xl px-6 lg:max-w-7xl">

                <header class="flex items-center justify-center">
                    <a href="/" wire:navigate>
                        <x-application-logo class="w-20 h-20 text-gray-500 fill-current" />
                    </a>
                </header>

                <main class="mt-10">
                    {{ $slot }}
                </main>

                <footer class="py-16 text-sm text-center text-black dark:text-white/70">
                    Laravel v{{ Illuminate\Foundation\Application::VERSION }} (PHP v{{ PHP_VERSION }}) © Farhan Israq - All rights reserved.
                </footer>
            </div>
        </div>
    </div>
    @livewireScripts
</body>
</html>
