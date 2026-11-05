<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Penawaran Saya') }}</h2>
            <a href="{{ route('projects.index') }}" class="text-sm font-semibold text-violet-600 hover:text-violet-700">{{ __('Cari proyek terbuka') }} &rarr;</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <p class="mx-4 rounded-md bg-green-50 p-3 text-sm text-green-700 sm:mx-0">
                    {{ match (session('status')) {
                        'proposal-sent' => __('Penawaran terkirim ke customer.'),
                        'proposal-updated' => __('Penawaran diperbarui.'),
                        'proposal-withdrawn' => __('Penawaran ditarik.'),
                        default => '',
                    } }}
                </p>
            @endif

            @if ($proposals->isEmpty())
                <x-empty-state :title="__('Belum ada penawaran')">
                    {{ __('Temukan proyek terbuka dan kirim penawaran terbaik Anda.') }}
                </x-empty-state>
            @else
                <ul class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @foreach ($proposals as $proposal)
                        <li class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <x-status-badge :status="$proposal->status" />
                                    <span class="text-xs text-gray-500">{{ $proposal->project->category->name }}</span>
                                </div>
                                <h3 class="mt-1 truncate font-semibold text-gray-900">{{ $proposal->project->title }}</h3>
                                <p class="mt-1 text-sm text-gray-500">
                                    @rupiah($proposal->price) · {{ __(':days hari', ['days' => $proposal->delivery_days]) }} · {{ __('dikirim :date', ['date' => $proposal->created_at->translatedFormat('d F Y')]) }}
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-sm font-semibold">
                                @if ($proposal->order)
                                    <a href="{{ route('orders.show', $proposal->order) }}" class="text-violet-600 hover:text-violet-700">{{ __('Lihat pesanan') }}</a>
                                @else
                                    <a href="{{ route('proposals.chat', $proposal) }}" class="text-violet-600 hover:text-violet-700">{{ __('Chat customer') }}</a>
                                @endif
                                @can('update', $proposal)
                                    <a href="{{ route('freelancer.proposals.edit', $proposal) }}" class="text-gray-700 hover:text-gray-900">{{ __('Edit') }}</a>
                                    <form method="post" action="{{ route('freelancer.proposals.withdraw', $proposal) }}"
                                          onsubmit="return confirm(@js(__('Tarik penawaran ini?')))">
                                        @csrf
                                        <button type="submit" class="text-red-600 hover:text-red-700">{{ __('Tarik') }}</button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</x-app-layout>
