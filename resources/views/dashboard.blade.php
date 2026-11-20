<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Halo, :name', ['name' => $user->name]) }}</h2>
                <p class="text-sm text-gray-500">{{ __('Ringkasan aktivitas Anda di CreativeHub.') }}</p>
            </div>
            @if ($user->isAdmin())
                <a href="{{ url('/admin') }}" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-semibold text-white hover:bg-gray-700">{{ __('Buka Panel Admin') }}</a>
            @endif
        </div>
    </x-slot>

    @php($os = \App\Enums\OrderStatus::class)

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-10">
            @if ($customer)
                <section class="space-y-4 px-4 sm:px-0" aria-labelledby="customer-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 id="customer-heading" class="text-lg font-bold text-gray-900">{{ __('Sebagai customer') }}</h3>
                        <div class="flex gap-2 text-sm font-semibold">
                            <a href="{{ route('services.index') }}" class="rounded-md bg-violet-600 px-3 py-1.5 text-white hover:bg-violet-500">{{ __('Cari Jasa') }}</a>
                            <a href="{{ route('customer.projects.create') }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-gray-700 hover:bg-gray-50">{{ __('Posting Proyek') }}</a>
                        </div>
                    </div>

                    @php($c = $customer['statusCounts'])
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <x-stat-card :label="__('Menunggu pembayaran')" :value="$c[$os::PendingPayment->value] ?? 0" :href="route('orders.index')" />
                        <x-stat-card :label="__('Sedang dikerjakan')" :value="($c[$os::Paid->value] ?? 0) + ($c[$os::InProgress->value] ?? 0) + ($c[$os::RevisionRequested->value] ?? 0)" :href="route('orders.index')" />
                        <x-stat-card :label="__('Perlu ditinjau')" :value="$c[$os::Delivered->value] ?? 0" :href="route('orders.index')" />
                        <x-stat-card :label="__('Total dibayar')" :value="\App\Support\Rupiah::format($customer['totalSpent'])" :hint="__('pembayaran simulasi berhasil')" :href="route('payments.index')" />
                    </div>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="min-w-0 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100">
                            <h4 class="font-semibold text-gray-900">{{ __('Perlu tindakan Anda') }}</h4>
                            @forelse ($customer['toPay']->concat($customer['toReview']) as $order)
                                <a href="{{ route('orders.show', $order) }}" class="mt-3 flex items-center justify-between gap-3 rounded-md p-2 hover:bg-gray-50">
                                    <span class="min-w-0">
                                        <span class="block truncate text-sm font-semibold text-gray-900">{{ $order->title }}</span>
                                        <span class="text-xs text-gray-500">{{ $order->status === $os::PendingPayment ? __('Bayar :total', ['total' => \App\Support\Rupiah::format($order->total_amount)]) : __('Tinjau hasil dari :name', ['name' => $order->freelancer->name]) }}</span>
                                    </span>
                                    <x-status-badge :status="$order->status" />
                                </a>
                            @empty
                                <p class="mt-3 text-sm text-gray-500">{{ __('Tidak ada yang perlu ditindaklanjuti.') }}</p>
                            @endforelse
                        </div>

                        <div class="min-w-0 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100">
                            <div class="flex items-center justify-between">
                                <h4 class="font-semibold text-gray-900">{{ __('Proyek terbuka') }}</h4>
                                <a href="{{ route('customer.projects.index') }}" class="text-sm font-semibold text-violet-600">{{ __('Semua') }}</a>
                            </div>
                            @forelse ($customer['openProjects'] as $project)
                                <a href="{{ route('customer.projects.show', $project) }}" class="mt-3 flex items-center justify-between gap-3 rounded-md p-2 hover:bg-gray-50">
                                    <span class="min-w-0 truncate text-sm font-semibold text-gray-900">{{ $project->title }}</span>
                                    <span class="shrink-0 text-xs font-semibold text-violet-700">{{ trans_choice('{0} Belum ada penawaran|[1,*] :count penawaran baru', $project->pending_proposals_count, ['count' => $project->pending_proposals_count]) }}</span>
                                </a>
                            @empty
                                <p class="mt-3 text-sm text-gray-500">{{ __('Tidak ada proyek terbuka.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    @if ($customer['recentPayments']->isNotEmpty())
                        <div class="min-w-0 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100">
                            <h4 class="font-semibold text-gray-900">{{ __('Pembayaran terbaru') }}</h4>
                            <ul class="mt-3 divide-y divide-gray-100 text-sm">
                                @foreach ($customer['recentPayments'] as $payment)
                                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                                        <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs text-violet-700 hover:underline">{{ $payment->transaction_code }}</a>
                                        <span class="flex-1 truncate text-gray-600">{{ $payment->order->title }}</span>
                                        <span class="font-semibold">@rupiah($payment->amount)</span>
                                        <x-status-badge :status="$payment->status" />
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endif

            @if ($freelancer)
                <section class="space-y-4 px-4 sm:px-0" aria-labelledby="freelancer-heading">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 id="freelancer-heading" class="text-lg font-bold text-gray-900">{{ __('Sebagai freelancer') }}</h3>
                        <div class="flex gap-2 text-sm font-semibold">
                            <a href="{{ route('projects.index') }}" class="rounded-md bg-violet-600 px-3 py-1.5 text-white hover:bg-violet-500">{{ __('Cari Proyek') }}</a>
                            <a href="{{ route('freelancer.services.create') }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-gray-700 hover:bg-gray-50">{{ __('Tambah Layanan') }}</a>
                        </div>
                    </div>

                    @php($e = $freelancer['earnings'])
                    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                        <x-stat-card :label="__('Saldo tersedia')" :value="\App\Support\Rupiah::format($e['available'])" :hint="trans_choice('{0} belum ada pesanan selesai|[1,*] dari :count pesanan selesai', $e['completed_count'], ['count' => $e['completed_count']])" :href="route('freelancer.wallet')" />
                        <x-stat-card :label="__('Saldo tertahan')" :value="\App\Support\Rupiah::format($e['held'])" :hint="__('escrow pesanan yang belum disetujui')" :href="route('freelancer.wallet')" />
                        <x-stat-card :label="__('Penawaran menunggu')" :value="$freelancer['pendingProposals']" :href="route('freelancer.proposals.index')" />
                        <x-stat-card :label="__('Layanan tayang')" :value="$freelancer['serviceCounts'][\App\Enums\ServiceStatus::Published->value] ?? 0"
                            :hint="__(':n menunggu review', ['n' => $freelancer['serviceCounts'][\App\Enums\ServiceStatus::PendingReview->value] ?? 0])" :href="route('freelancer.services.index')" />
                    </div>

                    <div class="min-w-0 rounded-lg bg-white p-4 shadow-sm ring-1 ring-gray-100">
                        <h4 class="font-semibold text-gray-900">{{ __('Pesanan yang perlu dikirim') }}</h4>
                        @forelse ($freelancer['toDeliver'] as $order)
                            <a href="{{ route('orders.show', $order) }}#pengerjaan" class="mt-3 flex items-center justify-between gap-3 rounded-md p-2 hover:bg-gray-50">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $order->title }}</span>
                                    <span class="text-xs text-gray-500">
                                        {{ __('Customer: :name', ['name' => $order->customer->name]) }}
                                        @if ($order->paid_at)
                                            · {{ __('target :date', ['date' => $order->paid_at->copy()->addDays($order->delivery_days)->translatedFormat('d F Y')]) }}
                                        @endif
                                    </span>
                                </span>
                                <x-status-badge :status="$order->status" />
                            </a>
                        @empty
                            <p class="mt-3 text-sm text-gray-500">{{ __('Tidak ada pesanan yang menunggu pengiriman.') }}</p>
                        @endforelse
                    </div>
                </section>
            @endif

            @if (! $customer && ! $freelancer)
                <div class="rounded-lg bg-white p-6 text-sm text-gray-600 shadow-sm">
                    {{ __('Akun ini tidak memiliki peran customer atau freelancer.') }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
