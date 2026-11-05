<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Peran Akun') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Peran menentukan fitur yang dapat Anda gunakan di CreativeHub.') }}
        </p>
    </header>

    <div class="mt-4 flex flex-wrap gap-2">
        @forelse ($user->roles as $role)
            <span class="inline-flex items-center rounded-full bg-violet-100 px-3 py-1 text-sm font-medium text-violet-800">
                {{ $role->label }}
            </span>
        @empty
            <span class="text-sm text-gray-500">{{ __('Belum ada peran.') }}</span>
        @endforelse
    </div>

    @can('create', App\Models\FreelancerProfile::class)
        <form method="post" action="{{ route('profile.freelancer.store') }}" class="mt-6">
            @csrf

            <p class="text-sm text-gray-600">
                {{ __('Ingin menawarkan jasa? Aktifkan akun freelancer. Anda tetap dapat memesan jasa sebagai customer.') }}
            </p>

            <x-primary-button class="mt-4">{{ __('Aktifkan Akun Freelancer') }}</x-primary-button>
        </form>
    @endcan

    @if (session('status') === 'freelancer-activated')
        <p class="mt-4 text-sm font-medium text-green-600">
            {{ __('Akun freelancer berhasil diaktifkan.') }}
        </p>
    @endif
</section>
