{{-- Tampilan rating bintang (dibulatkan ke bintang terdekat) dengan label aksesibel. --}}
@props(['rating', 'count' => null, 'size' => 'text-base'])

@php($rounded = (int) round((float) $rating))
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1']) }} role="img" aria-label="{{ __('Rating :r dari 5', ['r' => number_format((float) $rating, 1, ',', '.')]) }}">
    <span class="{{ $size }} leading-none tracking-tight" aria-hidden="true">
        @foreach (range(1, 5) as $star)
            <span @class(['text-amber-400' => $star <= $rounded, 'text-gray-300' => $star > $rounded])>&#9733;</span>
        @endforeach
    </span>
    @if ($count !== null)
        <span class="text-xs text-gray-500">{{ number_format((float) $rating, 1, ',', '.') }} ({{ $count }})</span>
    @endif
</span>
