@props(['status'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
    'bg-gray-100 text-gray-700' => $status === \App\Enums\ServiceStatus::Draft,
    'bg-amber-100 text-amber-800' => $status === \App\Enums\ServiceStatus::PendingReview,
    'bg-emerald-100 text-emerald-800' => $status === \App\Enums\ServiceStatus::Published,
    'bg-red-100 text-red-700' => $status === \App\Enums\ServiceStatus::Rejected,
]) }}>{{ $status->label() }}</span>
