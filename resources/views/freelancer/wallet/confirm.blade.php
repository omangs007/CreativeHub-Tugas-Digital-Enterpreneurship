<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Konfirmasi Penarikan') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-xl mx-auto sm:px-6 lg:px-8">
            <section class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-gray-900">{{ __('Rincian penarikan') }}</h3>
                <p class="mt-1 text-sm text-gray-500">{{ __('Periksa kembali sebelum mengonfirmasi. Komisi platform hanya dipotong sekali, saat penarikan ini.') }}</p>

                <dl class="mt-6 space-y-2 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Nominal ditarik dari saldo tersedia') }}</dt><dd class="font-semibold">@rupiah($quote['amount'])</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Komisi platform (:rate)', ['rate' => \App\Filament\Pages\CommissionSettings::formatPercent($quote['rate_bps'])]) }}</dt><dd class="text-red-700">- @rupiah($quote['commission'])</dd></div>
                    <div class="flex justify-between gap-4 border-t border-gray-100 pt-3 text-base"><dt class="font-semibold">{{ __('Diterima di rekening') }}</dt><dd class="font-bold text-emerald-700">@rupiah($quote['net'])</dd></div>
                    <div class="flex justify-between gap-4 pt-2"><dt class="text-gray-500">{{ __('Sisa saldo tersedia') }}</dt><dd>@rupiah($wallet->available_balance - $quote['amount'])</dd></div>
                </dl>

                <dl class="mt-6 space-y-2 rounded-md bg-gray-50 p-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Bank / e-wallet') }}</dt><dd class="font-semibold">{{ $bank['bank_name'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Nomor rekening') }}</dt><dd class="font-mono">{{ $bank['account_number'] }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-gray-500">{{ __('Atas nama') }}</dt><dd class="font-semibold">{{ $bank['account_holder'] }}</dd></div>
                </dl>

                <form method="post" action="{{ route('freelancer.withdrawals.store') }}" class="mt-6 flex flex-wrap items-center gap-4"
                      x-data="{ sending: false }" x-on:submit="sending = true">
                    @csrf
                    <input type="hidden" name="confirm_token" value="{{ $token }}">
                    <input type="hidden" name="amount" value="{{ $quote['amount'] }}">
                    <input type="hidden" name="bank_name" value="{{ $bank['bank_name'] }}">
                    <input type="hidden" name="account_number" value="{{ $bank['account_number'] }}">
                    <input type="hidden" name="account_holder" value="{{ $bank['account_holder'] }}">
                    <x-primary-button x-bind:disabled="sending">{{ __('Konfirmasi Penarikan') }}</x-primary-button>
                    <a href="{{ route('freelancer.wallet') }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Batal') }}</a>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
