<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Profil Freelancer') }}</h2>
            <a href="{{ route('freelancers.show', $profile->user_id) }}" class="text-sm font-semibold text-violet-600 hover:text-violet-700">
                {{ __('Lihat profil publik') }} &rarr;
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="p-4 sm:p-8 bg-white shadow-sm sm:rounded-lg">
                <header>
                    <h2 class="text-lg font-medium text-gray-900">{{ __('Informasi Freelancer') }}</h2>
                    <p class="mt-1 text-sm text-gray-600">{{ __('Data ini tampil di profil publik dan halaman layanan Anda. Email tidak ditampilkan.') }}</p>
                </header>

                <form method="post" action="{{ route('freelancer.profile.update') }}" class="mt-6 space-y-6">
                    @csrf
                    @method('patch')

                    <div>
                        <x-input-label for="headline" :value="__('Headline')" />
                        <x-text-input id="headline" name="headline" type="text" class="mt-1 block w-full" maxlength="120"
                            :value="old('headline', $profile->headline)" placeholder="{{ __('Contoh: Roblox Map Builder & Scripter') }}" />
                        <x-input-error class="mt-2" :messages="$errors->get('headline')" />
                    </div>

                    <div>
                        <x-input-label for="bio" :value="__('Bio')" />
                        <textarea id="bio" name="bio" rows="5" maxlength="2000"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">{{ old('bio', $profile->bio) }}</textarea>
                        <x-input-error class="mt-2" :messages="$errors->get('bio')" />
                    </div>

                    <div>
                        <x-input-label for="skills" :value="__('Keahlian')" />
                        <x-text-input id="skills" name="skills" type="text" class="mt-1 block w-full"
                            :value="old('skills', implode(', ', $profile->skills ?? []))" placeholder="Roblox Studio, Luau, Blender" />
                        <p class="mt-1 text-xs text-gray-500">{{ __('Pisahkan dengan koma, maksimal :max keahlian.', ['max' => \App\Http\Requests\FreelancerProfileUpdateRequest::MAX_SKILLS]) }}</p>
                        <x-input-error class="mt-2" :messages="$errors->get('skills')" />
                        <x-input-error class="mt-2" :messages="$errors->get('skills.*')" />
                    </div>

                    <div>
                        <x-input-label for="availability" :value="__('Status ketersediaan')" />
                        <select id="availability" name="availability"
                            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-xs">
                            @foreach ($availabilities as $availability)
                                <option value="{{ $availability->value }}" @selected(old('availability', $profile->availability->value) === $availability->value)>
                                    {{ $availability->label() }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error class="mt-2" :messages="$errors->get('availability')" />
                    </div>

                    <div>
                        <x-input-label for="portfolio_url" :value="__('Tautan portofolio eksternal (opsional)')" />
                        <x-text-input id="portfolio_url" name="portfolio_url" type="url" class="mt-1 block w-full" maxlength="255"
                            :value="old('portfolio_url', $profile->portfolio_url)" placeholder="https://behance.net/namaanda" />
                        <x-input-error class="mt-2" :messages="$errors->get('portfolio_url')" />
                    </div>

                    <div class="flex items-center gap-4">
                        <x-primary-button>{{ __('Simpan') }}</x-primary-button>

                        @if (session('status') === 'freelancer-profile-updated')
                            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)"
                               class="text-sm text-green-600">{{ __('Profil freelancer tersimpan.') }}</p>
                        @endif
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
