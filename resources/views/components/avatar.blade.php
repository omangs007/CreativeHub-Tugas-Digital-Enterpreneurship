{{-- Foto profil user, atau inisial nama jika belum mengunggah foto. --}}
@props(['user' => null, 'name' => null])

@php($displayName = $user?->name ?? $name ?? '?')
@php($src = $user?->avatarUrl())

@if ($src)
    <img src="{{ $src }}" alt="{{ __('Foto :name', ['name' => $displayName]) }}" {{ $attributes->merge(['class' => 'shrink-0 rounded-full object-cover']) }}>
@else
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-ink-900 font-bold text-white']) }} aria-hidden="true">
        {{ Str::upper(Str::substr($displayName, 0, 1)) }}
    </span>
@endif
