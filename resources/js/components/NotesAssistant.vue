<template>
    <div class="flex h-screen flex-col overflow-hidden bg-slate-950 text-slate-100">
        <!-- header -->
        <header class="flex shrink-0 items-center justify-between border-b border-slate-800/80 bg-slate-950/90 px-4 py-3 backdrop-blur md:px-6">
            <div class="flex items-center gap-3">
                <button
                    type="button"
                    class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white"
                    @click="$emit('exit')"
                >
                    Back
                </button>
                <div>
                    <h1 class="text-sm font-bold text-white">Notes AI Assistant</h1>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-500">AI-powered notes reader</p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <span
                    v-if="puterReady"
                    class="inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-[11px] font-semibold"
                    :class="isSignedIn ? 'bg-emerald-900/40 text-emerald-300' : 'bg-amber-900/40 text-amber-300'"
                >
                    <span class="h-1.5 w-1.5 rounded-full" :class="isSignedIn ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                    {{ isSignedIn ? 'Puter connected' : 'Not signed in' }}
                </span>

                <button
                    v-if="!isSignedIn && puterReady"
                    type="button"
                    class="rounded-lg bg-indigo-600 px-3 py-2 text-xs font-bold text-white transition hover:bg-indigo-500"
                    @click="signInToPuter"
                >
                    Sign in to Puter
                </button>
                <select
                    v-if="isSignedIn && filteredModels.length > 0"
                    v-model="currentModelId"
                    class="max-w-[180px] rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-2 text-xs text-slate-200 outline-none transition focus:border-indigo-500"
                    title="AI model"
                >
                    <option
                        v-for="m in filteredModels"
                        :key="m.id"
                        :value="m.id"
                    >{{ m.name || m.id }}</option>
                </select>

                <button
                    v-if="isSignedIn"
                    type="button"
                    class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-xs font-semibold text-slate-400 transition hover:text-white"
                    @click="signOutOfPuter"
                >
                    Disconnect
                </button>
            </div>
        </header>

        <div class="relative flex min-h-0 flex-1">
            <!-- sidebar: presentation list -->
            <aside
                class="relative flex shrink-0 flex-col border-r border-slate-800 bg-slate-950/95 transition-all duration-300"
                :class="sidebarOpen ? 'w-72' : 'w-0 border-r-0'"
            >
                <button
                    type="button"
                    class="absolute -right-3 top-10 z-20 flex h-12 w-6 items-center justify-center rounded-r-lg border border-l-0 border-slate-800 bg-slate-950 text-slate-400 shadow-lg transition hover:text-white"
                    @click="sidebarOpen = !sidebarOpen"
                >
                    {{ sidebarOpen ? '<' : '>' }}
                </button>

                <div v-if="sidebarOpen" class="no-scrollbar flex h-full flex-col gap-3 overflow-y-auto p-4">
                    <h2 class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">Presentations with notes</h2>

                    <div v-if="loadingPresentations" class="py-4 text-center text-xs text-slate-500">Loading...</div>
                    <div v-else-if="presentationsWithNotes.length === 0" class="py-4 text-center text-xs text-slate-500">
                        No saved notes yet.<br>Present a deck and save the transcript first.
                    </div>

                    <button
                        v-for="p in presentationsWithNotes"
                        :key="p.id"
                        type="button"
                        class="w-full rounded-xl border p-3 text-left text-xs transition"
                        :class="selectedPresentation?.id === p.id ? 'border-indigo-600 bg-indigo-900/30' : 'border-slate-800 bg-slate-950 hover:border-slate-700'"
                        @click="selectPresentation(p)"
                    >
                        <p class="truncate font-semibold text-white">{{ p.title }}</p>
                        <p class="mt-1 truncate text-slate-400">{{ p.original_name }}</p>
                        <p class="mt-1 text-[10px] text-slate-500">{{ formatDate(p.created_at) }}</p>
                    </button>
                </div>
            </aside>

            <!-- main: chat area -->
            <main class="flex min-w-0 flex-1 flex-col overflow-hidden">
                <!-- notes preview -->
                <div
                    v-if="notesContent !== null"
                    class="shrink-0 border-b border-slate-800 bg-slate-950/60"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between px-5 py-3 text-xs font-semibold text-slate-400 transition hover:text-slate-200"
                        @click="showRawNotes = !showRawNotes"
                    >
                        <span>Raw notes &mdash; {{ selectedPresentation?.title }}</span>
                        <span>{{ showRawNotes ? 'Collapse' : 'Expand' }}</span>
                    </button>
                    <pre
                        v-if="showRawNotes"
                        class="no-scrollbar max-h-48 overflow-y-auto px-5 pb-3 font-mono text-[11px] leading-relaxed text-slate-500"
                    >{{ notesContent }}</pre>
                </div>

                <!-- messages -->
                <div ref="messagesRef" class="no-scrollbar flex-1 overflow-y-auto px-5 py-5">
                    <div v-if="messages.length === 0" class="flex flex-col items-center justify-center py-16 text-center">
                        <div class="mb-4 text-4xl">📝</div>
                        <p class="text-lg font-bold text-white">Notes AI Assistant</p>
                        <p class="mt-2 max-w-md text-sm text-slate-400">
                            Select a presentation with saved notes from the sidebar, then ask questions
                            about the content. The AI will answer based on the transcript.
                        </p>
                        <div v-if="!isSignedIn && puterReady" class="mt-6">
                            <button
                                type="button"
                                class="rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white transition hover:bg-indigo-500"
                                @click="signInToPuter"
                            >
                                Sign in to Puter to start chatting
                            </button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div v-for="msg in messages" :key="msg.id" :class="msg.role === 'user' ? 'flex justify-end' : 'flex justify-start'">
                            <div
                                v-if="msg.role === 'user'"
                                class="max-w-[75%] rounded-2xl bg-indigo-600 px-4 py-3 text-sm leading-relaxed text-white"
                            >{{ msg.content }}</div>

                            <!-- assistant bubble with copy button -->
                            <div v-else class="group relative max-w-[75%]">
                                <div
                                    class="markdown-body rounded-2xl border border-slate-800 bg-slate-900 px-4 pb-3 pt-8 text-sm leading-relaxed text-slate-200"
                                    v-html="renderMarkdown(msg.content)"
                                ></div>
                                <button
                                    type="button"
                                    class="absolute right-2 top-2 flex items-center gap-1.5 rounded-lg border px-2 py-1 text-[10px] font-semibold transition-all"
                                    :class="copiedId === msg.id
                                        ? 'border-emerald-700 bg-emerald-900/50 text-emerald-300'
                                        : 'border-slate-700 bg-slate-800/80 text-slate-400 opacity-0 hover:border-slate-600 hover:text-slate-200 group-hover:opacity-100'"
                                    @click="copyMessage(msg)"
                                >
                                    <!-- copy icon -->
                                    <svg v-if="copiedId !== msg.id" xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                    <!-- check icon -->
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                    {{ copiedId === msg.id ? 'Copied!' : 'Copy' }}
                                </button>
                            </div>
                        </div>

                        <div v-if="isStreaming" class="flex justify-start">
                            <div class="markdown-body max-w-[75%] rounded-2xl border border-slate-800 bg-slate-900 px-4 py-3 text-sm leading-relaxed text-slate-200">
                                <span v-if="streamBuffer === ''" class="inline-flex items-center gap-1">
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-500" style="animation-delay:0ms"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-500" style="animation-delay:150ms"></span>
                                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-slate-500" style="animation-delay:300ms"></span>
                                </span>
                                <span v-html="renderMarkdown(streamBuffer)"></span>
                            </div>
                        </div>

                        <div v-if="error" class="rounded-xl border border-red-900/50 bg-red-950/40 p-3 text-xs text-red-200">
                            {{ error }}
                        </div>
                    </div>
                </div>

                <!-- input -->
                <div class="shrink-0 border-t border-slate-800 bg-slate-950/90 px-5 py-4">
                    <div class="flex gap-3">
                        <input
                            ref="inputRef"
                            v-model="userInput"
                            type="text"
                            placeholder="Ask about the presentation notes..."
                            class="min-w-0 flex-1 rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-600 focus:border-indigo-500"
                            :disabled="!selectedPresentation || isStreaming || !isSignedIn"
                            @keydown.enter="sendMessage"
                        />
                        <button
                            type="button"
                            class="rounded-xl px-4 py-3 text-sm font-bold transition"
                            :class="canSend ? 'bg-indigo-600 text-white hover:bg-indigo-500' : 'bg-slate-800 text-slate-600'"
                            :disabled="!canSend"
                            @click="sendMessage"
                        >
                            Send
                        </button>
                    </div>
                    <p v-if="!isSignedIn && puterReady" class="mt-2 text-xs text-slate-500">
                        Sign in to Puter above to enable AI chat.
                    </p>
                    <p v-else-if="!selectedPresentation" class="mt-2 text-xs text-slate-500">
                        Select a presentation from the sidebar.
                    </p>
                </div>
            </main>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { marked } from 'marked';

