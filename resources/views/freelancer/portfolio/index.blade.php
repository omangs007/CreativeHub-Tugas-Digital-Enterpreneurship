<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Portofolio') }}</h2>
            <span class="text-sm text-gray-500">{{ __(':count dari :max item', ['count' => $items->count(), 'max' => $maxItems]) }}</span>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid gap-6 lg:grid-cols-[22rem_minmax(0,1fr)]">
            <div class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg self-start">
                <h3 class="text-lg font-medium text-gray-900">{{ __('Tambah portofolio') }}</h3>

                @if ($items->count() >= $maxItems)
                    <p class="mt-3 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                        {{ __('Batas :max item tercapai. Hapus item lama untuk menambah yang baru.', ['max' => $maxItems]) }}
                    </p>
                @else
                    <form method="post" action="{{ route('portfolio.store') }}" enctype="multipart/form-data" class="mt-4 space-y-5">
                        @csrf
                        @include('freelancer.portfolio._form')
                        <x-primary-button>{{ __('Unggah') }}</x-primary-button>
                    </form>
                @endif
            </div>

            <div>
                @if (session('status'))
                    <p class="mb-4 rounded-md bg-green-50 p-3 text-sm text-green-700">
                        {{ match (session('status')) {
                            'portfolio-created' => __('Portofolio berhasil ditambahkan.'),
                            'portfolio-updated' => __('Portofolio berhasil diperbarui.'),
                            'portfolio-deleted' => __('Portofolio berhasil dihapus.'),
                            default => '',
                        } }}
                    </p>
                @endif

                @if ($items->isEmpty())
                    <x-empty-state :title="__('Belum ada portofolio')">
                        {{ __('Unggah contoh karya terbaik agar customer lebih yakin memilih Anda.') }}
                    </x-empty-state>
                @else
                    <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($items as $item)
                            <div class="overflow-hidden rounded-lg bg-white shadow-sm">
                                <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}" class="aspect-[4/3] w-full bg-gray-100 object-cover" loading="lazy">
                                <div class="p-4">
                                    @if ($item->category)
                                        <span class="text-xs font-semibold uppercase tracking-wide text-violet-600">{{ $item->category->name }}</span>
                                    @endif
                                    <h4 class="font-semibold text-gray-900">{{ $item->title }}</h4>
                                    <div class="mt-4 flex items-center gap-3">
                                        <a href="{{ route('portfolio.edit', $item) }}" class="text-sm font-semibold text-gray-700 hover:text-gray-900">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('portfolio.destroy', $item) }}"
                                              onsubmit="return confirm(@js(__('Hapus portofolio ":title"?', ['title' => $item->title])))">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">{{ __('Hapus') }}</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
