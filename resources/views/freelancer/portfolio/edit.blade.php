<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Portofolio') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" class="mb-6 aspect-[4/3] w-full max-w-sm rounded-md bg-gray-100 object-cover">

                <form method="post" action="{{ route('portfolio.update', $item) }}" enctype="multipart/form-data" class="space-y-5">
                    @csrf
                    @method('patch')
                    @include('freelancer.portfolio._form', ['item' => $item])

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Simpan') }}</x-primary-button>
                        <a href="{{ route('portfolio.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
