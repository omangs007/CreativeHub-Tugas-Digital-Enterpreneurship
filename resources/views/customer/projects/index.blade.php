<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Proyek Saya') }}</h2>
            <a href="{{ route('customer.projects.create') }}" class="inline-flex items-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                + {{ __('Posting Proyek') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if ($projects->isEmpty())
                <x-empty-state :title="__('Belum ada proyek')">
                    {{ __('Posting kebutuhan Anda dan terima penawaran dari freelancer.') }}
                </x-empty-state>
            @else
                <ul class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @foreach ($projects as $project)
                        <li>
                            <a href="{{ route('customer.projects.show', $project) }}" class="flex flex-col gap-2 p-4 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <x-status-badge :status="$project->status" />
                                        <span class="text-xs text-gray-500">{{ $project->category->name }}</span>
                                    </div>
                                    <h3 class="mt-1 truncate font-semibold text-gray-900">{{ $project->title }}</h3>
                                </div>
                                <div class="text-sm text-gray-500 sm:text-right">
                                    <p class="font-semibold text-gray-900">{{ $project->budgetLabel() }}</p>
                                    <p>{{ __('Diposting :date', ['date' => $project->created_at->translatedFormat('d F Y')]) }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