// Configure marked once: GFM + line breaks so the AI's output renders properly
marked.setOptions({
    gfm: true,
    breaks: true,
});

function renderMarkdown(text) {
    if (!text) return '';
    try {
        return marked.parse(text, { async: false });
    } catch {
        return text;
    }
}

const emit = defineEmits(['exit']);

// ── puter.js auth state ──────────────────────────────────────
const puterReady = ref(false);
const isSignedIn = ref(false);

async function initPuter() {
    try {
        const mod = await import('@heyputer/puter.js');
        const puter = mod.default || mod.puter || mod;
        window.__puter = puter;

        if (typeof puter?.auth?.isSignedIn === 'function') {
            isSignedIn.value = await puter.auth.isSignedIn();
        }

        puterReady.value = true;
    } catch (err) {
        console.error('Puter.js failed to load', err);
        puterReady.value = false;
    }
}

async function signInToPuter() {
    const puter = window.__puter;
    if (!puter) return;

    try {
        await puter.auth.signIn();
        isSignedIn.value = true;
    } catch (err) {
        error.value = 'Puter sign-in failed: ' + (err.message || err);
    }
}

async function signOutOfPuter() {
    const puter = window.__puter;
    if (!puter) return;

    try {
        await puter.auth.signOut();
        isSignedIn.value = false;
    } catch (err) {
        // ignore
    }
}

