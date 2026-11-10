{{-- Floating conversational assistant --}}
@php
    $assistantLlm = app(\App\Services\AssistantService::class)->isLlmConfigured();
@endphp
<div
    x-data="aiAssistant(@js(route('assistant.chat')), @js($assistantLlm), @js(auth()->id()))"
    x-init="init()"
    x-cloak
    class="fixed bottom-5 right-5 z-[60] flex flex-col items-end gap-3 pointer-events-none"
>
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-3 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 translate-y-3 scale-95"
        class="pointer-events-auto w-[min(100vw-2rem,24rem)] h-[min(72vh,34rem)] flex flex-col rounded-2xl border border-gray-200 shadow-2xl overflow-hidden dark-panel"
        style="background: var(--theme-panel, #fff); color: var(--theme-text, #111);"
        @keydown.escape.window="if (showHistory) { showHistory = false } else { open = false }"
    >
        <div class="flex items-center gap-2 shrink-0" style="background: var(--theme-primary, #0b5d3b); color: var(--theme-on-primary, #ffffff);">
            <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
            </div>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-semibold leading-tight">CSUCC Assistant</p>
                <p class="text-[11px] opacity-80 truncate" x-text="showHistory ? 'Conversation history' : (llm ? 'Live conversation · history saved' : 'Chat mode · history saved')"></p>
            </div>
            <button type="button" @click="showHistory = !showHistory" class="p-1.5 rounded-lg hover:bg-white/15 text-xs font-medium" :class="showHistory ? 'bg-white/20' : ''" title="History">History</button>
            <button type="button" @click="clearChat()" class="p-1.5 rounded-lg hover:bg-white/15 text-xs font-medium" title="New chat">New</button>
            <button type="button" @click="open = false; showHistory = false" class="p-1.5 rounded-lg hover:bg-white/15" aria-label="Close assistant">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- History panel --}}
        <div x-show="showHistory" class="flex-1 overflow-y-auto px-3 py-3 space-y-2" x-cloak>
            <template x-if="conversations.length === 0">
                <p class="text-sm text-gray-500 text-center py-8">No saved conversations yet. Start chatting and they will appear here.</p>
            </template>
            <template x-for="conv in conversations" :key="conv.id">
                <div
                    class="flex items-start gap-2 rounded-xl border px-3 py-2.5 transition"
                    :class="conv.id === activeId ? 'border-emerald-300 bg-emerald-50/70' : 'border-gray-200 hover:bg-gray-50'"
                >
                    <button type="button" @click="loadConversation(conv.id)" class="min-w-0 flex-1 text-left">
                        <p class="text-sm font-medium text-gray-800 truncate" x-text="conv.title"></p>
                        <p class="text-[11px] text-gray-500 mt-0.5" x-text="formatWhen(conv.updatedAt)"></p>
                    </button>
                    <button
                        type="button"
                        @click.stop="deleteConversation(conv.id)"
                        class="shrink-0 p-1 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50"
                        title="Delete"
                        aria-label="Delete conversation"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
            </template>
        </div>

        {{-- Active chat --}}
        <div x-show="!showHistory" class="flex-1 overflow-y-auto px-3 py-3 space-y-3" x-ref="thread">
            <template x-for="(msg, i) in messages" :key="i">
                <div :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                    <div
                        :class="msg.role === 'user'
                            ? 'max-w-[85%] rounded-2xl rounded-br-md px-3 py-2 text-sm shadow-sm'
                            : 'max-w-[90%] rounded-2xl rounded-bl-md px-3 py-2 text-sm bg-gray-100 text-gray-800 border border-gray-100 shadow-sm'"
                        :style="msg.role === 'user' ? 'background: var(--theme-primary, #0b5d3b); color: var(--theme-on-primary, #ffffff);' : ''"
                    >
                        <p class="whitespace-pre-wrap leading-relaxed" x-text="msg.text"></p>
                        <template x-if="msg.links && msg.links.length">
                            <div class="mt-2 flex flex-wrap gap-1.5">
                                <template x-for="(link, li) in msg.links" :key="li">
                                    <a :href="link.url" class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-medium bg-white border border-indigo-100 text-indigo-700 hover:bg-indigo-50" x-text="link.label"></a>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
            <div x-show="busy" class="flex justify-start">
                <div class="rounded-2xl rounded-bl-md px-3 py-2 text-xs text-gray-500 bg-gray-100 border border-gray-100 flex items-center gap-1.5">
                    <span class="inline-flex gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:120ms"></span>
                        <span class="w-1.5 h-1.5 rounded-full bg-gray-400 animate-bounce" style="animation-delay:240ms"></span>
                    </span>
                    Typing…
                </div>
            </div>
        </div>

        <div x-show="!showHistory" class="px-3 pb-2 flex flex-wrap gap-1.5 shrink-0 border-t border-gray-100 pt-2">
            <template x-for="chip in chips" :key="chip">
                <button
                    type="button"
                    @click="ask(chip)"
                    class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-gray-50 hover:bg-gray-100 text-gray-700 border border-gray-200"
                    x-text="chip"
                ></button>
            </template>
        </div>

        <form x-show="!showHistory" @submit.prevent="send()" class="flex items-end gap-2 px-3 py-3 border-t border-gray-100 shrink-0">
            <textarea
                x-model="draft"
                @keydown.enter.prevent="if (!$event.shiftKey) send()"
                rows="2"
                maxlength="2000"
                placeholder="Message the assistant…"
                class="flex-1 resize-none px-3 py-2 text-sm border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/40 focus:border-indigo-400 outline-none max-h-28"
            ></textarea>
            <button
                type="submit"
                :disabled="busy || !draft.trim()"
                class="shrink-0 inline-flex items-center justify-center w-10 h-10 rounded-xl disabled:opacity-40"
                style="background: var(--theme-primary, #0b5d3b); color: var(--theme-on-primary, #ffffff);"
                aria-label="Send"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
            </button>
        </form>
    </div>

    <button
        type="button"
        @click="open = !open; if (open) { showHistory = false; $nextTick(() => scrollBottom()) }"
        class="pointer-events-auto group relative inline-flex items-center justify-center w-14 h-14 rounded-full shadow-lg hover:shadow-xl focus:outline-none focus:ring-4 focus:ring-emerald-500/30 transition-transform hover:scale-105 border border-gray-200"
        style="background: var(--theme-primary, #0b5d3b); color: var(--theme-on-primary, #ffffff);"
        :aria-expanded="open"
        aria-label="Open CSUCC assistant"
    >
        <span x-show="!open" class="absolute -top-1 -left-1 flex h-3 w-3">
            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-300 opacity-75"></span>
            <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-200"></span>
        </span>
        <svg x-show="!open" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z"/></svg>
        <svg x-show="open" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
