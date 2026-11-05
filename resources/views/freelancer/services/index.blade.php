<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Layanan Saya') }}</h2>
            <a href="{{ route('freelancer.services.create') }}" class="inline-flex items-center rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500">
                + {{ __('Tambah Layanan') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <p class="mx-4 rounded-md bg-green-50 p-3 text-sm text-green-700 sm:mx-0">
                    {{ match (session('status')) {
                        'service-created' => __('Layanan disimpan sebagai draf.'),
                        'service-updated' => __('Perubahan layanan tersimpan.'),
                        'service-submitted' => __('Layanan diajukan untuk review admin.'),
                        'service-withdrawn' => __('Layanan ditarik dan kembali menjadi draf.'),
                        'service-deleted' => __('Layanan dihapus.'),
                        default => '',
                    } }}
                </p>
            @endif

            <x-input-error class="mx-4 sm:mx-0" :messages="$errors->get('service')" />

            @if ($services->isEmpty())
                <x-empty-state :title="__('Belum ada layanan')">
                    {{ __('Buat layanan pertama Anda dengan paket Basic, Standard, atau Premium.') }}
                </x-empty-state>
            @else
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    <ul class="divide-y divide-gray-100">
                        @foreach ($services as $service)
                            <li class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:p-6">
                                <x-service-cover :service="$service" icon-size="size-6" class="aspect-[16/10] w-full shrink-0 rounded-md sm:w-32" />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <x-service-status-badge :status="$service->status" />
                                        <span class="text-xs text-gray-500">{{ $service->category->name }}</span>
                                    </div>
                                    <h3 class="mt-1 truncate font-semibold text-gray-900">{{ $service->title }}</h3>
                                    @if ($service->status === \App\Enums\ServiceStatus::Rejected && $service->rejection_reason)
                                        <p class="mt-1 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                                            <span class="font-semibold">{{ __('Alasan penolakan:') }}</span> {{ $service->rejection_reason }}
                                        </p>
                                    @endif
                                    <p class="mt-1 text-sm text-gray-500">
                                        @if ($service->active_packages_count > 0)
                                            {{ __(':count paket aktif · mulai :price', ['count' => $service->active_packages_count, 'price' => \App\Support\Rupiah::format($service->starting_price)]) }}
                                        @else
                                            {{ __('Belum ada paket aktif') }}
                                        @endif
                                        · {{ __('diperbarui :date', ['date' => $service->updated_at->translatedFormat('d F Y')]) }}
                                    </p>
                                </div>

                                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm font-semibold">
                                    @if ($service->status === \App\Enums\ServiceStatus::Published)
                                        <a href="{{ route('services.show', $service->slug) }}" class="text-violet-600 hover:text-violet-700">{{ __('Lihat') }}</a>
                                    @endif

                                    <a href="{{ route('freelancer.services.edit', $service) }}" class="text-gray-700 hover:text-gray-900">{{ __('Edit') }}</a>

                                    @if (in_array($service->status, [\App\Enums\ServiceStatus::Draft, \App\Enums\ServiceStatus::Rejected], true))
                                        <form method="post" action="{{ route('freelancer.services.submit', $service) }}">
                                            @csrf
                                            <button type="submit" class="text-violet-600 hover:text-violet-700">{{ __('Ajukan Review') }}</button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('freelancer.services.withdraw', $service) }}"
                                              onsubmit="return confirm(@js(__('Tarik layanan ini menjadi draf?')))">
                                            @csrf
                                            <button type="submit" class="text-amber-700 hover:text-amber-800">{{ __('Tarik') }}</button>
                                        </form>
                                    @endif

                                    @if ($service->isDeletable())
                                        <form method="post" action="{{ route('freelancer.services.destroy', $service) }}"
                                              onsubmit="return confirm(@js(__('Hapus layanan ":title"? Tindakan ini tidak dapat dibatalkan.', ['title' => $service->title])))">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="text-red-600 hover:text-red-700">{{ __('Hapus') }}</button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
