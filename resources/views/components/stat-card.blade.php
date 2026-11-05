@props(['label', 'value', 'hint' => null, 'href' => null])

@php($tag = $href ? 'a' : 'div')
<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => 'block rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100'.($href ? ' transition hover:ring-violet-300' : '')]) }}>
    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-extrabold text-gray-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-500">{{ $hint }}</p>
    @endif
</{{ $tag }}>
