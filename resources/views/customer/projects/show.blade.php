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

            @if ($project->order)
                <p class="mx-4 rounded-md bg-violet-50 p-3 text-sm text-violet-800 sm:mx-0">
                    {{ __('Penawaran telah diterima dan pesanan :number dibuat.', ['number' => $project->order->order_number]) }}
                    <a href="{{ route('orders.show', $project->order) }}" class="font-semibold underline">{{ __('Lihat pesanan') }}</a>
                </p>
            @endif

            <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-gray-900">{{ __('Penawaran freelancer') }} ({{ $project->proposals->count() }})</h3>
                <x-input-error class="mt-2" :messages="$errors->get('proposal')" />

                @forelse ($project->proposals as $proposal)
                    <article class="mt-4 rounded-lg border border-gray-200 p-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="flex items-center gap-3">
                                <x-avatar :name="$proposal->freelancer->name" class="size-10" />
                                <div>
                                    <a href="{{ route('freelancers.show', $proposal->freelancer_id) }}" target="_blank" class="font-semibold text-gray-900 hover:text-violet-700">{{ $proposal->freelancer->name }}</a>
                                    <p class="text-xs text-gray-500">{{ $proposal->freelancer->freelancerProfile?->headline ?? __('Freelancer CreativeHub') }}</p>
                                </div>
                            </div>
                            <div class="text-sm sm:text-right">
                                <p class="text-lg font-bold text-gray-900">@rupiah($proposal->price)</p>
                                <p class="text-gray-500">{{ __(':days hari · :rev revisi', ['days' => $proposal->delivery_days, 'rev' => $proposal->revisions]) }}</p>
                                <x-status-badge :status="$proposal->status" class="mt-1" />
                            </div>
                        </div>
                        <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $proposal->message }}</p>

                        @can('decide', $proposal)
                            <div class="mt-4 flex flex-wrap gap-3">
                                <form method="post" action="{{ route('customer.proposals.accept', $proposal) }}"
                                      onsubmit="return confirm(@js(__('Terima penawaran dari :name seharga :price? Penawaran lain akan ditolak otomatis.', ['name' => $proposal->freelancer->name, 'price' => \App\Support\Rupiah::format($proposal->price)])))">
                                    @csrf
                                    <button type="submit" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">{{ __('Terima Penawaran') }}</button>
                                </form>
                                <form method="post" action="{{ route('customer.proposals.reject', $proposal) }}"
                                      onsubmit="return confirm(@js(__('Tolak penawaran ini?')))">
                                    @csrf
                                    <button type="submit" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">{{ __('Tolak') }}</button>
                                </form>
                                <a href="{{ route('proposals.chat', $proposal) }}" class="rounded-md px-4 py-2 text-sm font-semibold text-violet-700 hover:bg-violet-50">{{ __('Tanya freelancer') }}</a>
                            </div>
                        @endcan
                    </article>
                @empty
                    <p class="mt-2 text-sm text-gray-500">{{ __('Belum ada penawaran. Freelancer dapat menemukan proyek ini di halaman Proyek.') }}</p>
                @endforelse
            </section>
        </div>
    </div>
</x-app-layout>
