<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Notifikasi') }}</h2>
            @if ($notifications->contains(fn ($n) => $n->read_at === null))
                <form method="post" action="{{ route('notifications.read-all') }}">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-violet-600 hover:text-violet-700">{{ __('Tandai semua terbaca') }}</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if ($notifications->isEmpty())
                <x-empty-state :title="__('Belum ada notifikasi')">
                    {{ __('Aktivitas pesanan, penawaran, dan layanan Anda akan muncul di sini.') }}
                </x-empty-state>
            @else
                <ul class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @foreach ($notifications as $notification)
                        <li>
                            <a href="{{ route('notifications.open', $notification->id) }}" @class(['flex gap-3 p-4 hover:bg-gray-50 sm:p-5', 'bg-violet-50/60' => $notification->read_at === null])>
                                <span @class(['mt-1.5 size-2 shrink-0 rounded-full', 'bg-violet-600' => $notification->read_at === null, 'bg-transparent' => $notification->read_at !== null]) aria-hidden="true"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="font-semibold text-gray-900">{{ $notification->data['title'] ?? '' }}</span>
                                        <span class="shrink-0 text-xs text-gray-500">{{ $notification->created_at->diffForHumans() }}</span>
                                    </span>
                                    <span class="mt-0.5 block text-sm text-gray-600">{{ $notification->data['body'] ?? '' }}</span>
                                    @if ($notification->read_at === null)
                                        <span class="sr-only">{{ __('(belum dibaca)') }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $notifications->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
