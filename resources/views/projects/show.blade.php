<x-public-layout :title="$project->title">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <nav class="text-sm text-ink-500" aria-label="Breadcrumb">
            <a href="{{ route('projects.index') }}" class="hover:text-violet-600">{{ __('Proyek') }}</a>
            <span class="mx-1.5">/</span>
            <span>{{ $project->category->name }}</span>
        </nav>

        <div class="mt-6 grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <article>
                <h1 class="text-3xl font-extrabold leading-tight tracking-tight sm:text-4xl">{{ $project->title }}</h1>
                <p class="mt-3 text-sm text-ink-500">{{ __('Diposting oleh :name · :date', ['name' => $project->customer->name, 'date' => $project->created_at->translatedFormat('d F Y')]) }}</p>

                <section class="mt-8 rounded-2xl bg-white p-6 ring-1 ring-ink-100">
                    <h2 class="text-xl font-bold">{{ __('Deskripsi kebutuhan') }}</h2>
                    <div class="mt-3 whitespace-pre-line leading-relaxed text-ink-700">{{ $project->description }}</div>
                </section>
            </article>

            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100">
                    <dl class="space-y-4 text-sm">
                        <div>
                            <dt class="text-ink-500">{{ __('Anggaran') }}</dt>
                            <dd class="text-xl font-extrabold">{{ $project->budgetLabel() }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">{{ __('Tenggat') }}</dt>
                            <dd class="font-semibold">{{ $project->deadline?->translatedFormat('d F Y') ?? __('Tidak ditentukan') }}</dd>
                        </div>
                        <div>
                            <dt class="text-ink-500">{{ __('Kategori') }}</dt>
                            <dd class="font-semibold">{{ $project->category->name }}</dd>
                        </div>
                    </dl>
                </div>

                <div id="penawaran" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-ink-100">
                    <div class="flex items-center justify-between gap-3">
                        <h2 class="font-bold">{{ __('Kirim penawaran') }}</h2>
                        <span class="text-xs font-semibold text-ink-500">{{ trans_choice('{0} Belum ada penawaran|[1,*] :count penawaran', $proposalCount, ['count' => $proposalCount]) }}</span>
                    </div>

                    @guest
                        <p class="mt-2 text-sm text-ink-600">{{ __('Masuk sebagai freelancer untuk mengirim penawaran.') }}</p>
                        <a href="{{ route('login') }}" class="mt-4 block rounded-xl bg-violet-600 px-4 py-3 text-center font-semibold text-white hover:bg-violet-500">{{ __('Masuk') }}</a>
                    @else
                        @if ($project->customer_id === auth()->id())
                            <p class="mt-2 text-sm text-ink-600">{{ __('Ini proyek Anda.') }}</p>
                            <a href="{{ route('customer.projects.show', $project) }}" class="mt-4 block rounded-xl bg-ink-900 px-4 py-3 text-center font-semibold text-white hover:bg-ink-700">{{ __('Kelola penawaran') }}</a>
                        @elseif ($myProposal && in_array($myProposal->status, [\App\Enums\ProposalStatus::Pending, \App\Enums\ProposalStatus::Accepted], true))
                            <p class="mt-2 text-sm text-ink-600">
                                {{ __('Anda sudah mengirim penawaran :price.', ['price' => \App\Support\Rupiah::format($myProposal->price)]) }}
                                <x-status-badge :status="$myProposal->status" class="ml-1" />
                            </p>
                            <a href="{{ route('freelancer.proposals.index') }}" class="mt-4 block rounded-xl bg-ink-900 px-4 py-3 text-center font-semibold text-white hover:bg-ink-700">{{ __('Lihat penawaran saya') }}</a>
                        @elseif (! auth()->user()->hasRole(\App\Enums\RoleName::Freelancer))
                            <p class="mt-2 text-sm text-ink-600">{{ __('Aktifkan akun freelancer dari halaman profil untuk mengirim penawaran.') }}</p>
                            <a href="{{ route('profile.edit') }}" class="mt-4 block rounded-xl bg-ink-900 px-4 py-3 text-center font-semibold text-white hover:bg-ink-700">{{ __('Ke Profil') }}</a>
                        @else
                            @can('propose', $project)
                                <form method="post" action="{{ route('freelancer.proposals.store', $project) }}" class="mt-4 space-y-4">
                                    @csrf
                                    @include('freelancer.proposals._fields', ['proposal' => null])
                                    <button type="submit" class="w-full rounded-xl bg-violet-600 px-4 py-3 font-semibold text-white hover:bg-violet-500">{{ __('Kirim Penawaran') }}</button>
                                </form>
                            @endcan
                        @endif
                    @endguest
                </div>
            </aside>
        </div>
    </div>
</x-public-layout>
