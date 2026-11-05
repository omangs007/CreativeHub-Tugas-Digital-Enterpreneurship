<x-public-layout :title="__('Konfirmasi Pesanan')">
    <div class="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-ink-500" aria-label="Breadcrumb">
            <a href="{{ route('services.show', $service->slug) }}" class="hover:text-violet-600">&larr; {{ __('Kembali ke layanan') }}</a>
        </nav>

        <h1 class="mt-4 text-3xl font-extrabold tracking-tight">{{ __('Konfirmasi Pesanan') }}</h1>

        <div class="mt-8 grid gap-8 md:grid-cols-[minmax(0,1fr)_18rem]">
            <section class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100">
                <div class="flex gap-4">
                    <x-service-cover :service="$service" icon-size="size-6" class="aspect-[16/10] w-28 shrink-0 rounded-lg" />
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-violet-600">{{ $service->category->name }}</p>
                        <h2 class="font-bold">{{ $service->title }}</h2>
                        <p class="text-sm text-ink-500">{{ __('oleh :name', ['name' => $service->freelancer->name]) }}</p>
                    </div>
                </div>

                <div class="mt-6 rounded-xl bg-ink-50 p-4">
                    <p class="text-sm font-semibold">{{ __('Paket :tier — :name', ['tier' => $package->tier->label(), 'name' => $package->name]) }}</p>
                    <p class="mt-2 text-sm text-ink-700">{{ $package->description }}</p>
                    <ul class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-ink-600">
                        <li>{{ __('Pengerjaan :days hari', ['days' => $package->delivery_days]) }}</li>
                        <li>{{ trans_choice('{0} Tanpa revisi|[1,*] :count kali revisi', $package->revisions, ['count' => $package->revisions]) }}</li>
                    </ul>
                </div>

                @if ($isOwner)
                    <p class="mt-6 rounded-xl bg-amber-50 p-4 text-sm text-amber-900">{{ __('Ini layanan milik Anda sendiri dan tidak dapat dipesan.') }}</p>
                @else
                    <form method="post" action="{{ route('checkout.package.store', $package) }}" class="mt-6 space-y-4">
                        @csrf
                        <x-input-error :messages="$errors->get('package')" />

                        <div>
                            <label for="note" class="text-sm font-semibold text-ink-700">{{ __('Catatan untuk freelancer (opsional)') }}</label>
                            <textarea id="note" name="note" rows="4" maxlength="2000"
                                class="mt-1 block w-full rounded-lg border-ink-200 text-sm focus:border-violet-500 focus:ring-violet-500"
                                placeholder="{{ __('Contoh: tema warna, referensi, atau tenggat khusus.') }}">{{ old('note') }}</textarea>
                            <x-input-error class="mt-1" :messages="$errors->get('note')" />
                        </div>

                        <label class="flex items-start gap-2 text-sm text-ink-700">
                            <input type="checkbox" name="agree" value="1" @checked(old('agree')) class="mt-0.5 rounded border-ink-300 text-violet-600 focus:ring-violet-500">
                            <span>{{ __('Saya memahami ruang lingkup paket di atas. Pembayaran di CreativeHub adalah simulasi dan tidak memindahkan uang sungguhan.') }}</span>
                        </label>
                        <x-input-error :messages="$errors->get('agree')" />

                        <button type="submit" class="w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white shadow-lg shadow-violet-600/30 hover:bg-violet-500">
                            {{ __('Buat Pesanan') }}
                        </button>
                    </form>
                @endif
            </section>

            <aside class="h-fit rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100 text-sm">
                <h2 class="font-bold">{{ __('Ringkasan biaya') }}</h2>
                <dl class="mt-4 space-y-2">
                    <div class="flex justify-between"><dt class="text-ink-500">{{ __('Harga paket') }}</dt><dd>@rupiah($package->price)</dd></div>
                    <div class="flex justify-between"><dt class="text-ink-500">{{ __('Biaya layanan') }}</dt><dd>@rupiah(0)</dd></div>
                    <div class="flex justify-between border-t border-ink-100 pt-3 text-base font-extrabold"><dt>{{ __('Total') }}</dt><dd>@rupiah($package->price)</dd></div>
                </dl>
                <p class="mt-4 text-xs text-ink-400">{{ __('Total dihitung ulang oleh server saat pesanan dibuat.') }}</p>
            </aside>
        </div>
    </div>
</x-public-layout>
