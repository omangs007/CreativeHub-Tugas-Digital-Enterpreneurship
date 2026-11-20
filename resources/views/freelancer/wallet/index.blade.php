<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Saldo Saya') }}</h2>
    </x-slot>

    @php($rate = \App\Filament\Pages\CommissionSettings::formatPercent($example['rate_bps']))

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="mx-4 rounded-md bg-green-50 p-3 text-sm text-green-700 sm:mx-0" role="status">{{ session('status') }}</p>
            @endif
            <x-input-error class="mx-4 sm:mx-0" :messages="$errors->get('withdrawal')" />

            <div class="grid grid-cols-1 gap-4 px-4 sm:grid-cols-2 sm:px-0 lg:grid-cols-4">
                <x-stat-card :label="__('Saldo tersedia')" :value="\App\Support\Rupiah::format($wallet->available_balance)" :hint="__('dari pesanan yang sudah disetujui customer, dapat ditarik')" class="ring-emerald-200" />
                <x-stat-card :label="__('Saldo tertahan (escrow)')" :value="\App\Support\Rupiah::format($wallet->held_balance)" :hint="trans_choice('{0} tidak ada pesanan dalam proses|[1,*] dari :count pesanan yang belum disetujui', $heldOrders->count(), ['count' => $heldOrders->count()])" />
                <x-stat-card :label="__('Penarikan dalam proses')" :value="\App\Support\Rupiah::format($totals['open_amount'])" :hint="__('menunggu / sedang ditransfer')" />
                <x-stat-card :label="__('Total diterima')" :value="\App\Support\Rupiah::format($totals['withdrawn_net'])" :hint="__('komisi platform dibayar :amount', ['amount' => \App\Support\Rupiah::format($totals['commission_paid'])])" />
            </div>

            <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_24rem]">
                <div class="min-w-0 space-y-6">
                    <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg" aria-labelledby="held-heading">
                        <h3 id="held-heading" class="font-semibold text-gray-900">{{ __('Dana tertahan per pesanan') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('Dana tetap tertahan selama pesanan dikerjakan atau direvisi, dan pindah ke saldo tersedia setelah customer menyetujui hasil.') }}</p>
                        @forelse ($heldOrders as $order)
                            <a href="{{ route('orders.show', $order) }}" class="mt-3 flex items-center justify-between gap-3 rounded-md p-2 hover:bg-gray-50">
                                <span class="min-w-0">
                                    <span class="block truncate text-sm font-semibold text-gray-900">{{ $order->title }}</span>
                                    <span class="text-xs text-gray-500">{{ $order->order_number }} · {{ $order->customer->name }}</span>
                                </span>
                                <span class="flex shrink-0 items-center gap-2 text-sm">
                                    <x-status-badge :status="$order->status" />
                                    <span class="font-semibold">@rupiah($order->total_amount)</span>
                                </span>
                            </a>
                        @empty
                            <p class="mt-3 text-sm text-gray-500">{{ __('Tidak ada dana tertahan saat ini.') }}</p>
                        @endforelse
                    </section>

                    <section class="bg-white shadow-sm sm:rounded-lg" aria-labelledby="withdrawals-heading">
                        <h3 id="withdrawals-heading" class="px-4 pt-4 font-semibold text-gray-900 sm:px-6 sm:pt-6">{{ __('Riwayat penarikan') }}</h3>
                        @if ($withdrawals->isEmpty())
                            <p class="px-4 pb-4 pt-2 text-sm text-gray-500 sm:px-6 sm:pb-6">{{ __('Belum ada penarikan.') }}</p>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100 text-sm">
                                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3">{{ __('Penarikan') }}</th>
                                            <th class="px-4 py-3">{{ __('Status') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Nominal') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Komisi') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Diterima') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($withdrawals as $withdrawal)
                                            <tr>
                                                <td class="px-4 py-3">
                                                    <p class="font-mono text-xs font-semibold text-gray-900">{{ $withdrawal->reference }}</p>
                                                    <p class="text-xs text-gray-500">{{ $withdrawal->created_at->translatedFormat('d M Y H:i') }} · {{ $withdrawal->bank_name }} {{ $withdrawal->maskedAccountNumber() }}</p>
                                                    @if ($withdrawal->transfer_reference)
                                                        <p class="text-xs text-emerald-700">{{ __('Ref. transfer: :ref', ['ref' => $withdrawal->transfer_reference]) }}</p>
                                                    @endif
                                                    @if ($withdrawal->admin_note)
                                                        <p class="text-xs text-red-700">{{ $withdrawal->admin_note }}</p>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3"><x-status-badge :status="$withdrawal->status" /></td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap">@rupiah($withdrawal->amount)</td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap text-gray-500">- @rupiah($withdrawal->commission_amount)</td>
                                                <td class="px-4 py-3 text-right whitespace-nowrap font-semibold">@rupiah($withdrawal->net_amount)</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </section>

                    <section class="bg-white shadow-sm sm:rounded-lg" aria-labelledby="ledger-heading">
                        <h3 id="ledger-heading" class="px-4 pt-4 font-semibold text-gray-900 sm:px-6 sm:pt-6">{{ __('Riwayat transaksi saldo') }}</h3>
                        @if (! $transactions || $transactions->isEmpty())
                            <p class="px-4 pb-4 pt-2 text-sm text-gray-500 sm:px-6 sm:pb-6">{{ __('Belum ada transaksi. Dana tercatat setelah customer membayar pesanan Anda.') }}</p>
                        @else
                            <div class="mt-3 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-100 text-sm">
                                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                        <tr>
                                            <th class="px-4 py-3">{{ __('Waktu') }}</th>
                                            <th class="px-4 py-3">{{ __('Keterangan') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Tertahan') }}</th>
                                            <th class="px-4 py-3 text-right">{{ __('Tersedia') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($transactions as $tx)
                                            @php($held = match ($tx->type) { \App\Enums\WalletTransactionType::EscrowHold => 1, \App\Enums\WalletTransactionType::EscrowRelease => -1, default => 0 })
                                            @php($available = $tx->availableDirection())
                                            <tr>
                                                <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $tx->created_at->translatedFormat('d M Y H:i') }}</td>
                                                <td class="min-w-56 px-4 py-3">
                                                    <x-status-badge :status="$tx->type" class="whitespace-nowrap" />
                                                    <p class="mt-1 text-xs text-gray-600">{{ $tx->description }}</p>
                                                </td>
                                                <td @class(['px-4 py-3 text-right whitespace-nowrap', 'text-gray-400' => $held === 0])>
                                                    {{ $held === 0 ? '' : ($held > 0 ? '+' : '−').' '.\App\Support\Rupiah::format($tx->amount) }}
                                                    <span class="block text-xs text-gray-400">@rupiah($tx->held_after)</span>
                                                </td>
                                                <td @class(['px-4 py-3 text-right whitespace-nowrap', 'text-emerald-700 font-semibold' => $available > 0, 'text-gray-900' => $available < 0])>
                                                    {{ $available === 0 ? '' : ($available > 0 ? '+' : '−').' '.\App\Support\Rupiah::format($tx->amount) }}
                                                    <span class="block text-xs font-normal text-gray-400">@rupiah($tx->available_after)</span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <div class="px-4 py-3 sm:px-6">{{ $transactions->links() }}</div>
                        @endif
                    </section>
                </div>

                <aside>
                    <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg" aria-labelledby="withdraw-heading"
                             x-data="{ amount: @js((string) old('amount', $wallet->available_balance ?: '')), rate: {{ $example['rate_bps'] }},
                                       get value() { return parseInt(String(this.amount).replace(/\D/g, '')) || 0 },
                                       get commission() { return Math.floor(this.value * this.rate / 10000) },
                                       rp(n) { return 'Rp' + n.toLocaleString('id-ID') } }">
                        <h3 id="withdraw-heading" class="font-semibold text-gray-900">{{ __('Tarik saldo') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('Komisi platform :rate dipotong saat penarikan. Hanya saldo tersedia yang dapat ditarik.', ['rate' => $rate]) }}
                        </p>

                        @if ($wallet->available_balance < \App\Services\Wallet::minimumWithdrawal())
                            <p class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-900">
                                {{ __('Saldo tersedia belum mencapai minimal penarikan :min. Dana dari pesanan yang masih dikerjakan tetap tertahan sampai customer menyetujui hasil.', ['min' => \App\Support\Rupiah::format(\App\Services\Wallet::minimumWithdrawal())]) }}
                            </p>
                        @else
                            <form method="post" action="{{ route('freelancer.withdrawals.preview') }}" class="mt-4 space-y-4">
                                @csrf
                                <div>
                                    <x-input-label for="amount" :value="__('Nominal penarikan (Rp)')" />
                                    <x-text-input id="amount" name="amount" type="number" inputmode="numeric" class="mt-1 block w-full" required
                                        min="{{ \App\Services\Wallet::minimumWithdrawal() }}" max="{{ $wallet->available_balance }}" step="1" x-model="amount" />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Maksimal :max', ['max' => \App\Support\Rupiah::format($wallet->available_balance)]) }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                                </div>

                                <dl class="space-y-1 rounded-md bg-gray-50 p-3 text-sm" aria-live="polite">
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Nominal') }}</dt><dd x-text="rp(value)"></dd></div>
                                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Komisi platform (:rate)', ['rate' => $rate]) }}</dt><dd x-text="'- ' + rp(commission)"></dd></div>
                                    <div class="flex justify-between gap-4 border-t border-gray-200 pt-1 font-semibold"><dt>{{ __('Diterima') }}</dt><dd x-text="rp(value - commission)"></dd></div>
                                </dl>

                                <div>
                                    <x-input-label for="bank_name" :value="__('Bank / e-wallet')" />
                                    <x-text-input id="bank_name" name="bank_name" type="text" class="mt-1 block w-full" list="bank-options" maxlength="60" required
                                        :value="old('bank_name', $lastBank?->bank_name)" />
                                    <datalist id="bank-options">
                                        @foreach (['BCA', 'BRI', 'BNI', 'Mandiri', 'BSI', 'CIMB Niaga', 'Bank Jago', 'SeaBank', 'GoPay', 'OVO', 'DANA'] as $bank)
                                            <option value="{{ $bank }}">
                                        @endforeach
                                    </datalist>
                                    <x-input-error class="mt-2" :messages="$errors->get('bank_name')" />
                                </div>
                                <div>
                                    <x-input-label for="account_number" :value="__('Nomor rekening')" />
                                    <x-text-input id="account_number" name="account_number" type="text" inputmode="numeric" class="mt-1 block w-full" maxlength="34" required
                                        :value="old('account_number', $lastBank?->account_number)" autocomplete="off" />
                                    <x-input-error class="mt-2" :messages="$errors->get('account_number')" />
                                </div>
                                <div>
                                    <x-input-label for="account_holder" :value="__('Nama pemilik rekening')" />
                                    <x-text-input id="account_holder" name="account_holder" type="text" class="mt-1 block w-full" maxlength="100" required
                                        :value="old('account_holder', $lastBank?->account_holder ?? auth()->user()->name)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('account_holder')" />
                                </div>

                                <x-primary-button class="w-full justify-center">{{ __('Lanjut ke Konfirmasi') }}</x-primary-button>
                            </form>
                        @endif

                        <p class="mt-4 text-xs text-gray-500">
                            {{ __('Mode demo: transfer dikonfirmasi manual oleh admin, tidak ada uang sungguhan yang berpindah.') }}
                        </p>
                    </section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
