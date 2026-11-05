<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Pesan') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if ($conversations->isEmpty())
                <x-empty-state :title="__('Belum ada percakapan')">
                    {{ __('Mulai chat dari halaman pesanan atau dari penawaran pada proyek.') }}
                </x-empty-state>
            @else
                <ul class="divide-y divide-gray-100 overflow-hidden bg-white shadow-sm sm:rounded-lg">
                    @foreach ($conversations as $conversation)
                        @php($other = $conversation->otherParticipant(auth()->user()))
                        <li>
                            <a href="{{ route('messages.show', $conversation) }}" class="flex items-center gap-4 p-4 hover:bg-gray-50 sm:p-5">
                                <x-avatar :user="$other" class="size-11" />
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2">
                                        <p class="truncate font-semibold text-gray-900">{{ $other->name }}</p>
                                        @if ($conversation->latestMessage)
                                            <span class="shrink-0 text-xs text-gray-500">{{ $conversation->latestMessage->created_at->diffForHumans() }}</span>
                                        @endif
                                    </div>
                                    <p class="truncate text-xs text-violet-700">
                                        {{ $conversation->order_id ? __('Pesanan: :title', ['title' => $conversation->order->title]) : __('Penawaran: :title', ['title' => $conversation->proposal->project->title]) }}
                                    </p>
                                    <p class="truncate text-sm text-gray-500">{{ $conversation->latestMessage?->body ?? __('Belum ada pesan.') }}</p>
                                </div>
                                @if ($conversation->unread_count > 0)
                                    <span class="rounded-full bg-violet-600 px-2 py-0.5 text-xs font-bold text-white">{{ $conversation->unread_count }}</span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
                <div class="mt-4">{{ $conversations->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
