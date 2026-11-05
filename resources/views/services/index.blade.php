<x-public-layout :title="__('Katalog Jasa')">
    <section class="bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Katalog Jasa Kreatif') }}</h1>
            <p class="mt-2 text-ink-300">{{ __('Temukan paket jasa yang sesuai kebutuhan dan anggaranmu.') }}</p>

            <form action="{{ route('services.index') }}" method="get" class="mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row" role="search">
                @if ($selectedCategory)
                    <input type="hidden" name="kategori" value="{{ $selectedCategory->slug }}">
                @endif
                <label for="catalog-search" class="sr-only">{{ __('Cari jasa') }}</label>
                <div class="relative flex-1">
                    <x-lucide name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-400" />
                    <input id="catalog-search" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="{{ __('Cari judul layanan…') }}"
                        class="w-full rounded-xl border-0 bg-white py-3 pl-12 pr-4 text-ink-900 placeholder:text-ink-400 focus:ring-2 focus:ring-violet-500">
                </div>
                <button type="submit" class="rounded-xl bg-violet-600 px-6 py-3 font-semibold transition hover:bg-violet-500">{{ __('Cari') }}</button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        @php($baseParams = array_filter(['q' => $search, 'harga_min' => $minPrice, 'harga_maks' => $maxPrice, 'urut' => $sort !== 'terbaru' ? $sort : null], fn ($v) => $v !== null && $v !== ''))
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Filter kategori') }}">
            @php($pill = 'rounded-full px-4 py-2 text-sm font-semibold transition')
            <a href="{{ route('services.index', $baseParams) }}"
               @class([$pill, 'bg-ink-900 text-white' => ! $selectedCategory, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:ring-violet-400' => $selectedCategory])>
                {{ __('Semua') }}
            </a>
            @foreach ($categories as $category)
                <a href="{{ route('services.index', [...$baseParams, 'kategori' => $category->slug]) }}"
                   @class([$pill, 'bg-ink-900 text-white' => $selectedCategory?->is($category), 'bg-white text-ink-600 ring-1 ring-ink-200 hover:ring-violet-400' => ! $selectedCategory?->is($category)])>
                    {{ $category->name }}
                </a>
            @endforeach
        </nav>

        <form action="{{ route('services.index') }}" method="get" class="mt-6 flex flex-wrap items-end gap-3 rounded-2xl bg-white p-4 ring-1 ring-ink-100">
            @if ($search !== '')
                <input type="hidden" name="q" value="{{ $search }}">
            @endif
            @if ($selectedCategory)
                <input type="hidden" name="kategori" value="{{ $selectedCategory->slug }}">
            @endif

            <label class="text-sm">
                <span class="block font-semibold text-ink-700">{{ __('Harga mulai dari (Rp)') }}</span>
                <input type="number" name="harga_min" min="0" step="1000" value="{{ $minPrice }}" placeholder="0"
                    class="mt-1 w-40 rounded-lg border-ink-200 text-sm focus:border-violet-500 focus:ring-violet-500">
            </label>
            <label class="text-sm">
                <span class="block font-semibold text-ink-700">{{ __('Sampai (Rp)') }}</span>
                <input type="number" name="harga_maks" min="0" step="1000" value="{{ $maxPrice }}" placeholder="{{ __('Tanpa batas') }}"
                    class="mt-1 w-40 rounded-lg border-ink-200 text-sm focus:border-violet-500 focus:ring-violet-500">
            </label>
            <label class="text-sm">
                <span class="block font-semibold text-ink-700">{{ __('Urutkan') }}</span>
                <select name="urut" class="mt-1 w-44 rounded-lg border-ink-200 text-sm focus:border-violet-500 focus:ring-violet-500">
                    @foreach ($sorts as $value => $label)
                        <option value="{{ $value }}" @selected($sort === $value)>{{ __($label) }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="rounded-lg bg-ink-900 px-4 py-2 text-sm font-semibold text-white hover:bg-ink-700">{{ __('Terapkan') }}</button>
            @if ($minPrice !== null || $maxPrice !== null || $sort !== 'terbaru')
                <a href="{{ route('services.index', array_filter(['q' => $search, 'kategori' => $selectedCategory?->slug])) }}" class="px-2 py-2 text-sm font-semibold text-ink-500 hover:text-ink-900">{{ __('Reset') }}</a>
            @endif
        </form>

        <p class="mt-6 text-sm text-ink-500">
            {{ trans_choice('{0} Tidak ada hasil|[1,*] :count layanan ditemukan', $services->total(), ['count' => $services->total()]) }}
            @if ($search !== '')
                {{ __('untuk ":q"', ['q' => $search]) }}
            @endif
        </p>

        <div class="mt-6">
            <x-service-grid :services="$services" :empty-title="__('Tidak ada layanan yang cocok')">
                {{ __('Coba kata kunci lain atau pilih kategori yang berbeda.') }}
            </x-service-grid>
        </div>
    </section>
</x-public-layout>
