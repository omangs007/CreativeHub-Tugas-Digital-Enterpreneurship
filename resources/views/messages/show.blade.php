<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <x-avatar :user="$other" class="size-10" />
                <div>
                    <h2 class="font-semibold text-lg text-gray-800 leading-tight">{{ $other->name }}</h2>
                    <p class="text-xs text-gray-500">
                        @if ($conversation->order_id)
                            {{ __('Pesanan') }} <a href="{{ route('orders.show', $conversation->order) }}" class="font-semibold text-violet-700 hover:underline">{{ $conversation->order->title }}</a>
                        @else
                            {{ __('Penawaran untuk') }} <span class="font-semibold">{{ $conversation->proposal->project->title }}</span>
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('messages.index') }}" class="text-sm font-semibold text-gray-600 hover:text-gray-900">&larr; {{ __('Semua pesan') }}</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="flex h-[70vh] flex-col overflow-hidden bg-white shadow-sm sm:rounded-lg"
                 x-data="chatRoom({
                    messages: @js($messages),
                    pollUrl: @js(route('messages.poll', $conversation)),
                    sendUrl: @js(route('messages.store', $conversation)),
                    csrf: @js(csrf_token()),
                 })"
                 x-init="start()">
                <div x-ref="list" class="flex-1 space-y-3 overflow-y-auto p-4 sm:p-6" aria-live="polite">
                    <template x-if="messages.length === 0">
                        <p class="text-center text-sm text-gray-500">{{ __('Belum ada pesan. Sapa :name terlebih dahulu.', ['name' => $other->name]) }}</p>
                    </template>
                    <template x-for="message in messages" :key="message.id">
                        <div class="flex" :class="message.mine ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[80%] rounded-2xl px-4 py-2 text-sm" :class="message.mine ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-900'">
                                {{-- x-text merender sebagai teks, sehingga HTML/script di pesan tidak dieksekusi. --}}
                                <p class="whitespace-pre-line break-words" x-text="message.body"></p>
                                <p class="mt-1 text-[11px]" :class="message.mine ? 'text-violet-200' : 'text-gray-500'" x-text="message.time"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <form class="flex gap-2 border-t border-gray-100 p-3 sm:p-4" @submit.prevent="send" method="post" action="{{ route('messages.store', $conversation) }}">
                    @csrf
                    <label for="body" class="sr-only">{{ __('Tulis pesan') }}</label>
                    <textarea id="body" name="body" rows="1" maxlength="2000" required x-model="draft" @keydown.enter.prevent.exact="send"
                        class="flex-1 resize-none rounded-lg border-gray-300 text-sm focus:border-violet-500 focus:ring-violet-500"
                        placeholder="{{ __('Tulis pesan… (Enter untuk kirim, Shift+Enter baris baru)') }}"></textarea>
                    <button type="submit" :disabled="sending || draft.trim() === ''" class="rounded-lg bg-violet-600 px-4 text-sm font-semibold text-white hover:bg-violet-500 disabled:opacity-50">{{ __('Kirim') }}</button>
                </form>
                <p x-show="error" x-text="error" class="px-4 pb-3 text-xs text-red-600" x-cloak></p>
            </div>
            <p class="mt-2 text-center text-xs text-gray-400">{{ __('Pesan baru dimuat otomatis setiap beberapa detik.') }}</p>
        </div>
    </div>

    <script>
        function chatRoom({ messages, pollUrl, sendUrl, csrf }) {
            return {
                messages,
                draft: '',
                sending: false,
                error: '',
                timer: null,
                start() {
                    this.scrollToEnd();
                    this.timer = setInterval(() => this.poll(), 4000);
                },
                lastId() {
                    return this.messages.length ? this.messages[this.messages.length - 1].id : 0;
                },
                append(items) {
                    const known = new Set(this.messages.map((m) => m.id));
                    const fresh = items.filter((m) => !known.has(m.id));
                    if (fresh.length) {
                        this.messages.push(...fresh);
                        this.$nextTick(() => this.scrollToEnd());
                    }
                },
                async poll() {
                    try {
                        const response = await fetch(`${pollUrl}?after=${this.lastId()}`, { headers: { Accept: 'application/json' } });
                        if (response.ok) {
                            this.append((await response.json()).messages);
                        }
                    } catch (e) {
                        // Koneksi terputus sementara; polling berikutnya akan mencoba lagi.
                    }
                },
                async send() {
                    const body = this.draft.trim();
                    if (!body || this.sending) return;
                    this.sending = true;
                    this.error = '';
                    try {
                        const response = await fetch(sendUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ body }),
                        });
                        if (!response.ok) throw new Error();
                        this.append([(await response.json()).message]);
                        this.draft = '';
                    } catch (e) {
                        this.error = @js(__('Pesan gagal dikirim. Coba lagi.'));
                    } finally {
                        this.sending = false;
                    }
                },
                scrollToEnd() {
                    this.$refs.list.scrollTop = this.$refs.list.scrollHeight;
                },
            };
        }
    </script>
</x-app-layout>
