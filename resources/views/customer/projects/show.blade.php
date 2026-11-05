<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $project->title }}</h2>
                <x-status-badge :status="$project->status" />
            </div>
            <div class="flex items-center gap-4 text-sm font-semibold">
                @if ($project->isOpen())
                    <a href="{{ route('projects.show', $project) }}" class="text-violet-600 hover:text-violet-700">{{ __('Lihat halaman publik') }}</a>
                @endif
                @can('update', $project)
                    <a href="{{ route('customer.projects.edit', $project) }}" class="text-gray-700 hover:text-gray-900">{{ __('Edit') }}</a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="mx-4 rounded-md bg-green-50 p-3 text-sm text-green-700 sm:mx-0">
                    {{ match (session('status')) {
                        'project-created' => __('Proyek berhasil dipublikasikan.'),
                        'project-updated' => __('Perubahan proyek tersimpan.'),
                        'project-closed' => __('Proyek ditutup dan tidak lagi menerima penawaran.'),
                        default => '',
                    } }}
                </p>
            @endif
            <x-input-error class="mx-4 sm:mx-0" :messages="$errors->get('project')" />

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]">
                <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                    <h3 class="font-semibold text-gray-900">{{ __('Deskripsi') }}</h3>
                    <p class="mt-3 whitespace-pre-line text-gray-700">{{ $project->description }}</p>
                </section>

                <aside class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg space-y-4 self-start text-sm">
                    <div>
                        <p class="text-gray-500">{{ __('Kategori') }}</p>
                        <p class="font-semibold">{{ $project->category->name }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Anggaran') }}</p>
                        <p class="font-semibold">{{ $project->budgetLabel() }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Tenggat') }}</p>
                        <p class="font-semibold">{{ $project->deadline?->translatedFormat('d F Y') ?? __('Tidak ditentukan') }}</p>
                    </div>
                    <div>
                        <p class="text-gray-500">{{ __('Diposting') }}</p>
                        <p class="font-semibold">{{ $project->created_at->translatedFormat('d F Y') }}</p>
                    </div>

                    @if ($project->isOpen())
                        <form method="post" action="{{ route('customer.projects.close', $project) }}"
                              onsubmit="return confirm(@js(__('Tutup proyek ini? Freelancer tidak dapat mengirim penawaran lagi.')))">
                            @csrf
                            <x-danger-button class="w-full justify-center">{{ __('Tutup Proyek') }}</x-danger-button>
                        </form>
                    @endif
                </aside>
            </div>

            <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-gray-900">{{ __('Penawaran freelancer') }}</h3>
                <p class="mt-2 text-sm text-gray-500">{{ __('Belum ada penawaran.') }}</p>
            </section>
        </div>
    </div>
</x-app-layout>
