<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pesanan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if ($hasFreelancerOrders || $role === 'freelancer')
                <nav class="mx-4 flex gap-2 sm:mx-0" aria-label="{{ __('Jenis pesanan') }}">
                    @foreach (['customer' => __('Sebagai customer'), 'freelancer' => __('Sebagai freelancer')] as $key => $label)
                        <a href="{{ route('orders.index', $key === 'freelancer' ? ['sebagai' => 'freelancer'] : []) }}"
                           @class(['rounded-full px-4 py-2 text-sm font-semibold', 'bg-gray-900 text-white' => $role === $key, 'bg-white text-gray-600 ring-1 ring-gray-200' => $role !== $key])>{{ $label }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($orders->isEmpty())
                <x-empty-state :title="__('Belum ada pesanan')">
                    {{ $role === 'freelancer' ? __('Pesanan dari customer akan tampil di sini.') : __('Pesan paket jasa atau terima penawaran di proyek Anda.') }}
                </x-empty-state>
            @else
                <ul class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @foreach ($orders as $order)
                        <li>
                            <a href="{{ route('orders.show', $order) }}" class="flex flex-col gap-2 p-4 hover:bg-gray-50 sm:flex-row sm:items-center sm:justify-between sm:p-6">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <x-status-badge :status="$order->status" />
                                        <span class="font-mono text-xs text-gray-500">{{ $order->order_number }}</span>
                                    </div>
                                    <h3 class="mt-1 truncate font-semibold text-gray-900">{{ $order->title }}</h3>
                                    <p class="text-sm text-gray-500">
                                        {{ $role === 'freelancer' ? __('Customer: :name', ['name' => $order->customer->name]) : __('Freelancer: :name', ['name' => $order->freelancer->name]) }}
                                    </p>
                                </div>
                                <div class="text-sm sm:text-right">
                                    <p class="font-bold text-gray-900">@rupiah($order->total_amount)</p>
                                    <p class="text-gray-500">{{ $order->created_at->translatedFormat('d F Y') }}</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div>{{ $orders->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
