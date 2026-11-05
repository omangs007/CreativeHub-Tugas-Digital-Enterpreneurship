<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pembayaran Pesanan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-demo-payment-banner class="mx-4 sm:mx-0" />
            <x-input-error class="mx-4 sm:mx-0" :messages="$errors->get('payment')" />

            <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="font-mono text-xs text-gray-500">{{ $order->order_number }}</p>
                        <h3 class="font-semibold text-gray-900">{{ $order->title }}</h3>
                        @if ($order->package_name)
                            <p class="text-sm text-gray-500">{{ __('Paket :name', ['name' => $order->package_name]) }}</p>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">{{ __('Total tagihan') }}</p>
                        <p class="text-2xl font-extrabold text-gray-900">@rupiah($order->total_amount)</p>
                    </div>
                </div>
            </section>

            @if ($pendingPayment)
                <p class="mx-4 rounded-md bg-sky-50 p-3 text-sm text-sky-800 sm:mx-0">
                    {{ __('Anda memiliki tagihan :code yang masih menunggu.', ['code' => $pendingPayment->transaction_code]) }}
                    <a href="{{ route('payments.show', $pendingPayment) }}" class="font-semibold underline">{{ __('Lanjutkan tagihan ini') }}</a>
                    {{ __('atau pilih metode lain di bawah (tagihan lama dibatalkan).') }}
                </p>
            @endif

            <form method="post" action="{{ route('payments.start', $order) }}" class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                @csrf
                <fieldset>
                    <legend class="font-semibold text-gray-900">{{ __('Pilih metode pembayaran simulasi') }}</legend>
                    <div class="mt-4 space-y-3">
                        @foreach ($methods as $method)
                            <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 p-4 has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50">
                                <input type="radio" name="method" value="{{ $method->value }}" @checked(old('method', 'qris') === $method->value) class="mt-1 text-violet-600 focus:ring-violet-500">
                                <span>
                                    <span class="block font-semibold text-gray-900">{{ $method->getLabel() }}</span>
                                    <span class="block text-sm text-gray-500">{{ $method->description() }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <x-input-error class="mt-2" :messages="$errors->get('method')" />
                </fieldset>

                <div class="mt-6 flex items-center gap-4">
                    <x-primary-button>{{ __('Lanjutkan ke Pembayaran') }}</x-primary-button>
                    <a href="{{ route('orders.show', $order) }}" class="text-sm text-gray-600 hover:text-gray-900">{{ __('Kembali ke pesanan') }}</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
