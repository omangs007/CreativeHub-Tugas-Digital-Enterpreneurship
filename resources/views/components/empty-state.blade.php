@props(['title', 'icon' => 'inbox'])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center rounded-2xl border-2 border-dashed border-ink-200 bg-white px-6 py-14 text-center']) }}>
    <span class="flex size-12 items-center justify-center rounded-full bg-violet-50 text-violet-600">
        <x-lucide :name="$icon" class="size-6" />
    </span>
    <h3 class="mt-4 font-bold text-ink-900">{{ $title }}</h3>
    @if ($slot->isNotEmpty())
        <div class="mt-1 max-w-md text-sm text-ink-500">{{ $slot }}</div>
    @endif
</div>
