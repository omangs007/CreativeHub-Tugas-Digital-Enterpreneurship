{{-- Field form proyek. Membutuhkan $project dan $categories. --}}
@php($inputClass = 'mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs')

<div>
    <x-input-label for="title" :value="__('Judul proyek')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" minlength="10" maxlength="150" required
        :value="old('title', $project->title)" placeholder="{{ __('Contoh: Butuh map Roblox bertema sekolah untuk event') }}" />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="category_id" :value="__('Kategori')" />
    <select id="category_id" name="category_id" class="{{ $inputClass }}" required>
        <option value="">{{ __('Pilih kategori') }}</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) old('category_id', $project->category_id) === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
</div>

<div>
    <x-input-label for="description" :value="__('Deskripsi kebutuhan')" />
    <textarea id="description" name="description" rows="7" minlength="30" maxlength="5000" required class="{{ $inputClass }}">{{ old('description', $project->description) }}</textarea>
    <p class="mt-1 text-xs text-gray-500">{{ __('Jelaskan hasil yang diharapkan, referensi, dan hal yang perlu diketahui freelancer.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <x-input-label for="budget_min" :value="__('Anggaran minimum (Rp, opsional)')" />
        <x-text-input id="budget_min" name="budget_min" type="number" min="{{ \App\Http\Requests\ProjectRequest::MIN_BUDGET }}" step="1000" inputmode="numeric"
            class="mt-1 block w-full" :value="old('budget_min', $project->budget_min)" />
        <x-input-error class="mt-2" :messages="$errors->get('budget_min')" />
    </div>
    <div>
        <x-input-label for="budget_max" :value="__('Anggaran maksimum (Rp, opsional)')" />
        <x-text-input id="budget_max" name="budget_max" type="number" min="{{ \App\Http\Requests\ProjectRequest::MIN_BUDGET }}" step="1000" inputmode="numeric"
            class="mt-1 block w-full" :value="old('budget_max', $project->budget_max)" />
        <x-input-error class="mt-2" :messages="$errors->get('budget_max')" />
    </div>
</div>

<div>
    <x-input-label for="deadline" :value="__('Tenggat (opsional)')" />
    <x-text-input id="deadline" name="deadline" type="date" class="mt-1 block w-full sm:w-60"
        min="{{ now()->addDay()->toDateString() }}" :value="old('deadline', $project->deadline?->toDateString())" />
    <x-input-error class="mt-2" :messages="$errors->get('deadline')" />
</div>
