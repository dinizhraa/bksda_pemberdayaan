<x-app-layout>
    <x-slot name="header">
        <h1 class="page-title">Chat dengan AI</h1>
        <p class="page-sub">Tanyakan data aktual kelompok binaan, kawasan, monitoring, dan bantuan.</p>
    </x-slot>

    <section
        data-ai-chat
        data-send-url="{{ $conversation ? route('ai.chat.messages.store', $conversation, false) : '' }}"
        data-conversation-id="{{ $conversation?->id ?? '' }}"
        class="grid min-h-[70vh] gap-4 lg:grid-cols-[17rem_minmax(0,1fr)]"
    >
        <aside class="glass-card flex flex-col gap-4 p-4">
            <form method="POST" action="{{ route('ai.chat.conversations.store', [], false) }}">
                @csrf
                <button type="submit" class="btn-brand w-full justify-center">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Percakapan baru
                </button>
            </form>

            @if (session('status'))
                <p class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800" role="status">
                    {{ session('status') }}
                </p>
            @endif

            <div class="min-h-0 flex-1">
                <h2 class="mb-2 px-2 text-xs font-bold uppercase tracking-wide text-ink-400">Riwayat percakapan</h2>
                <nav aria-label="Riwayat chat" class="flex max-h-[50vh] flex-col gap-1 overflow-y-auto lg:max-h-[58vh]">
                    @forelse ($conversations as $item)
                        <div class="flex items-center gap-1">
                            <a
                                href="{{ route('ai.chat.show', $item) }}"
                                data-conversation-id="{{ $item->id }}"
                                @class([
                                    'min-w-0 flex-1 rounded-xl px-3 py-2.5 text-sm transition hover:bg-white/70',
                                    'bg-brand-500/15 font-semibold text-brand-800' => $conversation?->is($item),
                                    'text-ink-600' => ! $conversation?->is($item),
                                ])
                            >
                                <span class="block truncate">{{ $item->title }}</span>
                                <span class="mt-1 block text-[11px] text-ink-400">{{ $item->updated_at?->format('d M Y H:i') }}</span>
                            </a>
                            <form
                                method="POST"
                                action="{{ route('ai.chat.conversations.destroy', $item, false) }}"
                                onsubmit="return confirm('Hapus percakapan ini beserta semua pesannya? Tindakan ini tidak dapat dibatalkan.')"
                            >
                                @csrf
                                @method('DELETE')
                                @if ($conversation)
                                    <input type="hidden" name="active_conversation_id" value="{{ $conversation->id }}">
                                @endif
                                <button
                                    type="submit"
                                    class="grid size-9 shrink-0 place-items-center rounded-lg text-ink-400 transition hover:bg-rose-50 hover:text-rose-700 focus:outline-none focus:ring-2 focus:ring-rose-300"
                                    aria-label="Hapus percakapan: {{ $item->title }}"
                                    title="Hapus percakapan"
                                >
                                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="px-2 py-3 text-sm text-ink-500">Belum ada riwayat percakapan.</p>
                    @endforelse
                </nav>
            </div>
        </aside>

        <section class="glass-card flex min-h-[70vh] min-w-0 flex-col overflow-hidden p-0">
            <header class="flex items-center gap-3 border-b border-ink-100 px-5 py-4">
                <x-icon-badge icon="robot" tone="teal" class="size-10" />
                <div class="min-w-0">
                    <h2 class="truncate font-bold text-ink-900">{{ $conversation?->title ?? 'AI Assistant BKSDA' }}</h2>
                    <p class="text-xs text-ink-500">Jawaban menggunakan ringkasan database, tanpa akses AI langsung ke SQL.</p>
                </div>
            </header>

            <div id="ai-chat-messages" class="flex-1 space-y-4 overflow-y-auto p-4 sm:p-6" aria-live="polite">
                @forelse ($messages as $message)
                    <article @class([
                        'flex gap-3',
                        'justify-end' => $message->role === \App\Models\AiMessage::ROLE_USER,
                    ])>
                        @if ($message->role !== \App\Models\AiMessage::ROLE_USER)
                            <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-brand-100 text-brand-700" aria-hidden="true">
                                <i class="fa-solid fa-robot text-xs"></i>
                            </span>
                        @endif
                        <div @class([
                            'max-w-[88%] whitespace-pre-wrap break-words rounded-2xl px-4 py-3 text-sm leading-6',
                            'bg-brand-700 text-white' => $message->role === \App\Models\AiMessage::ROLE_USER,
                            'border border-ink-100 bg-white/80 text-ink-700' => $message->role !== \App\Models\AiMessage::ROLE_USER,
                        ])>
                            <span data-message-content>{{ $message->content }}</span>
                            @if ($message->role === \App\Models\AiMessage::ROLE_USER && in_array($message->client_message_id, $pendingMessageIds, true))
                                <button
                                    type="button"
                                    data-ai-retry-message="{{ $message->client_message_id }}"
                                    class="mt-2 block text-[11px] font-semibold underline decoration-white/60 underline-offset-2"
                                >Coba lagi</button>
                            @endif
                        </div>
                        @if ($message->role === \App\Models\AiMessage::ROLE_USER)
                            <span class="grid size-8 shrink-0 place-items-center rounded-xl bg-ink-100 text-ink-600" aria-hidden="true">
                                <i class="fa-solid fa-user text-xs"></i>
                            </span>
                        @endif
                    </article>
                @empty
                    <div class="mx-auto flex max-w-xl flex-col items-center py-10 text-center">
                        <span class="grid size-14 place-items-center rounded-2xl bg-brand-100 text-2xl text-brand-700" aria-hidden="true">
                            <i class="fa-solid fa-comments"></i>
                        </span>
                        <h3 class="mt-4 text-lg font-bold text-ink-900">Apa yang ingin Anda ketahui?</h3>
                        <p class="mt-2 text-sm leading-6 text-ink-500">
                            Mulai percakapan baru, lalu tanyakan statistik dan kondisi kelompok berdasarkan data yang tersimpan.
                        </p>
                        <div class="mt-5 flex flex-wrap justify-center gap-2 text-xs text-ink-600">
                            <span class="rounded-full border border-ink-100 bg-white/70 px-3 py-2">Berapa jumlah kelompok aktif?</span>
                            <span class="rounded-full border border-ink-100 bg-white/70 px-3 py-2">Kawasan mana yang terbanyak?</span>
                            <span class="rounded-full border border-ink-100 bg-white/70 px-3 py-2">Kelompok mana belum dimonitoring?</span>
                        </div>
                    </div>
                @endforelse
            </div>

            <div id="ai-chat-error" class="mx-4 mb-2 hidden rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                <p id="ai-chat-error-message"></p>
                <button id="ai-chat-retry" type="button" class="mt-2 font-semibold underline underline-offset-2" hidden>
                    Coba lagi
                </button>
            </div>

            <form
                id="ai-chat-form"
                action="{{ $conversation ? route('ai.chat.messages.store', $conversation, false) : '#' }}"
                class="border-t border-ink-100 bg-white/50 p-3 sm:p-4"
            >
                @csrf
                <label for="ai-chat-input" class="sr-only">Tulis pertanyaan</label>
                <div class="flex items-end gap-2 rounded-2xl border border-ink-200 bg-white p-2 shadow-sm focus-within:ring-2 focus-within:ring-brand-400">
                    <textarea
                        id="ai-chat-input"
                        name="message"
                        rows="2"
                        maxlength="2000"
                        placeholder="{{ $conversation ? 'Tulis pertanyaan tentang data pemberdayaan...' : 'Buat percakapan baru untuk mulai bertanya...' }}"
                        class="max-h-40 min-h-12 flex-1 resize-y border-0 bg-transparent text-sm text-ink-800 placeholder:text-ink-400 focus:ring-0"
                        @disabled(! $conversation)
                    ></textarea>
                    <button
                        id="ai-chat-submit"
                        type="submit"
                        class="grid size-11 shrink-0 place-items-center rounded-xl bg-brand-700 text-white transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50"
                        aria-label="Kirim pertanyaan"
                        @disabled(! $conversation)
                    >
                        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                    </button>
                </div>
                <p class="mt-2 px-1 text-[11px] text-ink-400">Maksimal 2.000 karakter. Hindari memasukkan data pribadi atau kontak.</p>
            </form>
        </section>
    </section>
</x-app-layout>