// ── model list ────────────────────────────────────────────────
const MODEL_KEYWORDS = ['deepseek', 'gpt', 'qwen', 'gemma'];

const availableModels = ref([]);
const currentModelId = ref('gpt-5.4-nano');
const modelsError = ref('');

const filteredModels = computed(() =>
    availableModels.value.filter((m) =>
        MODEL_KEYWORDS.some((kw) => m.id.toLowerCase().includes(kw))
    )
);

async function loadModels() {
    const puter = window.__puter;
    if (!puter) return;

    try {
        const models = await puter.ai.listModels();
        availableModels.value = models;

        // Reset to first filtered model if current isn't in filtered list
        const inFiltered = filteredModels.value.some((m) => m.id === currentModelId.value);
        if (!inFiltered && filteredModels.value.length > 0) {
            currentModelId.value = filteredModels.value[0].id;
        }
    } catch (err) {
        modelsError.value = 'Failed to load models: ' + (err.message || err);
    }
}

watch(isSignedIn, (signedIn) => {
    if (signedIn) loadModels();
});

// ── presentation list ────────────────────────────────────────
const loadingPresentations = ref(true);
const presentations = ref([]);

const presentationsWithNotes = computed(() =>
    presentations.value.filter((p) => p.notes_url)
);

async function loadPresentations() {
    loadingPresentations.value = true;
    try {
        const { data } = await axios.get('/api/presentations');
        presentations.value = data;
    } catch {
        // ignore
    } finally {
        loadingPresentations.value = false;
    }
}

