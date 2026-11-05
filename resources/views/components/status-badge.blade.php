{{-- Badge untuk enum status yang mengimplementasikan getLabel() dan getColor() (warna Filament). --}}
@props(['status'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
    match ($status->getColor()) {
        'success' => 'bg-emerald-100 text-emerald-800',
        'warning' => 'bg-amber-100 text-amber-800',
        'danger' => 'bg-red-100 text-red-700',
        'info' => 'bg-sky-100 text-sky-800',
        'primary' => 'bg-violet-100 text-violet-800',
        default => 'bg-gray-100 text-gray-700',
    },
]) }}>{{ $status->getLabel() }}</span>
