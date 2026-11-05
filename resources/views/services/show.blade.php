<x-public-layout :title="$service->title">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-ink-500" aria-label="Breadcrumb">
            <a href="{{ route('services.index') }}" class="hover:text-violet-600">{{ __('Katalog') }}</a>
            <span class="mx-1.5">/</span>
            <a href="{{ route('categories.show', $service->category->slug) }}" class="hover:text-violet-600">{{ $service->category->name }}</a>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <article>
                <h1 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ $service->title }}</h1>

                @php($profile = $service->freelancer->freelancerProfile)
                @php($hasPublicProfile = $profile && $service->freelancer->hasRole(\App\Enums\RoleName::Freelancer))
                <div class="mt-5 flex items-center gap-3">
                    <x-avatar :name="$service->freelancer->name" class="size-11" />
                    <div>
                        @if ($hasPublicProfile)
                            <a href="{{ route('freelancers.show', $service->user_id) }}" class="font-semibold hover:text-violet-700">{{ $service->freelancer->name }}</a>
                        @else
                            <p class="font-semibold">{{ $service->freelancer->name }}</p>
                        @endif
                        <p class="text-sm text-ink-500">
                            {{ $profile?->headline ?? __('Freelancer CreativeHub') }}
                            @if ($profile)
                                <span class="mx-1">·</span>{{ $profile->availability->label() }}
                            @endif
                        </p>
                    </div>
                </div>

                <x-service-cover :service="$service" icon-size="size-16" class="mt-8 aspect-[16/9] rounded-2xl" />

                <section class="mt-10">
                    <h2 class="text-xl font-bold">{{ __('Tentang layanan ini') }}</h2>
                    <div class="mt-3 whitespace-pre-line leading-relaxed text-ink-700">{{ $service->description }}</div>
                    @if ($service->published_at)
                        <p class="mt-4 text-sm text-ink-400">{{ __('Dipublikasikan :date', ['date' => $service->published_at->translatedFormat('d F Y')]) }}</p>
                    @endif
                </section>

                @if ($profile?->bio)
                    <section class="mt-10 rounded-2xl bg-white p-6 ring-1 ring-ink-100">
                        <h2 class="text-xl font-bold">{{ __('Tentang freelancer') }}</h2>
                        <p class="mt-3 whitespace-pre-line text-ink-700">{{ $profile->bio }}</p>
                        @if (! empty($profile->skills))
                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($profile->skills as $skill)
                                    <span class="rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700">{{ $skill }}</span>
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endif

                <section class="mt-10" id="ulasan">
                    <div class="flex flex-wrap items-center gap-3">
                        <h2 class="text-xl font-bold">{{ __('Ulasan') }}</h2>
                        @if ($ratingCount > 0)
                            <x-star-rating :rating="$ratingAvg" :count="$ratingCount" />
                        @endif
                    </div>
                    @forelse ($reviews as $review)
                        <article class="mt-4 rounded-2xl bg-white p-5 ring-1 ring-ink-100">
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex items-center gap-2">
                                    <x-avatar :name="$review->customer->name" class="size-8 text-sm" />
                                    <span class="font-semibold">{{ $review->customer->name }}</span>
                                </div>
                                <x-star-rating :rating="$review->rating" size="text-sm" />
                            </div>
                            @if ($review->comment)
                                <p class="mt-3 whitespace-pre-line text-sm text-ink-700">{{ $review->comment }}</p>
                            @endif
                            <p class="mt-2 text-xs text-ink-400">{{ $review->created_at->translatedFormat('d F Y') }}</p>
                        </article>
                    @empty
                        <p class="mt-3 text-sm text-ink-500">{{ __('Belum ada ulasan untuk layanan ini.') }}</p>
                    @endforelse
                </section>
            </article>

            <aside>
                <div class="space-y-4 lg:sticky lg:top-24" x-data="{ tier: '{{ $packages->first()->tier->value }}' }">
                    <h2 class="text-lg font-bold">{{ __('Pilih paket') }}</h2>

                    <div @class(['grid gap-1 rounded-xl bg-ink-100 p-1', match ($packages->count()) { 1 => 'grid-cols-1', 2 => 'grid-cols-2', default => 'grid-cols-3' }]) role="tablist">
                        @foreach ($packages as $package)
                            <button type="button" role="tab" @click="tier = '{{ $package->tier->value }}'"
                                :aria-selected="tier === '{{ $package->tier->value }}'"
                                :class="tier === '{{ $package->tier->value }}' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500 hover:text-ink-900'"
                                class="rounded-lg px-3 py-2 text-sm font-semibold transition">
                                {{ $package->tier->label() }}
                            </button>
                        @endforeach
                    </div>

                    @foreach ($packages as $package)
                        <div x-show="tier === '{{ $package->tier->value }}'" @if (! $loop->first) x-cloak @endif role="tabpanel"
                             class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100">
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-bold">{{ $package->name }}</h3>
                                <span class="text-2xl font-extrabold">@rupiah($package->price)</span>
                            </div>
                            <p class="mt-3 text-sm leading-relaxed text-ink-600">{{ $package->description }}</p>

                            <ul class="mt-5 space-y-2 text-sm text-ink-700">
                                <li class="flex items-center gap-2">
                                    <x-lucide name="clock" class="size-4 text-violet-600" />
                                    {{ __('Pengerjaan :days hari', ['days' => $package->delivery_days]) }}
                                </li>
                                <li class="flex items-center gap-2">
                                    <x-lucide name="revision" class="size-4 text-violet-600" />
                                    {{ trans_choice('{0} Tanpa revisi|[1,*] :count kali revisi', $package->revisions, ['count' => $package->revisions]) }}
                                </li>
                            </ul>

                            @if (auth()->id() === $service->user_id)
                                <p class="mt-6 rounded-xl bg-ink-100 px-4 py-3 text-center text-sm font-semibold text-ink-600">{{ __('Ini layanan Anda') }}</p>
                            @else
                                <a href="{{ route('checkout.package', $package) }}"
                                   class="mt-6 block w-full rounded-xl bg-violet-600 px-4 py-3 text-center font-semibold text-white shadow-lg shadow-violet-600/30 transition hover:bg-violet-500">
                                    {{ __('Pesan Paket :tier', ['tier' => $package->tier->label()]) }}
                                </a>
                                @guest
                                    <p class="mt-2 text-center text-xs text-ink-400">{{ __('Anda akan diminta masuk terlebih dahulu.') }}</p>
                                @endguest
                            @endif
                            <p class="mt-2 text-center text-xs text-ink-400">{{ __('Mode demo: pembayaran tidak menggunakan uang sungguhan.') }}</p>
                        </div>
                    @endforeach
                </div>
            </aside>
        </div>
    </div>
</x-public-layout>
