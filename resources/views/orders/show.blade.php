<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="font-mono text-xs text-gray-500">{{ $order->order_number }}</p>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $order->title }}</h2>
            </div>
            <div class="flex items-center gap-3">
                @if ($order->involves(auth()->user()))
                    <a href="{{ route('orders.chat', $order) }}" class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        {{ __('Chat dengan :name', ['name' => $order->customer_id === auth()->id() ? $order->freelancer->name : $order->customer->name]) }}
                    </a>
                @endif
                <x-status-badge :status="$order->status" class="text-sm" />
            </div>
        </div>
    </x-slot>

    @php($isCustomer = $order->customer_id === auth()->id())

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if (session('status'))
                <p class="mx-4 rounded-md bg-green-50 p-3 text-sm text-green-700 sm:mx-0">
                    {{ match (session('status')) {
                        'proposal-accepted' => __('Penawaran diterima. Pesanan dibuat dan menunggu pembayaran.'),
                        'order-created' => __('Pesanan dibuat dan menunggu pembayaran.'),
                        'order-delivered' => __('Hasil pekerjaan terkirim ke customer.'),
                        'revision-requested' => __('Permintaan revisi terkirim ke freelancer.'),
                        'order-completed' => __('Pesanan selesai. Terima kasih! Jangan lupa beri ulasan.'),
                        'review-created' => __('Terima kasih atas ulasan Anda.'),
                        'order-cancelled' => __('Pesanan dibatalkan.'),
                        default => '',
                    } }}
                </p>
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

                    @php($timeline = $order->deliveries->map(fn ($d) => ['type' => 'delivery', 'at' => $d->created_at, 'item' => $d])
                        ->concat($order->revisions->map(fn ($r) => ['type' => 'revision', 'at' => $r->created_at, 'item' => $r]))
                        ->sortBy([['at', 'asc']])->values())

                    @if ($timeline->isNotEmpty() || Gate::allows('deliver', $order) || Gate::allows('review', $order))
                        <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg" id="pengerjaan">
                            <h3 class="font-semibold text-gray-900">{{ __('Hasil & revisi') }}</h3>

                            @foreach ($timeline as $entry)
                                @if ($entry['type'] === 'delivery')
                                    <article class="mt-4 rounded-lg border border-violet-200 bg-violet-50/50 p-4 text-sm">
                                        <p class="font-semibold text-violet-900">{{ __('Hasil dikirim oleh :name', ['name' => $entry['item']->freelancer->name]) }}</p>
                                        <p class="text-xs text-gray-500">{{ $entry['at']->translatedFormat('d F Y H:i') }}</p>
                                        <p class="mt-2 whitespace-pre-line text-gray-700">{{ $entry['item']->message }}</p>
                                        <div class="mt-3 flex flex-wrap gap-3">
                                            @if ($entry['item']->hasFile())
                                                <a href="{{ route('deliveries.download', $entry['item']) }}" class="inline-flex items-center gap-1 rounded-md bg-white px-3 py-1.5 font-semibold text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100">
                                                    {{ __('Unduh :name', ['name' => $entry['item']->file_name]) }}
                                                    <span class="text-xs font-normal text-gray-500">({{ number_format($entry['item']->file_size / 1024, 0, ',', '.') }} KB)</span>
                                                </a>
                                            @endif
                                            @if ($entry['item']->link_url)
                                                <a href="{{ $entry['item']->link_url }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center rounded-md bg-white px-3 py-1.5 font-semibold text-violet-700 ring-1 ring-violet-200 hover:bg-violet-100">{{ __('Buka tautan hasil') }} &nearr;</a>
                                            @endif
                                        </div>
                                    </article>
                                @else
                                    <article class="mt-4 rounded-lg border border-amber-200 bg-amber-50/60 p-4 text-sm">
                                        <p class="font-semibold text-amber-900">{{ __('Revisi diminta oleh :name', ['name' => $entry['item']->requester->name]) }}</p>
                                        <p class="text-xs text-gray-500">{{ $entry['at']->translatedFormat('d F Y H:i') }}</p>
                                        <p class="mt-2 whitespace-pre-line text-gray-700">{{ $entry['item']->reason }}</p>
                                    </article>
                                @endif
                            @endforeach

                            @can('deliver', $order)
                                <form method="post" action="{{ route('orders.deliver', $order) }}" enctype="multipart/form-data" class="mt-6 space-y-4 border-t border-gray-100 pt-6">
                                    @csrf
                                    <h4 class="font-semibold text-gray-900">{{ $order->status === \App\Enums\OrderStatus::RevisionRequested ? __('Kirim hasil revisi') : __('Kirim hasil pekerjaan') }}</h4>
                                    <div>
                                        <x-input-label for="message" :value="__('Catatan untuk customer')" />
                                        <textarea id="message" name="message" rows="4" required minlength="10" maxlength="3000" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">{{ old('message') }}</textarea>
                                        <x-input-error class="mt-1" :messages="$errors->get('message')" />
                                    </div>
                                    <div>
                                        <x-input-label for="file" :value="__('File hasil')" />
                                        <input id="file" name="file" type="file" accept=".{{ implode(',.', config('creativehub.delivery_extensions')) }}"
                                            class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-gray-700">
                                        <p class="mt-1 text-xs text-gray-500">
                                            {{ __('Format: :ext. Maksimal :size MB. File besar? Gunakan tautan di bawah.', ['ext' => implode(', ', config('creativehub.delivery_extensions')), 'size' => rtrim(rtrim(number_format(config('creativehub.delivery_max_kb') / 1024, 1, ',', '.'), '0'), ',')]) }}
                                        </p>
                                        <x-input-error class="mt-1" :messages="$errors->get('file')" />
                                    </div>
                                    <div>
                                        <x-input-label for="link_url" :value="__('Tautan hasil (opsional jika ada file)')" />
                                        <x-text-input id="link_url" name="link_url" type="url" class="mt-1 block w-full" maxlength="500" :value="old('link_url')" placeholder="https://drive.google.com/..." />
                                        <x-input-error class="mt-1" :messages="$errors->get('link_url')" />
                                    </div>
                                    <x-primary-button>{{ __('Kirim Hasil') }}</x-primary-button>
                                </form>
                            @endcan

                            @can('review', $order)
                                <div class="mt-6 space-y-4 border-t border-gray-100 pt-6">
                                    <h4 class="font-semibold text-gray-900">{{ __('Tinjau hasil') }}</h4>
                                    <form method="post" action="{{ route('orders.complete', $order) }}"
                                          onsubmit="return confirm(@js(__('Selesaikan pesanan? Setelah selesai, revisi tidak dapat diminta lagi.')))">
                                        @csrf
                                        <button type="submit" class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">{{ __('Terima Hasil & Selesaikan') }}</button>
                                    </form>

                                    @if ($order->revisionsRemaining() > 0)
                                        <form method="post" action="{{ route('orders.revisions.store', $order) }}" class="space-y-2">
                                            @csrf
                                            <x-input-label for="reason" :value="__('Minta revisi (sisa :n kali)', ['n' => $order->revisionsRemaining()])" />
                                            <textarea id="reason" name="reason" rows="3" required minlength="10" maxlength="2000" class="block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500" placeholder="{{ __('Jelaskan bagian yang perlu diperbaiki.') }}">{{ old('reason') }}</textarea>
                                            <x-input-error :messages="$errors->get('reason')" />
                                            <button type="submit" class="rounded-md border border-amber-400 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 hover:bg-amber-100">{{ __('Minta Revisi') }}</button>
                                        </form>
                                    @else
                                        <p class="text-sm text-gray-500">{{ __('Jatah revisi sudah habis.') }}</p>
                                    @endif
                                </div>
                            @endcan
                        </section>
                    @endif

                    @if ($order->review)
                        <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg">
                            <h3 class="font-semibold text-gray-900">{{ __('Ulasan customer') }}</h3>
                            <x-star-rating :rating="$order->review->rating" class="mt-2" />
                            @if ($order->review->comment)
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $order->review->comment }}</p>
                            @endif
                            @unless ($order->review->is_visible)
                                <p class="mt-2 text-xs text-amber-700">{{ __('Ulasan ini disembunyikan oleh admin.') }}</p>
                            @endunless
                        </section>
                    @elseif (Gate::allows('reviewOrder', $order))
                        <form method="post" action="{{ route('orders.review', $order) }}" class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg space-y-4" x-data="{ rating: {{ (int) old('rating', 5) }} }">
                            @csrf
                            <h3 class="font-semibold text-gray-900">{{ __('Beri ulasan') }}</h3>
                            <fieldset>
                                <legend class="text-sm text-gray-600">{{ __('Rating untuk :name', ['name' => $order->freelancer->name]) }}</legend>
                                <div class="mt-2 flex gap-1">
                                    @foreach (range(1, 5) as $star)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="rating" value="{{ $star }}" x-model.number="rating" class="sr-only">
                                            <span class="text-3xl leading-none" :class="rating >= {{ $star }} ? 'text-amber-400' : 'text-gray-300'" aria-hidden="true">&#9733;</span>
                                            <span class="sr-only">{{ __(':n bintang', ['n' => $star]) }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-1" :messages="$errors->get('rating')" />
                            </fieldset>
                            <div>
                                <x-input-label for="comment" :value="__('Komentar (opsional)')" />
                                <textarea id="comment" name="comment" rows="3" maxlength="1000" class="mt-1 block w-full rounded-md border-gray-300 shadow-xs focus:border-indigo-500 focus:ring-indigo-500">{{ old('comment') }}</textarea>
                                <x-input-error class="mt-1" :messages="$errors->get('comment')" />
                            </div>
                            <x-primary-button>{{ __('Kirim Ulasan') }}</x-primary-button>
                        </form>
                    @endif

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
                                @php($escrow = $order->escrowState())
                                <div class="flex justify-between gap-4 text-base"><dt class="font-semibold">{{ __('Nilai pesanan') }}</dt><dd class="font-bold">@rupiah($order->total_amount)</dd></div>
                                <div class="flex justify-between gap-4">
                                    <dt class="text-gray-500">{{ __('Status dana') }}</dt>
                                    <dd class="text-right font-semibold {{ match ($escrow) { 'released' => 'text-emerald-700', 'held' => 'text-amber-700', default => 'text-gray-500' } }}">
                                        {{ match ($escrow) { 'released' => __('Masuk saldo tersedia'), 'held' => __('Tertahan (escrow)'), default => __('Belum dibayar') } }}
                                    </dd>
                                </div>
                                <p class="text-xs text-gray-500">
                                    {{ __('Dana masuk saldo tersedia setelah customer menyetujui hasil. Komisi platform :rate dipotong saat penarikan saldo.', ['rate' => \App\Filament\Pages\CommissionSettings::formatPercent(\App\Support\CommissionCalculator::currentRateBps())]) }}
                                    @if ($order->freelancer_id === auth()->id())
                                        <a href="{{ route('freelancer.wallet') }}" class="font-semibold text-violet-700 hover:underline">{{ __('Lihat saldo') }}</a>
                                    @endif
                                </p>
                            @endif
                        </dl>
                    </section>

                    @if ($order->status === \App\Enums\OrderStatus::PendingPayment)
                        <section class="p-4 sm:p-6 bg-amber-50 shadow-sm sm:rounded-lg text-sm text-amber-900">
                            <h3 class="font-semibold">{{ __('Menunggu pembayaran') }}</h3>
                            <p class="mt-1">
                                {{ $isCustomer ? __('Lakukan pembayaran agar freelancer dapat mulai bekerja.') : __('Pekerjaan dimulai setelah customer membayar.') }}
                            </p>
                            @can('pay', $order)
                                <a href="{{ route('payments.checkout', $order) }}" class="mt-4 block rounded-md bg-violet-600 px-4 py-2 text-center font-semibold text-white hover:bg-violet-500">{{ __('Bayar Sekarang') }}</a>
                                <form method="post" action="{{ route('orders.cancel', $order) }}" class="mt-2"
                                      onsubmit="return confirm(@js(__('Batalkan pesanan ini? Tagihan yang menunggu juga dibatalkan.')))">
                                    @csrf
                                    <button type="submit" class="w-full rounded-md px-4 py-2 text-center text-sm font-semibold text-red-700 hover:bg-red-50">{{ __('Batalkan Pesanan') }}</button>
                                </form>
                            @endcan
                        </section>
                    @endif

                    @if ($isCustomer && $order->payments->isNotEmpty())
                        <section class="p-4 sm:p-6 bg-white shadow-sm sm:rounded-lg text-sm">
                            <h3 class="font-semibold text-gray-900">{{ __('Transaksi pembayaran') }}</h3>
                            <ul class="mt-3 space-y-2">
                                @foreach ($order->payments as $payment)
                                    <li class="flex items-center justify-between gap-2">
                                        <a href="{{ route('payments.show', $payment) }}" class="font-mono text-xs text-violet-700 hover:underline">{{ $payment->transaction_code }}</a>
                                        <span class="flex items-center gap-2">
                                            @if ($payment->status === \App\Enums\PaymentStatus::Success)
                                                <a href="{{ route('receipts.show', $payment) }}" class="text-xs font-semibold text-violet-600 hover:underline">{{ __('Struk') }}</a>
                                            @endif
                                            <x-status-badge :status="$payment->status" />
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
