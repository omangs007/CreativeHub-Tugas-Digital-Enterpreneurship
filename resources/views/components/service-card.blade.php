{{-- Membutuhkan service dari scope forCatalogCard() (starting_price, fastest_delivery_days). --}}
@props(['service'])

<a href="{{ route('services.show', $service->slug) }}" class="group flex flex-col overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-ink-100 transition hover:-translate-y-1 hover:shadow-xl hover:shadow-ink-900/10 focus-visible:outline-2 focus-visible:outline-violet-600">
    <x-service-cover :service="$service" class="aspect-[16/10]" />

    <div class="flex flex-1 flex-col p-5">
        <span class="text-xs font-semibold uppercase tracking-wide text-violet-600">{{ $service->category->name }}</span>

        <h3 class="mt-2 line-clamp-2 font-bold leading-snug text-ink-900 group-hover:text-violet-700">{{ $service->title }}</h3>

        <div class="mt-3 flex items-center gap-2 text-sm text-ink-500">
            <span class="flex size-6 items-center justify-center rounded-full bg-ink-100 text-xs font-bold text-ink-700">
                {{ Str::upper(Str::substr($service->freelancer->name, 0, 1)) }}
            </span>
            <span class="truncate">{{ $service->freelancer->name }}</span>
            @if (($service->rating_count ?? 0) > 0)
                <x-star-rating :rating="$service->rating_avg" :count="$service->rating_count" size="text-sm" class="ml-auto shrink-0" />
            @endif
        </div>

        <div class="mt-auto flex items-end justify-between border-t border-ink-100 pt-4">
            <span class="flex items-center gap-1.5 text-xs text-ink-500">
                <x-lucide name="clock" class="size-4" />
                {{ __('Mulai :days hari', ['days' => $service->fastest_delivery_days]) }}
            </span>
            <span class="text-right">
                <span class="block text-xs text-ink-500">{{ __('Mulai dari') }}</span>
                <span class="text-lg font-extrabold text-ink-900">@rupiah($service->starting_price)</span>
            </span>
        </div>
    </div>
</a>
