<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pendapatan') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="mx-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900 sm:mx-0">
                {{ __('Pendapatan dan komisi di halaman ini adalah pencatatan simulasi. CreativeHub tidak melakukan payout atau escrow uang sungguhan.') }}
            </p>

            <div class="grid grid-cols-1 gap-4 px-4 sm:grid-cols-3 sm:px-0">
                <x-stat-card :label="__('Pendapatan selesai')" :value="\App\Support\Rupiah::format($summary['completed'])" :hint="__('bagian Anda dari pesanan selesai')" />
                <x-stat-card :label="__('Dalam pengerjaan')" :value="\App\Support\Rupiah::format($summary['in_progress'])" :hint="__('sudah dibayar customer, belum selesai')" />
                <x-stat-card :label="__('Komisi marketplace')" :value="\App\Support\Rupiah::format($summary['commission'])" :hint="__('dari seluruh pesanan berbayar')" />
            </div>

            @if ($orders->isEmpty())
                <x-empty-state :title="__('Belum ada pesanan berbayar')">
                    {{ __('Pendapatan tercatat setelah customer membayar pesanan Anda.') }}
                </x-empty-state>
            @else
                <div class="overflow-x-auto bg-white shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-100 text-sm">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-4 py-3">{{ __('Pesanan') }}</th>
                                <th class="px-4 py-3">{{ __('Dibayar') }}</th>
                                <th class="px-4 py-3">{{ __('Status') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Nilai') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Komisi') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Bagian Anda') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('orders.show', $order) }}" class="font-semibold text-gray-900 hover:text-violet-700">{{ $order->title }}</a>
                                        <p class="font-mono text-xs text-gray-500">{{ $order->order_number }} · {{ $order->customer->name }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $order->paid_at?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$order->status" /></td>
                                    <td class="px-4 py-3 text-right">@rupiah($order->total_amount)</td>
                                    <td class="px-4 py-3 text-right text-gray-500">- @rupiah($order->commission_amount)</td>
                                    <td class="px-4 py-3 text-right font-semibold">@rupiah($order->freelancer_amount)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div>{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
