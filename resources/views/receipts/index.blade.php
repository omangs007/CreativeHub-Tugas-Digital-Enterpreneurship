<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Riwayat Pembayaran') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <x-demo-payment-banner class="mx-4 sm:mx-0" />

            @if ($payments->isEmpty())
                <x-empty-state :title="__('Belum ada transaksi')">
                    {{ __('Transaksi pembayaran pesanan Anda akan tampil di sini.') }}
                </x-empty-state>
            @else
                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Transaksi') }}</th>
                                <th class="px-4 py-3">{{ __('Pesanan') }}</th>
                                <th class="px-4 py-3">{{ __('Metode') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Nominal') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3"><span class="sr-only">{{ __('Aksi') }}</span></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($payments as $payment)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs text-violet-700 hover:underline">{{ $payment->transaction_code }}</a>
                                        <p class="text-xs text-gray-500">{{ $payment->created_at->translatedFormat('d F Y H:i') }}</p>
                                    </td>
                                    <td class="px-4 py-3">{{ $payment->order->title }}</td>
                                    <td class="px-4 py-3">{{ $payment->method->getLabel() }}</td>
                                    <td class="px-4 py-3 text-right font-semibold">@rupiah($payment->amount)</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$payment->status" /></td>
                                    <td class="px-4 py-3 text-right">
                                        @if ($payment->status === \App\Enums\PaymentStatus::Success)
                                            <a href="{{ route('receipts.show', $payment) }}" class="font-semibold text-violet-600 hover:text-violet-700">{{ __('Struk') }}</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div>{{ $payments->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
