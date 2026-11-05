<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">{{ __('Foto Profil') }}</h2>
        <p class="mt-1 text-sm text-gray-600">{{ __('Tampil di profil freelancer, layanan, penawaran, dan chat. JPG, PNG, atau WebP, maksimal 5 MB. Foto otomatis diperkecil.') }}</p>
    </header>

    <div class="mt-6 flex flex-wrap items-center gap-6">
        <x-avatar :user="$user" class="size-20 text-2xl" />

        <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
            @csrf
            <label for="avatar" class="sr-only">{{ __('Pilih foto') }}</label>
            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required
                class="block w-full min-w-0 text-sm text-gray-700 file:mr-3 file:rounded-md file:border-0 file:bg-gray-800 file:px-4 file:py-2 file:text-xs file:font-semibold file:uppercase file:tracking-widest file:text-white hover:file:bg-gray-700">
            <x-primary-button>{{ __('Unggah') }}</x-primary-button>
        </form>

        @if ($user->avatar_path)
            <form method="post" action="{{ route('profile.avatar.destroy') }}">
                @csrf
                @method('delete')
                <button type="submit" class="text-sm font-semibold text-red-600 hover:text-red-700">{{ __('Hapus foto') }}</button>
            </form>
        @endif
    </div>

    <x-input-error class="mt-2" :messages="$errors->avatar->get('avatar')" />

    @if (in_array(session('status'), ['avatar-updated', 'avatar-removed'], true))
        <p class="mt-3 text-sm text-green-600">{{ session('status') === 'avatar-updated' ? __('Foto profil diperbarui.') : __('Foto profil dihapus.') }}</p>
    @endif
</section>
