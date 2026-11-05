@props(['services', 'emptyTitle'])

@if ($services->isEmpty())
    <x-empty-state icon="search" :title="$emptyTitle">
        {{ $slot }}
    </x-empty-state>
@else
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($services as $service)
            <x-service-card :service="$service" />
        @endforeach
    </div>

    <div class="mt-10">
        {{ $services->links() }}
    </div>
@endif
