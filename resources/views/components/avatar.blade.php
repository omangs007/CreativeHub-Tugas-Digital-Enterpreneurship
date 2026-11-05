{{-- Avatar inisial nama (belum ada upload foto profil). --}}
@props(['name'])

<span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-full bg-ink-900 font-bold text-white']) }} aria-hidden="true">
    {{ Str::upper(Str::substr($name, 0, 1)) }}
</span>
