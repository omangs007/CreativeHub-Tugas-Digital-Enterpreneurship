<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Layanan') }}</h2>
            <x-service-status-badge :status="$service->status" />
        </div>
    </x-slot>

    <div class="py-12">
        <form method="post" action="{{ route('freelancer.services.update', $service) }}" enctype="multipart/form-data" class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @csrf
            @method('patch')

            @if ($service->status === \App\Enums\ServiceStatus::Published)
                <p class="mx-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800 sm:mx-0">
                    {{ __('Layanan ini sedang tayang. Menyimpan perubahan akan mengirimnya kembali ke review admin, dan layanan tidak tampil di katalog sampai disetujui.') }}
                </p>
            @endif

            @include('freelancer.services._form')

            <div class="flex items-center gap-4 px-4 sm:px-0">
                <x-primary-button>{{ __('Simpan Perubahan') }}</x-primary-button>
                <a href="{{ route('freelancer.services.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
            </div>
        </form>
    </div>
</x-app-layout>
