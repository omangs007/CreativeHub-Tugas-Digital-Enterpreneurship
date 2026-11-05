{{-- Field form layanan + paket. Membutuhkan $service, $categories, $tiers, $packages (key = tier). --}}
@php($inputClass = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs')

<div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg space-y-6">
    <header>
        <h3 class="text-lg font-medium text-gray-900">{{ __('Informasi layanan') }}</h3>
    </header>

    <div>
        <x-input-label for="title" :value="__('Judul layanan')" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" minlength="10" maxlength="150" required
            :value="old('title', $service->title)" placeholder="{{ __('Contoh: Desain Map Obby Roblox Custom') }}" />
        <x-input-error class="mt-2" :messages="$errors->get('title')" />
    </div>

    <div>
        <x-input-label for="category_id" :value="__('Kategori')" />
        <select id="category_id" name="category_id" class="{{ $inputClass }}" required>
            <option value="">{{ __('Pilih kategori') }}</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $service->category_id) === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
    </div>

    <div>
        <x-input-label for="description" :value="__('Deskripsi')" />
        <textarea id="description" name="description" rows="6" minlength="30" maxlength="5000" required class="{{ $inputClass }}">{{ old('description', $service->description) }}</textarea>
        <p class="mt-1 text-xs text-gray-500">{{ __('Jelaskan ruang lingkup pekerjaan, apa yang diterima customer, dan kebutuhan dari customer.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('description')" />
    </div>

    <div>
        <x-input-label for="thumbnail" :value="$service->thumbnail_path ? __('Ganti thumbnail (opsional)') : __('Thumbnail (opsional)')" />
        @if ($service->thumbnail_path)
            <img src="{{ asset('storage/'.$service->thumbnail_path) }}" alt="" class="mt-2 aspect-[16/10] w-56 rounded-md object-cover">
        @endif
        <input id="thumbnail" name="thumbnail" type="file" accept="image/jpeg,image/png,image/webp"
            class="mt-2 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-gray-700">
        <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG, atau WebP. Maksimal 2 MB. Tanpa thumbnail, sampul memakai warna kategori.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('thumbnail')" />
    </div>
</div>

<div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
    <header>
        <h3 class="text-lg font-medium text-gray-900">{{ __('Paket') }}</h3>
        <p class="mt-1 text-sm text-gray-600">{{ __('Aktifkan minimal satu paket. Harga dalam Rupiah tanpa desimal, minimal :min.', ['min' => \App\Support\Rupiah::format(\App\Http\Requests\ServiceRequest::MIN_PRICE)]) }}</p>
    </header>
    <x-input-error class="mt-3" :messages="$errors->get('packages')" />

    <div class="mt-6 grid gap-5 lg:grid-cols-3">
        @foreach ($tiers as $tier)
            @php($package = $packages[$tier->value] ?? null)
            @php($field = fn (string $name) => "packages[{$tier->value}][{$name}]")
            @php($old = fn (string $name, $default = null) => old("packages.{$tier->value}.{$name}", $default))
            @php($enabled = (bool) $old('enabled', $package ? $package->is_active : ($tier === \App\Enums\PackageTier::Basic && ! $service->exists)))

            <fieldset x-data="{ enabled: @js($enabled) }" class="rounded-lg border p-4" :class="enabled ? 'border-violet-300 bg-violet-50/40' : 'border-gray-200 bg-gray-50'">
                <legend class="px-1">
                    <label class="flex items-center gap-2 text-sm font-semibold text-gray-900">
                        <input type="hidden" name="{{ $field('enabled') }}" value="0">
                        <input type="checkbox" name="{{ $field('enabled') }}" value="1" x-model="enabled"
                            class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                        {{ __('Paket :tier', ['tier' => $tier->label()]) }}
                    </label>
                </legend>

                <div x-show="enabled" @if (! $enabled) x-cloak @endif class="space-y-4">
                    <div>
                        <x-input-label :for="'pkg-'.$tier->value.'-name'" :value="__('Nama paket')" />
                        <x-text-input :id="'pkg-'.$tier->value.'-name'" :name="$field('name')" type="text" class="mt-1 block w-full" maxlength="100"
                            :value="$old('name', $package?->name ?? $tier->label())" />
                        <x-input-error class="mt-1" :messages="$errors->get('packages.'.$tier->value.'.name')" />
                    </div>

                    <div>
                        <x-input-label :for="'pkg-'.$tier->value.'-description'" :value="__('Yang didapat customer')" />
                        <textarea id="pkg-{{ $tier->value }}-description" name="{{ $field('description') }}" rows="3" maxlength="1000" class="{{ $inputClass }}">{{ $old('description', $package?->description) }}</textarea>
                        <x-input-error class="mt-1" :messages="$errors->get('packages.'.$tier->value.'.description')" />
                    </div>

                    <div>
                        <x-input-label :for="'pkg-'.$tier->value.'-price'" :value="__('Harga (Rp)')" />
                        <x-text-input :id="'pkg-'.$tier->value.'-price'" :name="$field('price')" type="number" inputmode="numeric"
                            min="{{ \App\Http\Requests\ServiceRequest::MIN_PRICE }}" max="{{ \App\Http\Requests\ServiceRequest::MAX_PRICE }}" step="1000"
                            class="mt-1 block w-full" :value="$old('price', $package?->price)" />
                        <x-input-error class="mt-1" :messages="$errors->get('packages.'.$tier->value.'.price')" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <x-input-label :for="'pkg-'.$tier->value.'-days'" :value="__('Pengerjaan (hari)')" />
                            <x-text-input :id="'pkg-'.$tier->value.'-days'" :name="$field('delivery_days')" type="number" min="1" max="365"
                                class="mt-1 block w-full" :value="$old('delivery_days', $package?->delivery_days)" />
                            <x-input-error class="mt-1" :messages="$errors->get('packages.'.$tier->value.'.delivery_days')" />
                        </div>
                        <div>
                            <x-input-label :for="'pkg-'.$tier->value.'-revisions'" :value="__('Revisi')" />
                            <x-text-input :id="'pkg-'.$tier->value.'-revisions'" :name="$field('revisions')" type="number" min="0" max="20"
                                class="mt-1 block w-full" :value="$old('revisions', $package?->revisions ?? 1)" />
                            <x-input-error class="mt-1" :messages="$errors->get('packages.'.$tier->value.'.revisions')" />
                        </div>
                    </div>
                </div>

                <p x-show="! enabled" @if ($enabled) x-cloak @endif class="text-sm text-gray-500">{{ __('Paket tidak ditawarkan.') }}</p>
            </fieldset>
        @endforeach
    </div>
</div>
