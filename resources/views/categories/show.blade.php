<x-public-layout :title="$category->name">
    <section class="relative overflow-hidden bg-ink-950 text-white">
        <div class="pointer-events-none absolute -right-24 -top-24 size-96 rounded-full bg-violet-600/25 blur-3xl"></div>
        <div class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <nav class="text-sm text-ink-400" aria-label="Breadcrumb">
                <a href="{{ route('home') }}" class="hover:text-white">{{ __('Beranda') }}</a>
                <span class="mx-1.5">/</span>
                <a href="{{ route('services.index') }}" class="hover:text-white">{{ __('Katalog') }}</a>
            </nav>
            <div class="mt-4 flex items-center gap-4">
                <span class="flex size-14 items-center justify-center rounded-2xl bg-violet-600">
                    <x-lucide :name="$category->icon" class="size-7" />
                </span>
                <div>
                    <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $category->name }}</h1>
                    @if ($category->description)
                        <p class="mt-1 text-ink-300">{{ $category->description }}</p>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <x-service-grid :services="$services" :empty-title="__('Belum ada layanan di kategori ini')">
            {{ __('Layanan akan tampil di sini setelah freelancer mempublikasikannya.') }}
        </x-service-grid>
    </section>
</x-public-layout>
