<x-public-layout>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-ink-950 text-white">
        <div class="pointer-events-none absolute -top-40 right-0 size-[36rem] rounded-full bg-violet-600/30 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-48 -left-24 size-[30rem] rounded-full bg-electric-500/20 blur-3xl"></div>

        <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
            <div class="max-w-3xl">
                <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 text-xs font-semibold text-violet-200">
                    <x-lucide name="sparkles" class="size-3.5" />
                    {{ __('Marketplace jasa kreatif lokal') }}
                </span>

                <h1 class="mt-6 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl lg:text-6xl">
                    {{ __('Wujudkan ide kreatifmu bersama') }}
                    <span class="bg-gradient-to-r from-violet-400 to-electric-400 bg-clip-text text-transparent">{{ __('freelancer berbakat') }}</span>
                </h1>

                <p class="mt-6 max-w-2xl text-lg text-ink-200">
                    {{ __('Pesan jasa desain map Roblox, pembuatan website, hingga poster dan desain grafis. Pilih paket yang sesuai, atau posting kebutuhanmu dan terima penawaran.') }}
                </p>

                <form action="{{ route('services.index') }}" method="get" class="mt-8 flex max-w-xl flex-col gap-2 sm:flex-row" role="search">
                    <label for="hero-search" class="sr-only">{{ __('Cari jasa') }}</label>
                    <div class="relative flex-1">
                        <x-lucide name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-400" />
                        <input id="hero-search" type="search" name="q" placeholder="{{ __('Coba: map obby, landing page, poster event…') }}"
                            class="w-full rounded-xl border-0 bg-white py-3.5 pl-12 pr-4 text-ink-900 placeholder:text-ink-400 focus:ring-2 focus:ring-violet-500">
                    </div>
                    <button type="submit" class="rounded-xl bg-violet-600 px-6 py-3.5 font-semibold shadow-lg shadow-violet-600/30 transition hover:bg-violet-500">
                        {{ __('Cari Jasa') }}
                    </button>
                </form>

                @if ($categories->isNotEmpty())
                    <div class="mt-6 flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-ink-400">{{ __('Populer:') }}</span>
                        @foreach ($categories as $category)
                            <a href="{{ route('categories.show', $category->slug) }}" class="rounded-full border border-white/15 px-3 py-1 text-ink-200 transition hover:border-violet-400 hover:text-white">{{ $category->name }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- Kategori --}}
    <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Jelajahi kategori') }}</h2>
                <p class="mt-2 text-ink-500">{{ __('Tiga bidang kreatif utama di CreativeHub.') }}</p>
            </div>
        </div>

        <div class="mt-8 grid gap-5 md:grid-cols-3">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category->slug) }}" class="group rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-ink-900/10">
                    <span class="flex size-12 items-center justify-center rounded-xl bg-ink-950 text-violet-300 transition group-hover:bg-violet-600 group-hover:text-white">
                        <x-lucide :name="$category->icon" class="size-6" />
                    </span>
                    <h3 class="mt-5 text-lg font-bold">{{ $category->name }}</h3>
                    <p class="mt-1 text-sm text-ink-500">{{ $category->description }}</p>
                    <p class="mt-4 flex items-center gap-1 text-sm font-semibold text-violet-600">
                        {{ trans_choice('{0} Belum ada layanan|[1,*] :count layanan', $category->services_count, ['count' => $category->services_count]) }}
                        <x-lucide name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                    </p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- Layanan terbaru --}}
    <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Layanan terbaru') }}</h2>
                <p class="mt-2 text-ink-500">{{ __('Paket jasa yang baru dipublikasikan freelancer.') }}</p>
            </div>
            <a href="{{ route('services.index') }}" class="hidden items-center gap-1 text-sm font-semibold text-violet-600 hover:text-violet-700 sm:flex">
                {{ __('Lihat semua') }} <x-lucide name="arrow-right" class="size-4" />
            </a>
        </div>

        @if ($featuredServices->isEmpty())
            <x-empty-state class="mt-8" :title="__('Belum ada layanan yang dipublikasikan')">
                {{ __('Layanan dari freelancer akan tampil di sini setelah dipublikasikan.') }}
            </x-empty-state>
        @else
            <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featuredServices as $service)
                    <x-service-card :service="$service" />
                @endforeach
            </div>
        @endif
    </section>

    {{-- Freelancer unggulan --}}
    @if ($featuredFreelancers->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pb-16 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('Freelancer unggulan') }}</h2>
            <p class="mt-2 text-ink-500">{{ __('Freelancer dengan layanan aktif terbanyak.') }}</p>

            <div class="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($featuredFreelancers as $freelancer)
                    <a href="{{ route('freelancers.show', $freelancer) }}" class="group flex flex-col items-center rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-ink-100 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-ink-900/10">
                        <x-avatar :user="$freelancer" class="size-16 bg-gradient-to-br from-violet-500 to-electric-500 text-2xl" />
                        <h3 class="mt-4 font-bold group-hover:text-violet-700">{{ $freelancer->name }}</h3>
                        <p class="mt-1 line-clamp-2 text-sm text-ink-500">{{ $freelancer->freelancerProfile->headline ?? __('Freelancer CreativeHub') }}</p>
                        <p class="mt-3 text-xs font-semibold text-violet-600">{{ __(':count layanan aktif', ['count' => $freelancer->published_services_count]) }}</p>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    {{-- CTA --}}
    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-violet-700 via-indigo-700 to-ink-900 px-6 py-12 text-white sm:px-12">
            <div class="pointer-events-none absolute -right-20 -top-20 size-72 rounded-full bg-white/10 blur-2xl"></div>
            <div class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                <div class="max-w-xl">
                    <h2 class="text-2xl font-extrabold sm:text-3xl">{{ __('Punya keahlian kreatif?') }}</h2>
                    <p class="mt-2 text-violet-100">{{ __('Daftar gratis, aktifkan akun freelancer, dan tawarkan jasamu kepada customer CreativeHub.') }}</p>
                </div>
                @auth
                    <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 font-semibold text-ink-900 transition hover:bg-violet-50">
                        {{ __('Kelola Akun') }} <x-lucide name="arrow-right" class="size-4" />
                    </a>
                @else
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-white px-6 py-3 font-semibold text-ink-900 transition hover:bg-violet-50">
                        {{ __('Mulai Sekarang') }} <x-lucide name="arrow-right" class="size-4" />
                    </a>
                @endauth
            </div>
        </div>
    </section>
</x-public-layout>
