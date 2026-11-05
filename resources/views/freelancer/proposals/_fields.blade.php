{{-- Field penawaran, dipakai di halaman proyek publik dan halaman edit. $proposal boleh null. --}}
@php($inputClass = 'mt-1 block w-full rounded-lg border-ink-200 text-sm focus:border-violet-500 focus:ring-violet-500')

<x-input-error :messages="$errors->get('proposal')" />

<div>
    <label for="price" class="text-sm font-semibold text-ink-700">{{ __('Harga penawaran (Rp)') }}</label>
    <input id="price" name="price" type="number" inputmode="numeric" min="{{ \App\Http\Requests\ProposalRequest::MIN_PRICE }}" step="1000" required
        value="{{ old('price', $proposal?->price) }}" class="{{ $inputClass }}">
    <x-input-error class="mt-1" :messages="$errors->get('price')" />
</div>

<div class="grid grid-cols-2 gap-3">
    <div>
        <label for="delivery_days" class="text-sm font-semibold text-ink-700">{{ __('Estimasi (hari)') }}</label>
        <input id="delivery_days" name="delivery_days" type="number" min="1" max="365" required
            value="{{ old('delivery_days', $proposal?->delivery_days) }}" class="{{ $inputClass }}">
        <x-input-error class="mt-1" :messages="$errors->get('delivery_days')" />
    </div>
    <div>
        <label for="revisions" class="text-sm font-semibold text-ink-700">{{ __('Revisi') }}</label>
        <input id="revisions" name="revisions" type="number" min="0" max="20" required
            value="{{ old('revisions', $proposal?->revisions ?? 2) }}" class="{{ $inputClass }}">
        <x-input-error class="mt-1" :messages="$errors->get('revisions')" />
    </div>
</div>

<div>
    <label for="message" class="text-sm font-semibold text-ink-700">{{ __('Pesan untuk customer') }}</label>
    <textarea id="message" name="message" rows="5" minlength="30" maxlength="3000" required class="{{ $inputClass }}"
        placeholder="{{ __('Jelaskan pendekatan, pengalaman relevan, dan apa yang akan diserahkan.') }}">{{ old('message', $proposal?->message) }}</textarea>
    <x-input-error class="mt-1" :messages="$errors->get('message')" />
</div>