// ── selection + notes loading ────────────────────────────────
const sidebarOpen = ref(true);
const selectedPresentation = ref(null);
const notesContent = ref(null);
const showRawNotes = ref(false);

async function selectPresentation(p) {
    selectedPresentation.value = p;
    notesContent.value = null;
    showRawNotes.value = false;
    messages.value = [];
    streamBuffer.value = '';
    error.value = '';

    if (!p.notes_url) return;

    try {
        const { data } = await axios.get(p.notes_url, { responseType: 'text' });
        notesContent.value = data;
    } catch {
        error.value = 'Failed to load notes for this presentation.';
    }
}

// ── chat ─────────────────────────────────────────────────────
const messages = ref([]);
const userInput = ref('');
const isStreaming = ref(false);
const streamBuffer = ref('');
const error = ref('');
const messagesRef = ref(null);
const inputRef = ref(null);
const copiedId = ref(null);

const canSend = computed(() =>
    userInput.value.trim() &&
    selectedPresentation.value &&
    !isStreaming.value &&
    isSignedIn.value
);

async function copyMessage(msg) {
    try {
        await navigator.clipboard.writeText(msg.content);
        copiedId.value = msg.id;
        setTimeout(() => {
            if (copiedId.value === msg.id) copiedId.value = null;
        }, 2000);
    } catch {
        // fallback for older browsers
        const el = document.createElement('textarea');
        el.value = msg.content;
        el.style.position = 'fixed';
        el.style.opacity = '0';
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        copiedId.value = msg.id;
        setTimeout(() => {
            if (copiedId.value === msg.id) copiedId.value = null;
        }, 2000);
    }
}

async function sendMessage() {
    const text = userInput.value.trim();
    if (!canSend.value || !text) return;

    const puter = window.__puter;
    if (!puter) {
        error.value = 'Puter.js is not loaded. Refresh and try again.';
        return;
    }

    const userMsg = { id: Date.now().toString(), role: 'user', content: text };
    messages.value.push(userMsg);
    userInput.value = '';
    error.value = '';
    streamBuffer.value = '';
    isStreaming.value = true;

    const chatMessages = [];

    if (notesContent.value) {
        chatMessages.push({
            role: 'system',
            content: [
                'You are a helpful assistant that helps users understand and work with their presentation notes.',
                'The user was presenting and the voice transcript was captured as notes.',
                '',
                `Here are the notes from the presentation "${selectedPresentation.value?.title}":`,
                '---',
                notesContent.value,
                '---',
                '',
                'Answer the user\'s questions based on the notes provided.',
                'If the notes don\'t contain enough information to answer, say so clearly.',
                'Format your responses using Markdown: use **bold** for emphasis, headings for structure,',
                '`code` for any technical terms, and bullet lists where appropriate.',
                'Keep responses concise and well-structured.',
            ].join('\n'),
        });
    }

    for (const msg of messages.value) {
        chatMessages.push({ role: msg.role, content: msg.content });
    }

    let fullText = '';

    try {
        const resp = await puter.ai.chat(chatMessages, {
            model: currentModelId.value,
            stream: true,
        });

        for await (const part of resp) {
            const chunk = part?.text || part?.message?.content || '';
            if (chunk) {
                fullText += chunk;
                streamBuffer.value = fullText;
                scrollToBottom();
            }
        }
    } catch (err) {
        error.value = 'AI request failed: ' + (err.message || err);
    } finally {
        isStreaming.value = false;
        streamBuffer.value = '';

        if (fullText) {
            messages.value.push({
                id: (Date.now() + 1).toString(),
                role: 'assistant',
                content: fullText,
            });
        }

        scrollToBottom();
        nextTick(() => inputRef.value?.focus());
    }
}

function scrollToBottom() {
    nextTick(() => {
        const el = messagesRef.value;
        if (el) el.scrollTop = el.scrollHeight;
    });
}

