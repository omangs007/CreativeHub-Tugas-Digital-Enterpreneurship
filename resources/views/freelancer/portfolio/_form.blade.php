{{-- Field form portofolio, dipakai halaman tambah dan edit. Membutuhkan $categories dan opsional $item. --}}
@php($item ??= null)

<div>
    <x-input-label for="title" :value="__('Judul')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" maxlength="120" required
        :value="old('title', $item?->title)" />
    <x-input-error class="mt-2" :messages="$errors->get('title')" />
</div>

<div>
    <x-input-label for="category_id" :value="__('Kategori (opsional)')" />
    <select id="category_id" name="category_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">
        <option value="">{{ __('Tanpa kategori') }}</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected((string) old('category_id', $item?->category_id) === (string) $category->id)>{{ $category->name }}</option>
        @endforeach
    </select>
    <x-input-error class="mt-2" :messages="$errors->get('category_id')" />
</div>

<div>
    <x-input-label for="description" :value="__('Deskripsi (opsional)')" />
    <textarea id="description" name="description" rows="3" maxlength="1000"
        class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">{{ old('description', $item?->description) }}</textarea>
    <x-input-error class="mt-2" :messages="$errors->get('description')" />
</div>

<div>
    <x-input-label for="project_url" :value="__('Tautan proyek (opsional)')" />
    <x-text-input id="project_url" name="project_url" type="url" class="mt-1 block w-full" maxlength="255"
        :value="old('project_url', $item?->project_url)" placeholder="https://" />
    <x-input-error class="mt-2" :messages="$errors->get('project_url')" />
</div>

<div>
    <x-input-label for="image" :value="$item ? __('Ganti gambar (opsional)') : __('Gambar')" />
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" @required(! $item)
        class="mt-1 block w-full text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-gray-700">
    <p class="mt-1 text-xs text-gray-500">{{ __('JPG, PNG, atau WebP. Maksimal 2 MB.') }}</p>
    <x-input-error class="mt-2" :messages="$errors->get('image')" />
</div>
