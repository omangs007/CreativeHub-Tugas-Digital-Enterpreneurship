<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Tambah Layanan') }}</h2>
    </x-slot>

    <div class="py-12">
        <form method="post" action="{{ route('freelancer.services.store') }}" enctype="multipart/form-data" class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @csrf
            @include('freelancer.services._form')

            <div class="flex items-center gap-4 px-4 sm:px-0">
                <x-primary-button>{{ __('Simpan sebagai Draf') }}</x-primary-button>
                <a href="{{ route('freelancer.services.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
            </div>
            <p class="px-4 text-sm text-gray-500 sm:px-0">{{ __('Setelah disimpan, ajukan review dari daftar layanan. Layanan tampil di katalog setelah disetujui admin.') }}</p>
        </form>
    </div>
</x-app-layout>
