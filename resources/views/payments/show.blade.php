<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-gray-500">{{ $payment->transaction_code }}</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Detail Pembayaran') }}</h2>
            </div>
            <x-status-badge :status="$payment->status" class="text-sm" />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-demo-payment-banner class="mx-4 sm:mx-0" />

            @if (session('status'))
                <p @class([
                    'mx-4 rounded-md p-3 text-sm sm:mx-0',
                    'bg-green-50 text-green-700' => session('status') === 'payment-success',
                    'bg-sky-50 text-sky-800' => session('status') === 'payment-pending',
                    'bg-red-50 text-red-700' => in_array(session('status'), ['payment-failed', 'payment-expired', 'payment-cancelled'], true),
                ])>
                    {{ match (session('status')) {
                        'payment-success' => __('Pembayaran simulasi berhasil. Pesanan dibayar dan mulai dikerjakan.'),
                        'payment-pending' => __('Pembayaran masih menunggu. Simulasikan lagi untuk menyelesaikan.'),
                        'payment-failed' => __('Pembayaran simulasi gagal. Pesanan tetap menunggu pembayaran; Anda dapat mencoba lagi.'),
                        default => __('Status pembayaran diperbarui.'),
                    } }}
                </p>
            @endif
            <x-input-error class="mx-4 sm:mx-0" :messages="$errors->get('payment')" />

            <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg text-sm">
                <dl class="grid gap-3 sm:grid-cols-2">
                    <div><dt class="text-gray-500">{{ __('Pesanan') }}</dt><dd class="font-semibold"><a href="{{ route('orders.show', $payment->order) }}" class="hover:text-violet-700">{{ $payment->order->order_number }}</a></dd></div>
                    <div><dt class="text-gray-500">{{ __('Metode') }}</dt><dd class="font-semibold">{{ $payment->method->getLabel() }}</dd></div>
                    <div><dt class="text-gray-500">{{ __('Nominal') }}</dt><dd class="text-xl font-extrabold">@rupiah($payment->amount)</dd></div>
                    <div><dt class="text-gray-500">{{ __('Dibuat') }}</dt><dd>{{ $payment->created_at->translatedFormat('d F Y H:i') }}</dd></div>
                    @if ($payment->paid_at)
                        <div><dt class="text-gray-500">{{ __('Dibayar') }}</dt><dd>{{ $payment->paid_at->translatedFormat('d F Y H:i') }}</dd></div>
                    @elseif ($payment->status === \App\Enums\PaymentStatus::Pending && $payment->expires_at)
                        <div><dt class="text-gray-500">{{ __('Berlaku hingga') }}</dt><dd>{{ $payment->expires_at->translatedFormat('d F Y H:i') }}</dd></div>
                    @endif
                </dl>
            </section>

            @if ($payment->status === \App\Enums\PaymentStatus::Pending)
                <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                    <h3 class="font-semibold text-gray-900">{{ __('Instruksi pembayaran (dummy)') }}</h3>
                    @switch($payment->method)
                        @case(\App\Enums\PaymentMethod::Qris)
                            <div class="mt-4 flex flex-col items-center gap-3 rounded-lg border-2 border-dashed border-gray-300 p-6 text-center">
                                <div class="grid size-40 grid-cols-8 gap-0.5 rounded bg-white p-2 ring-1 ring-gray-200" aria-hidden="true">
                                    @foreach (str_split(substr(md5($payment->transaction_code), 0, 32).substr(sha1($payment->transaction_code), 0, 32)) as $char)
                                        <span @class(['rounded-[1px]', 'bg-gray-900' => hexdec($char) % 2 === 0, 'bg-white' => hexdec($char) % 2 === 1])></span>
                                    @endforeach
                                </div>
                                <p class="font-mono text-xs text-gray-500">{{ $payment->provider_reference }}</p>
                                <p class="text-xs text-gray-500">{{ __('Kode QR ini hanya ilustrasi dan tidak dapat dipindai.') }}</p>
                            </div>
                            @break
                        @case(\App\Enums\PaymentMethod::VirtualAccount)
                            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                                <p class="text-sm text-gray-500">{{ __('Nomor Virtual Account (dummy)') }}</p>
                                <p class="font-mono text-2xl font-bold tracking-wider">{{ $payment->provider_reference }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ __('Nomor ini tidak terdaftar di bank mana pun.') }}</p>
                            </div>
                            @break
                        @case(\App\Enums\PaymentMethod::Ewallet)
                            <div class="mt-4 rounded-lg bg-gray-50 p-4">
                                <p class="text-sm text-gray-500">{{ __('Referensi e-wallet (dummy)') }}</p>
                                <p class="font-mono text-2xl font-bold tracking-wider">{{ $payment->provider_reference }}</p>
                            </div>
                            @break
                    @endswitch

                    @if ($canSimulate)
                        <div class="mt-6 border-t border-gray-100 pt-6">
                            <h4 class="font-semibold text-gray-900">{{ __('Simulasikan hasil pembayaran') }}</h4>
                            <p class="mt-1 text-sm text-gray-500">{{ __('Hasil diproses di server dan tercatat di database.') }}</p>
                            <div class="mt-4 flex flex-wrap gap-3">
                                @foreach ([
                                    'success' => ['label' => __('Simulasikan Berhasil'), 'class' => 'bg-emerald-600 hover:bg-emerald-500 text-white'],
                                    'pending' => ['label' => __('Simulasikan Pending'), 'class' => 'bg-sky-600 hover:bg-sky-500 text-white'],
                                    'failed' => ['label' => __('Simulasikan Gagal'), 'class' => 'bg-red-600 hover:bg-red-500 text-white'],
                                ] as $result => $button)
                                    <form method="post" action="{{ route('payments.simulate', $payment) }}">
                                        @csrf
                                        <input type="hidden" name="result" value="{{ $result }}">
                                        <button type="submit" class="rounded-md px-4 py-2 text-sm font-semibold {{ $button['class'] }}">{{ $button['label'] }}</button>
                                    </form>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </section>
            @elseif (in_array($payment->status, [\App\Enums\PaymentStatus::Failed, \App\Enums\PaymentStatus::Expired, \App\Enums\PaymentStatus::Cancelled], true)
                && $payment->order->status === \App\Enums\OrderStatus::PendingPayment
                && $payment->customer_id === auth()->id())
                <a href="{{ route('payments.checkout', $payment->order) }}" class="mx-4 inline-flex rounded-md bg-violet-600 px-4 py-2 text-sm font-semibold text-white hover:bg-violet-500 sm:mx-0">
                    {{ __('Coba bayar lagi') }}
                </a>
            @endif

            <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                <h3 class="font-semibold text-gray-900">{{ __('Log transaksi') }}</h3>
                <ol class="mt-3 space-y-2 text-sm">
                    @foreach ($payment->events as $event)
                        <li class="flex flex-wrap justify-between gap-2">
                            <span class="font-mono text-xs text-gray-700">{{ $event->event }}{{ $event->to_status ? ' → '.$event->to_status : '' }}</span>
                            <span class="text-gray-500">{{ $event->created_at->translatedFormat('d F Y H:i:s') }}</span>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>
    </div>
</x-app-layout>
