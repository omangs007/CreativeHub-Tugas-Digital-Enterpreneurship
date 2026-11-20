<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pendapatan') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <p class="mx-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900 sm:mx-0">
                {{ __('Pendapatan di halaman ini adalah pencatatan simulasi. Dana pesanan tertahan (escrow) sampai customer menyetujui hasil, lalu masuk saldo tersedia. Komisi platform dipotong saat penarikan saldo.') }}
                <a href="{{ route('freelancer.wallet') }}" class="font-semibold underline">{{ __('Buka Saldo Saya') }}</a>
            </p>

            <div class="grid grid-cols-1 gap-4 px-4 sm:grid-cols-3 sm:px-0">
                <x-stat-card :label="__('Pendapatan selesai')" :value="\App\Support\Rupiah::format($summary['completed'])" :hint="__('dari pesanan yang disetujui customer')" />
                <x-stat-card :label="__('Dalam pengerjaan')" :value="\App\Support\Rupiah::format($summary['in_progress'])" :hint="__('sudah dibayar customer, dana masih tertahan')" />
                <x-stat-card :label="__('Saldo tersedia')" :value="\App\Support\Rupiah::format($summary['available'])" :hint="__('siap ditarik, komisi dipotong saat penarikan')" :href="route('freelancer.wallet')" />
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
                                <th class="px-4 py-3">{{ __('Dana') }}</th>
                                <th class="px-4 py-3 text-right">{{ __('Nilai') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach ($orders as $order)
                                @php($escrow = $order->escrowState())
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('orders.show', $order) }}" class="font-semibold text-gray-900 hover:text-violet-700">{{ $order->title }}</a>
                                        <p class="font-mono text-xs text-gray-500">{{ $order->order_number }} · {{ $order->customer->name }}</p>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $order->paid_at?->translatedFormat('d M Y') ?? '-' }}</td>
                                    <td class="px-4 py-3"><x-status-badge :status="$order->status" /></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-xs font-semibold {{ match ($escrow) { 'released' => 'text-emerald-700', 'held' => 'text-amber-700', default => 'text-gray-400' } }}">
                                        {{ match ($escrow) { 'released' => __('Saldo tersedia'), 'held' => __('Tertahan'), default => '-' } }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">@rupiah($order->total_amount)</td>
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
