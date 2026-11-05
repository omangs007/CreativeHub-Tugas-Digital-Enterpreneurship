<x-public-layout :title="__('Proyek Terbuka')">
    <section class="bg-ink-950 text-white">
        <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ __('Proyek Terbuka') }}</h1>
            <p class="mt-2 text-ink-300">{{ __('Kebutuhan custom dari customer. Freelancer dapat mengirim penawaran.') }}</p>

            <form action="{{ route('projects.index') }}" method="get" class="mt-8 flex max-w-2xl flex-col gap-2 sm:flex-row" role="search">
                @if ($selectedCategory)
                    <input type="hidden" name="kategori" value="{{ $selectedCategory->slug }}">
                @endif
                <label for="project-search" class="sr-only">{{ __('Cari proyek') }}</label>
                <div class="relative flex-1">
                    <x-lucide name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-400" />
                    <input id="project-search" type="search" name="q" value="{{ $search }}" maxlength="100" placeholder="{{ __('Cari judul proyek…') }}"
                        class="w-full rounded-xl border-0 bg-white py-3 pl-12 pr-4 text-ink-900 placeholder:text-ink-400 focus:ring-2 focus:ring-violet-500">
                </div>
                <button type="submit" class="rounded-xl bg-violet-600 px-6 py-3 font-semibold transition hover:bg-violet-500">{{ __('Cari') }}</button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="flex flex-wrap gap-2" aria-label="{{ __('Filter kategori') }}">
            @php($pill = 'rounded-full px-4 py-2 text-sm font-semibold transition')
            <a href="{{ route('projects.index', array_filter(['q' => $search])) }}"
               @class([$pill, 'bg-ink-900 text-white' => ! $selectedCategory, 'bg-white text-ink-600 ring-1 ring-ink-200 hover:ring-violet-400' => $selectedCategory])>{{ __('Semua') }}</a>
            @foreach ($categories as $category)
                <a href="{{ route('projects.index', array_filter(['q' => $search, 'kategori' => $category->slug])) }}"
                   @class([$pill, 'bg-ink-900 text-white' => $selectedCategory?->is($category), 'bg-white text-ink-600 ring-1 ring-ink-200 hover:ring-violet-400' => ! $selectedCategory?->is($category)])>{{ $category->name }}</a>
            @endforeach
        </nav>

        <div class="mt-8 space-y-4">
            @forelse ($projects as $project)
                <a href="{{ route('projects.show', $project) }}" class="block rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100 transition hover:-translate-y-0.5 hover:shadow-lg hover:shadow-ink-900/10">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <span class="text-xs font-semibold uppercase tracking-wide text-violet-600">{{ $project->category->name }}</span>
                            <h2 class="mt-1 text-lg font-bold text-ink-900">{{ $project->title }}</h2>
                            <p class="mt-2 line-clamp-2 text-sm text-ink-600">{{ $project->description }}</p>
                        </div>
                        <div class="shrink-0 text-sm sm:text-right">
                            <p class="font-extrabold text-ink-900">{{ $project->budgetLabel() }}</p>
                            <p class="mt-1 text-ink-500">
                                {{ $project->deadline ? __('Tenggat :date', ['date' => $project->deadline->translatedFormat('d F Y')]) : __('Tanpa tenggat') }}
                            </p>
                        </div>
                    </div>
                    <p class="mt-4 text-xs text-ink-400">{{ __('Diposting oleh :name · :ago', ['name' => $project->customer->name, 'ago' => $project->created_at->diffForHumans()]) }}</p>
                </a>
            @empty
                <x-empty-state :title="__('Belum ada proyek terbuka')">
                    {{ __('Proyek dari customer akan tampil di sini.') }}
                </x-empty-state>
            @endforelse
        </div>

        <div class="mt-10">{{ $projects->links() }}</div>
    </section>
</x-public-layout>