</div>

<script>
function aiAssistant(endpoint, llmEnabled, userId) {
    const welcome = llmEnabled
        ? "Hi! I'm your CSUCC assistant. Ask me anything about this system — I'll keep the conversation going and remember what we talked about."
        : "Hi! I'm your CSUCC assistant. We can chat about students, enrollment, analytics, forecasting, and more. For fuller natural AI replies, add a free ASSISTANT_GEMINI_API_KEY to .env (Google AI Studio).";

    const storageKey = 'csucc_assistant_chats_v1_' + (userId || 'guest');
    const MAX_CONVERSATIONS = 30;

    function uid() {
        return 'c_' + Date.now().toString(36) + '_' + Math.random().toString(36).slice(2, 8);
    }

    function welcomeMessages() {
        return [{ role: 'assistant', text: welcome, links: [] }];
    }

    return {
        open: false,
        showHistory: false,
        busy: false,
        draft: '',
        endpoint,
        llm: !!llmEnabled,
        activeId: null,
        conversations: [],
        chips: [
            'How do I add a student?',
            'Walk me through CSV import',
            'Where is forecasting?',
            'Help with password reset',
        ],
        messages: welcomeMessages(),

        init() {
            this.loadStore();
            if (!this.activeId || !this.conversations.find((c) => c.id === this.activeId)) {
                this.startFresh(false);
            } else {
                const active = this.conversations.find((c) => c.id === this.activeId);
                this.messages = (active.messages && active.messages.length)
                    ? active.messages
                    : welcomeMessages();
            }
        },

        loadStore() {
            try {
                const raw = localStorage.getItem(storageKey);
                if (!raw) return;
                const data = JSON.parse(raw);
                this.conversations = Array.isArray(data.conversations) ? data.conversations : [];
                this.activeId = data.activeId || null;
            } catch (e) {
                this.conversations = [];
                this.activeId = null;
            }
        },

        persist() {
            const hasUserMsg = this.messages.some((m) => m.role === 'user');
            if (this.activeId && hasUserMsg) {
                const title = this.deriveTitle(this.messages);
                const existing = this.conversations.find((c) => c.id === this.activeId);
                const entry = {
                    id: this.activeId,
                    title,
                    updatedAt: Date.now(),
                    messages: this.messages.map((m) => ({
                        role: m.role,
                        text: m.text,
                        links: m.links || [],
                    })),
                };
                if (existing) {
                    Object.assign(existing, entry);
                } else {
                    this.conversations.unshift(entry);
                }
                this.conversations.sort((a, b) => (b.updatedAt || 0) - (a.updatedAt || 0));
                if (this.conversations.length > MAX_CONVERSATIONS) {
                    this.conversations = this.conversations.slice(0, MAX_CONVERSATIONS);
                }
            }
            try {
                localStorage.setItem(storageKey, JSON.stringify({
                    activeId: this.activeId,
                    conversations: this.conversations,
                }));
            } catch (e) {
                // ignore quota errors
            }
        },

        deriveTitle(messages) {
            const firstUser = messages.find((m) => m.role === 'user' && (m.text || '').trim());
            if (!firstUser) return 'New conversation';
            const t = firstUser.text.trim().replace(/\s+/g, ' ');
            return t.length > 48 ? t.slice(0, 48) + '…' : t;
        },

        formatWhen(ts) {
            if (!ts) return '';
            const d = new Date(ts);
            const now = new Date();
            const sameDay = d.toDateString() === now.toDateString();
            const time = d.toLocaleTimeString([], { hour: 'numeric', minute: '2-digit' });
            if (sameDay) return 'Today · ' + time;
            const yesterday = new Date(now);
            yesterday.setDate(now.getDate() - 1);
            if (d.toDateString() === yesterday.toDateString()) return 'Yesterday · ' + time;
            return d.toLocaleDateString([], { month: 'short', day: 'numeric', year: 'numeric' }) + ' · ' + time;
        },

        startFresh(persistCurrent) {
            if (persistCurrent) this.persist();
            this.activeId = uid();
            this.messages = welcomeMessages();
            this.draft = '';
            this.showHistory = false;
            this.persist();
        },

        clearChat() {
            this.startFresh(true);
            this.scrollBottom();
        },

        loadConversation(id) {
            const conv = this.conversations.find((c) => c.id === id);
            if (!conv) return;
            this.persist();
            this.activeId = conv.id;
            this.messages = (conv.messages && conv.messages.length)
                ? conv.messages.map((m) => ({ role: m.role, text: m.text, links: m.links || [] }))
                : welcomeMessages();
            this.showHistory = false;
            this.persist();
            this.scrollBottom();
        },

        deleteConversation(id) {
            this.conversations = this.conversations.filter((c) => c.id !== id);
            if (this.activeId === id) {
                this.activeId = uid();
                this.messages = welcomeMessages();
            }
            this.persist();
        },

        ask(text) {
            this.draft = text;
            this.send();
        },

        historyPayload() {
            return this.messages
                .filter((m) => m.role === 'user' || m.role === 'assistant')
                .slice(1) // skip welcome bubble
                .slice(-16)
                .map((m) => ({ role: m.role, text: m.text }));
        },

        scrollBottom() {
            this.$nextTick(() => {
                const el = this.$refs.thread;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },

        async send() {
            const message = (this.draft || '').trim();
            if (!message || this.busy) return;
            if (!this.activeId) this.activeId = uid();
            const history = this.historyPayload();
            this.messages.push({ role: 'user', text: message, links: [] });
            this.draft = '';
            this.busy = true;
            this.persist();
            this.scrollBottom();
            try {
                const res = await fetch(this.endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        message,
                        history,
                        page: window.location.pathname + window.location.search,
                    }),
                });
                const data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || 'Something went wrong');
                }
                if (typeof data.llm === 'boolean') {
                    this.llm = data.llm;
                }
                this.messages.push({
                    role: 'assistant',
                    text: data.reply || 'Sorry, I could not answer that.',
                    links: data.links || [],
                });
            } catch (e) {
                this.messages.push({
                    role: 'assistant',
                    text: 'I lost the connection for a moment. Please send that again.',
                    links: [],
                });
            } finally {
                this.busy = false;
                this.persist();
                this.scrollBottom();
            }
        },
    };
}
</script>
