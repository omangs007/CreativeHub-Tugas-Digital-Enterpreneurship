<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Posting Proyek') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('customer.projects.store') }}" class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg space-y-6">
                @csrf
                @include('customer.projects._form')

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Publikasikan Proyek') }}</x-primary-button>
                    <a href="{{ route('customer.projects.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                </div>
                <p class="text-sm text-gray-500">{{ __('Proyek langsung tampil di daftar proyek publik agar freelancer dapat mengirim penawaran.') }}</p>
            </form>
        </div>
    </div>
</x-app-layout>
