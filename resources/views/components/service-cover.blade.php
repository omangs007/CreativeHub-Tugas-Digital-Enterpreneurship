{{-- Sampul layanan. Tanpa thumbnail, tampilkan gradien sesuai ikon kategori. --}}
@props(['service', 'iconSize' => 'size-10'])

@php
    $gradient = match ($service->category->icon) {
        'gamepad' => 'from-violet-600 via-indigo-600 to-ink-800',
        'code' => 'from-electric-500 via-indigo-600 to-ink-800',
        'palette' => 'from-fuchsia-600 via-violet-600 to-ink-800',
        default => 'from-ink-700 via-violet-700 to-ink-900',
    };
@endphp

<div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-ink-800']) }}>
    @if ($service->thumbnail_path)
        <img src="{{ asset('storage/'.$service->thumbnail_path) }}" alt="{{ $service->title }}" class="size-full object-cover" loading="lazy">
    @else
        <div class="flex size-full items-center justify-center bg-gradient-to-br {{ $gradient }}">
            <div class="absolute -right-6 -top-6 size-28 rounded-full bg-white/10"></div>
            <div class="absolute -bottom-10 -left-4 size-32 rounded-full bg-white/5"></div>
            <x-lucide :name="$service->category->icon" class="relative {{ $iconSize }} text-white/90" />
        </div>
    @endif
</div>
