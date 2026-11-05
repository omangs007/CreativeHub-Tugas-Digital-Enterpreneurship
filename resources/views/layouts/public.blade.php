<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ? $title.' — ' : '' }}{{ config('app.name', 'CreativeHub') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="flex min-h-screen flex-col bg-ink-50 font-sans text-ink-900 antialiased">
        <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-white/10 bg-ink-950/95 text-white backdrop-blur">
            <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5">
                    <x-application-logo class="size-9" />
                    <span class="text-lg font-extrabold tracking-tight">Creative<span class="text-violet-400">Hub</span></span>
                </a>

                <nav class="hidden items-center gap-8 text-sm font-medium text-ink-200 md:flex">
                    <a href="{{ route('services.index') }}" @class(['transition hover:text-white', 'text-white' => request()->routeIs('services.*')])>{{ __('Katalog Jasa') }}</a>
                    @foreach ($navCategories as $navCategory)
                        <a href="{{ route('categories.show', $navCategory->slug) }}" @class(['transition hover:text-white', 'text-white' => request()->is('categories/'.$navCategory->slug)])>{{ $navCategory->name }}</a>
                    @endforeach
                </nav>

                <div class="hidden items-center gap-3 md:flex">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold transition hover:bg-white/20">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="px-3 py-2 text-sm font-semibold text-ink-200 transition hover:text-white">{{ __('Masuk') }}</a>
                        <a href="{{ route('register') }}" class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-semibold shadow-lg shadow-violet-600/30 transition hover:bg-violet-500">{{ __('Daftar Gratis') }}</a>
                    @endauth
                </div>

                <button type="button" @click="open = ! open" class="rounded-lg p-2 text-ink-200 hover:bg-white/10 md:hidden" :aria-expanded="open" aria-label="{{ __('Buka menu') }}">
                    <x-lucide name="menu" class="size-6" x-show="! open" />
                    <x-lucide name="x" class="size-6" x-show="open" x-cloak />
                </button>
            </div>

            <div x-show="open" x-cloak x-transition class="border-t border-white/10 px-4 pb-4 md:hidden">
                <nav class="flex flex-col gap-1 pt-3 text-sm font-medium text-ink-200">
                    <a href="{{ route('services.index') }}" class="rounded-lg px-3 py-2 hover:bg-white/10 hover:text-white">{{ __('Katalog Jasa') }}</a>
                    @foreach ($navCategories as $navCategory)
                        <a href="{{ route('categories.show', $navCategory->slug) }}" class="rounded-lg px-3 py-2 hover:bg-white/10 hover:text-white">{{ $navCategory->name }}</a>
                    @endforeach
                </nav>
                <div class="mt-3 flex gap-2 border-t border-white/10 pt-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="flex-1 rounded-lg bg-white/10 px-4 py-2 text-center text-sm font-semibold">{{ __('Dashboard') }}</a>
                    @else
                        <a href="{{ route('login') }}" class="flex-1 rounded-lg bg-white/10 px-4 py-2 text-center text-sm font-semibold">{{ __('Masuk') }}</a>
                        <a href="{{ route('register') }}" class="flex-1 rounded-lg bg-violet-600 px-4 py-2 text-center text-sm font-semibold">{{ __('Daftar Gratis') }}</a>
                    @endauth
                </div>
            </div>
        </header>

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-ink-950 text-ink-300">
            <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3 lg:px-8">
                <div>
                    <a href="{{ route('home') }}" class="flex items-center gap-2.5 text-white">
                        <x-application-logo class="size-8" />
                        <span class="text-lg font-extrabold tracking-tight">Creative<span class="text-violet-400">Hub</span></span>
                    </a>
                    <p class="mt-4 max-w-xs text-sm leading-relaxed">
                        {{ __('Marketplace jasa kreatif lokal: map Roblox, website, dan desain grafis dari freelancer berbakat.') }}
                    </p>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('Kategori') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        @foreach ($navCategories as $navCategory)
                            <li><a href="{{ route('categories.show', $navCategory->slug) }}" class="hover:text-white">{{ $navCategory->name }}</a></li>
                        @endforeach
                    </ul>
                </div>

                <div>
                    <h2 class="text-sm font-semibold text-white">{{ __('CreativeHub') }}</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li><a href="{{ route('services.index') }}" class="hover:text-white">{{ __('Katalog Jasa') }}</a></li>
                        @guest
                            <li><a href="{{ route('register') }}" class="hover:text-white">{{ __('Daftar') }}</a></li>
                            <li><a href="{{ route('login') }}" class="hover:text-white">{{ __('Masuk') }}</a></li>
                        @endguest
                    </ul>
                </div>
            </div>

            <div class="border-t border-white/10">
                <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-ink-400 sm:px-6 lg:px-8">
                    &copy; {{ now()->year }} CreativeHub. {{ __('Proyek tugas kuliah, dijalankan secara lokal untuk demonstrasi.') }}
                </p>
            </div>
        </footer>
    </body>
</html>
