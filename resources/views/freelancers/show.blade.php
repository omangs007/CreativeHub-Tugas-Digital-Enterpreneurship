<x-public-layout :title="$freelancer->name">
    <section class="relative overflow-hidden bg-ink-950 text-white">
        <div class="pointer-events-none absolute -right-24 -top-24 size-96 rounded-full bg-violet-600/25 blur-3xl"></div>
        <div class="relative mx-auto flex max-w-7xl flex-col gap-6 px-4 py-12 sm:flex-row sm:items-center sm:px-6 lg:px-8">
            <x-avatar :user="$freelancer" class="size-24 bg-gradient-to-br from-violet-500 to-electric-500 text-4xl ring-4 ring-white/10" />

            <div class="flex-1">
                <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $freelancer->name }}</h1>
                <p class="mt-1 text-lg text-ink-200">{{ $profile->headline ?? __('Freelancer CreativeHub') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-ink-300">
                    <span @class([
                        'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold',
                        'bg-emerald-400/15 text-emerald-300' => $profile->availability === \App\Enums\FreelancerAvailability::Available,
                        'bg-amber-400/15 text-amber-300' => $profile->availability === \App\Enums\FreelancerAvailability::Busy,
                        'bg-white/10 text-ink-300' => $profile->availability === \App\Enums\FreelancerAvailability::Unavailable,
                    ])>
                        <span class="size-1.5 rounded-full bg-current"></span>{{ $profile->availability->label() }}
                    </span>
                    <span>{{ __('Bergabung :date', ['date' => $freelancer->created_at->translatedFormat('F Y')]) }}</span>
                    @if ($ratingCount > 0)
                        <x-star-rating :rating="$ratingAvg" :count="$ratingCount" class="[&_.text-gray-500]:text-ink-300" />
                    @endif
                    @if ($profile->portfolio_url)
                        <a href="{{ $profile->portfolio_url }}" target="_blank" rel="noopener noreferrer nofollow" class="font-semibold text-violet-300 hover:text-white">
                            {{ __('Portofolio eksternal') }} &nearr;
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <div class="mx-auto grid max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-[20rem_minmax(0,1fr)] lg:px-8">
        <aside class="space-y-6">
            <section class="rounded-2xl bg-white p-6 ring-1 ring-ink-100">
                <h2 class="font-bold">{{ __('Tentang') }}</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-ink-700">{{ $profile->bio ?: __('Freelancer ini belum menulis bio.') }}</p>
            </section>

            @if (! empty($profile->skills))
                <section class="rounded-2xl bg-white p-6 ring-1 ring-ink-100">
                    <h2 class="font-bold">{{ __('Keahlian') }}</h2>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($profile->skills as $skill)
                            <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $skill }}</span>
                        @endforeach
                    </div>
                </section>
            @endif
        </aside>

        <div class="space-y-12">
            <section>
                <h2 class="text-2xl font-extrabold tracking-tight">{{ __('Layanan') }}</h2>
                <div class="mt-6">
                    @if ($services->isEmpty())
                        <x-empty-state :title="__('Belum ada layanan yang dipublikasikan')" />
                    @else
                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($services as $service)
                                <x-service-card :service="$service" />
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            @if ($reviews->isNotEmpty())
                <section>
                    <h2 class="text-2xl font-extrabold tracking-tight">{{ __('Ulasan terbaru') }}</h2>
                    <div class="mt-6 grid gap-4 sm:grid-cols-2">
                        @foreach ($reviews as $review)
                            <article class="rounded-2xl bg-white p-5 ring-1 ring-ink-100">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-semibold">{{ $review->customer->name }}</span>
                                    <x-star-rating :rating="$review->rating" size="text-sm" />
                                </div>
                                @if ($review->comment)
                                    <p class="mt-2 text-sm text-ink-700">{{ $review->comment }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif

            <section>
                <h2 class="text-2xl font-extrabold tracking-tight">{{ __('Portofolio') }}</h2>
                <div class="mt-6">
                    @if ($freelancer->portfolioItems->isEmpty())
                        <x-empty-state :title="__('Belum ada portofolio')" />
                    @else
                        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
                            @foreach ($freelancer->portfolioItems as $item)
                                <figure class="overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-ink-100">
                                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" class="aspect-[4/3] w-full bg-ink-100 object-cover" loading="lazy">
                                    <figcaption class="p-5">
                                        @if ($item->category)
                                            <span class="text-xs font-semibold uppercase tracking-wide text-violet-600">{{ $item->category->name }}</span>
                                        @endif
                                        <h3 class="font-bold">{{ $item->title }}</h3>
                                        @if ($item->description)
                                            <p class="mt-1 text-sm text-ink-500">{{ $item->description }}</p>
                                        @endif
                                        @if ($item->project_url)
                                            <a href="{{ $item->project_url }}" target="_blank" rel="noopener noreferrer nofollow" class="mt-3 inline-block text-sm font-semibold text-violet-600 hover:text-violet-700">
                                                {{ __('Lihat proyek') }} &nearr;
                                            </a>
                                        @endif
                                    </figcaption>
                                </figure>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>
        </div>
    </div>
</x-public-layout>
