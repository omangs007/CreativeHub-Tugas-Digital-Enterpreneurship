<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-gray-500">{{ $order->order_number }}</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->title }}</h2>
            </div>
            <x-status-badge :status="$order->status" class="text-sm" />
        </div>
    </x-slot>

    @php($isCustomer = $order->customer_id === auth()->id())

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status') === 'proposal-accepted')
                <p class="mx-4 rounded-md bg-green-50 p-3 text-sm text-green-700 sm:mx-0">{{ __('Penawaran diterima. Pesanan dibuat dan menunggu pembayaran.') }}</p>
            @endif
            <x-input-error class="mx-4 sm:mx-0" :messages="$errors->get('order')" />

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-6">
                    <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                        <h3 class="font-semibold text-gray-900">{{ __('Ruang lingkup pekerjaan') }}</h3>
                        @if ($order->package_name)
                            <p class="mt-1 text-sm text-gray-500">{{ __('Paket :name', ['name' => $order->package_name]) }}</p>
                        @endif
                        <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ $order->scope_description }}</p>
                        <p class="mt-4 text-xs text-gray-400">{{ __('Snapshot saat pesanan dibuat; tidak berubah jika layanan atau penawaran diedit.') }}</p>
                    </section>

                    <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                        <h3 class="font-semibold text-gray-900">{{ __('Riwayat status') }}</h3>
                        <ol class="mt-4 space-y-3 border-l-2 border-gray-100 pl-4">
                            @foreach ($order->statusHistories as $history)
                                <li class="text-sm">
                                    <p class="font-semibold text-gray-900">{{ $history->to_status->getLabel() }}</p>
                                    <p class="text-gray-500">
                                        {{ $history->created_at->translatedFormat('d F Y H:i') }}
                                        @if ($history->changedBy) · {{ $history->changedBy->name }} @endif
                                    </p>
                                    @if ($history->note)
                                        <p class="text-gray-600">{{ $history->note }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </section>
                </div>

                <aside class="space-y-6">
                    <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg text-sm">
                        <h3 class="font-semibold text-gray-900">{{ __('Rincian') }}</h3>
                        <dl class="mt-4 space-y-2">
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Customer') }}</dt><dd class="text-right font-semibold">{{ $order->customer->name }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Freelancer') }}</dt><dd class="text-right font-semibold">{{ $order->freelancer->name }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Pengerjaan') }}</dt><dd class="text-right">{{ __(':days hari', ['days' => $order->delivery_days]) }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Revisi') }}</dt><dd class="text-right">{{ $order->revisions_used }} / {{ $order->revisions_allowed }}</dd></div>
                            <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Dibuat') }}</dt><dd class="text-right">{{ $order->created_at->translatedFormat('d F Y') }}</dd></div>
                        </dl>
                        <dl class="mt-4 space-y-2 border-t border-gray-100 pt-4">
                            @if ($isCustomer)
                                <div class="flex justify-between gap-4 text-base"><dt class="font-semibold">{{ __('Total') }}</dt><dd class="font-bold">@rupiah($order->total_amount)</dd></div>
                            @else
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Nilai pesanan') }}</dt><dd>@rupiah($order->total_amount)</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Komisi marketplace') }}</dt><dd>- @rupiah($order->commission_amount)</dd></div>
                                <div class="flex justify-between gap-4 text-base"><dt class="font-semibold">{{ __('Bagian Anda') }}</dt><dd class="font-bold">@rupiah($order->freelancer_amount)</dd></div>
                            @endif
                        </dl>
                    </section>

                    @if ($order->status === \App\Enums\OrderStatus::PendingPayment)
                        <section class="p-4 sm:p-6 bg-amber-50 shadow-sm sm:rounded-lg text-sm text-amber-900">
                            <h3 class="font-semibold">{{ __('Menunggu pembayaran') }}</h3>
                            <p class="mt-1">
                                {{ $isCustomer ? __('Lakukan pembayaran agar freelancer dapat mulai bekerja.') : __('Pekerjaan dimulai setelah customer membayar.') }}
                            </p>
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