// ── helpers ──────────────────────────────────────────────────
function formatDate(value) {
    if (!value) return '';
    return new Date(value).toLocaleString();
}

// ── lifecycle ────────────────────────────────────────────────
onMounted(async () => {
    await loadPresentations();
    await initPuter();
});

onBeforeUnmount(() => {
    window.__puter = undefined;
});
</script>

<style scoped>
/* Hide scrollbars on all scrollable regions while keeping scroll behaviour */
.no-scrollbar {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE / Edge legacy */
}
.no-scrollbar::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

/* Markdown body styles for AI responses */
.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3),
.markdown-body :deep(h4) {
    font-weight: 700;
    line-height: 1.3;
    margin-top: 1em;
    margin-bottom: 0.4em;
    color: #f1f5f9; /* slate-100 */
}

.markdown-body :deep(h1) { font-size: 1.15em; }
.markdown-body :deep(h2) { font-size: 1.05em; }
.markdown-body :deep(h3) { font-size: 0.95em; color: #cbd5e1; }
.markdown-body :deep(h4) { font-size: 0.9em;  color: #94a3b8; }

.markdown-body :deep(p) {
    margin-bottom: 0.6em;
    line-height: 1.6;
}

.markdown-body :deep(p:last-child) {
    margin-bottom: 0;
}

.markdown-body :deep(ul),
.markdown-body :deep(ol) {
    margin: 0.5em 0 0.5em 1.25em;
    padding: 0;
}

.markdown-body :deep(ul) { list-style-type: disc; }
.markdown-body :deep(ol) { list-style-type: decimal; }

.markdown-body :deep(li) {
    margin-bottom: 0.25em;
    line-height: 1.55;
}

.markdown-body :deep(li > p) {
    margin: 0;
}

.markdown-body :deep(code) {
    font-family: ui-monospace, 'Cascadia Code', monospace;
    font-size: 0.82em;
    background: #0f172a; /* slate-950 */
    border: 1px solid #1e293b; /* slate-800 */
    border-radius: 4px;
    padding: 0.15em 0.4em;
    color: #a5b4fc; /* indigo-300 */
}

.markdown-body :deep(pre) {
    background: #0f172a;
    border: 1px solid #1e293b;
    border-radius: 10px;
    padding: 0.85em 1em;
    overflow-x: auto;
    margin: 0.6em 0;
}

.markdown-body :deep(pre code) {
    background: none;
    border: none;
    padding: 0;
    color: #94a3b8; /* slate-400 */
    font-size: 0.82em;
}

.markdown-body :deep(blockquote) {
    border-left: 3px solid #4f46e5; /* indigo-600 */
    margin: 0.6em 0;
    padding: 0.4em 0.9em;
    color: #94a3b8;
    font-style: italic;
}

.markdown-body :deep(strong) {
    font-weight: 700;
    color: #f1f5f9;
}

.markdown-body :deep(em) {
    font-style: italic;
    color: #cbd5e1;
}

.markdown-body :deep(hr) {
    border: none;
    border-top: 1px solid #1e293b;
    margin: 0.75em 0;
}

.markdown-body :deep(a) {
    color: #818cf8; /* indigo-400 */
    text-decoration: underline;
    text-underline-offset: 2px;
}

.markdown-body :deep(table) {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82em;
    margin: 0.6em 0;
}

.markdown-body :deep(th),
.markdown-body :deep(td) {
    border: 1px solid #1e293b;
    padding: 0.4em 0.7em;
    text-align: left;
}

.markdown-body :deep(th) {
    background: #0f172a;
    font-weight: 700;
    color: #e2e8f0;
}

.markdown-body :deep(tr:nth-child(even) td) {
    background: #0f172a40;
}

/* First child spacing reset */
.markdown-body :deep(> *:first-child),
.markdown-body :deep(h1:first-child),
.markdown-body :deep(h2:first-child),
.markdown-body :deep(h3:first-child),
.markdown-body :deep(p:first-child) {
    margin-top: 0;
}
</style>
