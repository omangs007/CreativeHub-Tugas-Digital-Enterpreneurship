<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Edit Penawaran') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="post" action="{{ route('freelancer.proposals.update', $proposal) }}" class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg space-y-4">
                @csrf
                @method('patch')
                <p class="text-sm text-gray-600">{{ __('Proyek:') }} <a href="{{ route('projects.show', $proposal->project) }}" class="font-semibold text-violet-600">{{ $proposal->project->title }}</a></p>

                @include('freelancer.proposals._fields')

                <div class="flex items-center gap-4">
                    <x-primary-button>{{ __('Simpan Perubahan') }}</x-primary-button>
                    <a href="{{ route('freelancer.proposals.index') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
