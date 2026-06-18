<template>
    <div class="flex h-screen flex-col overflow-hidden bg-slate-950 text-slate-100">
        <header class="flex shrink-0 items-center justify-between border-b border-slate-800/80 bg-slate-950/90 px-4 py-3 backdrop-blur md:px-6">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    class="rounded-lg border border-slate-800 bg-slate-900/70 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white"
                    @click="$emit('exit')"
                >
                    Exit
                </button>
                <div class="min-w-0">
                    <h1 class="truncate text-sm font-bold text-white">{{ presentationTitle || 'Presentation' }}</h1>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.25em] text-slate-500">voice-enabled presenter</p>
                </div>
            </div>

            <div class="hidden items-center gap-2 rounded-full border border-slate-800 bg-slate-950 px-3 py-2 text-[11px] text-slate-400 md:flex">
                <span class="h-2 w-2 rounded-full bg-indigo-400"></span>
                Dual-canvas crossfade active
            </div>
        </header>

        <div v-if="mobileNotice" class="shrink-0 border-b border-amber-900/50 bg-amber-950/60 px-4 py-2 text-center text-xs text-amber-200 md:hidden">
            Best viewed on desktop. Touch navigation is supported, but the presenter layout is optimized for larger screens.
        </div>

        <div class="relative flex min-h-0 flex-1 overflow-hidden">
            <!-- voice sidebar -->
            <aside
                class="relative flex shrink-0 flex-col border-r border-slate-800 bg-slate-950/95 transition-all duration-300"
                :class="voiceSidebarOpen ? 'w-80' : 'w-0 border-r-0'"
            >
                <button
                    type="button"
                    class="absolute -right-3 top-10 z-20 flex h-12 w-6 items-center justify-center rounded-r-lg border border-l-0 border-slate-800 bg-slate-950 text-slate-400 shadow-lg transition hover:text-white"
                    @click="voiceSidebarOpen = !voiceSidebarOpen"
                >
                    {{ voiceSidebarOpen ? '<' : '>' }}
                </button>

                <div v-if="voiceSidebarOpen" class="no-scrollbar flex h-full flex-col gap-5 overflow-y-auto p-5">
                    <div class="rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-slate-500">Voice navigation</p>
                                <p class="mt-1 text-sm text-slate-300">Tap the mic, then say next, previous, or exit.</p>
                            </div>
                            <button
                                type="button"
                                class="rounded-xl border px-3 py-2 text-xs font-bold transition"
                                :class="micActive ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-slate-800 bg-slate-950 text-slate-300'"
                                @click="toggleMic"
                            >
                                {{ micActive ? 'Mic on' : 'Mic off' }}
                            </button>
                        </div>

                        <div class="mt-4 flex items-center gap-2 text-xs text-slate-400">
                            <span class="h-2 w-2 rounded-full" :class="micActive ? 'bg-emerald-400' : 'bg-slate-600'"></span>
                            {{ micActive ? 'Listening for commands' : 'Microphone idle' }}
                        </div>

                        <p v-if="micError" class="mt-3 rounded-xl border border-red-900/50 bg-red-950/40 p-3 text-xs text-red-200">
                            {{ micError }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-bold uppercase tracking-[0.25em] text-slate-500">Language</label>
                        <select
                            v-model="language"
                            class="w-full rounded-xl border border-slate-800 bg-slate-950 px-3 py-3 text-sm text-slate-100 outline-none transition focus:border-indigo-500"
                        >
                            <option value="auto">Auto / browser default</option>
                            <option value="en-US">English (United States)</option>
                            <option value="es-ES">Spanish (Spain)</option>
                            <option value="ja-JP">Japanese (Japan)</option>
                            <option value="fr-FR">French (France)</option>
                            <option value="de-DE">German (Germany)</option>
                            <option value="pt-BR">Portuguese (Brazil)</option>
                        </select>
                    </div>

                    <div class="flex items-center justify-between rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <div>
                            <p class="text-sm font-semibold text-white">Wake word Jarvis</p>
                            <p class="text-xs text-slate-500">Require Jarvis before commands</p>
                        </div>
                        <button
                            type="button"
                            class="relative h-7 w-12 rounded-full border transition"
                            :class="wakeWordEnabled ? 'border-indigo-500 bg-indigo-600' : 'border-slate-800 bg-slate-900'"
                            @click="wakeWordEnabled = !wakeWordEnabled"
                        >
                            <span
                                class="absolute top-0.5 h-6 w-6 rounded-full bg-white shadow transition-all"
                                :class="wakeWordEnabled ? 'left-5' : 'left-0.5'"
                            ></span>
                        </button>
                    </div>

                    <div class="flex min-h-0 flex-1 flex-col rounded-2xl border border-slate-800 bg-slate-950/70 p-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-sm font-bold text-white">Live transcript</h2>
                            <button
                                type="button"
                                class="text-xs font-semibold text-indigo-300 transition hover:text-indigo-200"
                                @click="clearTranscript"
                            >
                                Clear words
                            </button>
                        </div>

                        <div class="no-scrollbar mt-3 min-h-0 flex-1 overflow-y-auto rounded-xl bg-slate-950/80 p-3 font-mono text-[11px] text-slate-400">
                            <p v-if="liveTranscript" class="text-slate-100">
                                {{ liveTranscript }}
                            </p>
                            <p v-else class="italic text-slate-600">No speech captured yet.</p>

                            <div v-if="transcriptHistory.length" class="mt-4 border-t border-slate-900 pt-3">
                                <p class="mb-2 text-[10px] font-bold uppercase tracking-[0.25em] text-slate-500">Recent commands</p>
                                <ul class="space-y-1">
                                    <li v-for="(item, index) in transcriptHistory" :key="index" class="truncate text-[10px] text-slate-500">
                                        {{ item }}
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <div class="mt-3 flex items-center gap-2 border-t border-slate-800 pt-3">
                            <button
                                type="button"
                                class="flex-1 rounded-xl px-3 py-2 text-xs font-bold transition"
                                :class="
                                    notesSaved
                                        ? 'border border-emerald-700 bg-emerald-900/40 text-emerald-300'
                                        : 'border border-indigo-800 bg-indigo-900/40 text-indigo-300 hover:bg-indigo-900/70'
                                "
                                :disabled="sessionTranscript.length === 0 || isSavingNotes"
                                @click="saveTranscriptNotes"
                            >
                                <span v-if="isSavingNotes" class="inline-flex items-center gap-2">
                                    <span class="h-3 w-3 animate-spin rounded-full border border-indigo-400 border-t-transparent"></span>
                                    Saving...
                                </span>
                                <span v-else-if="notesSaved">Notes saved ✓</span>
                                <span v-else>Save transcript as notes ({{ sessionTranscript.length }})</span>
                            </button>

                            <a
                                v-if="notesSaved && notesUrl"
                                :href="notesUrl"
                                target="_blank"
                                class="rounded-xl border border-slate-800 bg-slate-900 px-3 py-2 text-xs font-semibold text-slate-400 transition hover:text-white"
                            >
                                View
                            </a>
                        </div>
                    </div>
                </div>
            </aside>

            <!-- stage -->
            <main class="relative flex min-w-0 flex-1 flex-col items-center justify-center overflow-hidden bg-slate-950 px-4 py-6 md:px-6">
                <!-- ref is on the inner box so getBoundingClientRect gives the true drawable area -->
                <div
                    ref="stageBoxRef"
                    class="relative flex min-h-[320px] w-full max-w-6xl flex-1 items-center justify-center overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/60 p-3 shadow-2xl shadow-slate-950/60"
                >
                    <div v-if="isLoading" class="absolute inset-0 z-20 flex flex-col items-center justify-center gap-3 rounded-3xl bg-slate-950/90 text-slate-300">
                        <div class="h-10 w-10 animate-spin rounded-full border-2 border-slate-700 border-t-indigo-400"></div>
                        <p class="text-sm">Loading presentation...</p>
                    </div>

                    <div v-else-if="loadError" class="absolute inset-0 z-20 flex items-center justify-center rounded-3xl bg-slate-950/95 p-6 text-center">
                        <div class="max-w-md rounded-2xl border border-red-900/50 bg-red-950/40 p-5 text-red-100">
                            <p class="font-bold">Unable to open presentation</p>
                            <p class="mt-2 text-sm text-red-200">{{ loadError }}</p>
                        </div>
                    </div>

                    <!-- canvases are absolutely centred; max-width/height prevents overflow -->
                    <canvas
                        ref="canvasOne"
                        class="absolute inset-0 m-auto max-h-full max-w-full transition-opacity duration-500"
                        :class="activeCanvasId === 1 ? 'opacity-100' : 'opacity-0'"
                    ></canvas>
                    <canvas
                        ref="canvasTwo"
                        class="absolute inset-0 m-auto max-h-full max-w-full transition-opacity duration-500"
                        :class="activeCanvasId === 2 ? 'opacity-100' : 'opacity-0'"
                    ></canvas>

                    <div
                        v-if="voiceBadge"
                        class="absolute left-1/2 top-6 z-30 -translate-x-1/2 rounded-full border border-indigo-400/30 bg-indigo-600/95 px-5 py-3 text-sm font-bold text-white shadow-2xl shadow-indigo-950/50"
                    >
                        {{ voiceBadge }}
                    </div>
                </div>
            </main>

            <!-- thumbnail strip -->
            <aside
                class="relative flex shrink-0 flex-col border-l border-slate-800 bg-slate-950/95 transition-all duration-300"
                :class="thumbnailStripOpen ? 'w-64' : 'w-0 border-l-0'"
            >
                <button
                    type="button"
                    class="absolute -left-3 top-10 z-20 flex h-12 w-6 items-center justify-center rounded-l-lg border border-r-0 border-slate-800 bg-slate-950 text-slate-400 shadow-lg transition hover:text-white"
                    @click="thumbnailStripOpen = !thumbnailStripOpen"
                >
                    {{ thumbnailStripOpen ? '>' : '<' }}
                </button>

                <template v-if="thumbnailStripOpen">
                    <!-- sticky header -->
                    <div class="flex shrink-0 items-center justify-between px-4 py-3">
                        <h2 class="text-sm font-bold text-white">Slides</h2>
                        <span class="text-[11px] text-slate-500">{{ totalPages }} total</span>
                    </div>

                    <!-- scrollable thumbnails -->
                    <div class="no-scrollbar flex-1 overflow-y-auto px-4 pb-4">
                        <div class="flex flex-col gap-3">
                            <button
                                v-for="pageNumber in totalPages"
                                :key="pageNumber"
                                type="button"
                                class="w-full overflow-hidden rounded-2xl border transition"
                                :class="currentPage === pageNumber ? 'border-indigo-500 ring-2 ring-indigo-500/30' : 'border-slate-800 hover:border-slate-700'"
                                @click="jumpToPage(pageNumber)"
                            >
                                <div class="relative aspect-video w-full bg-slate-950">
                                    <canvas
                                        :ref="(el) => setThumbCanvas(el, pageNumber - 1)"
                                        class="absolute inset-0 h-full w-full"
                                    ></canvas>
                                    <span class="absolute bottom-2 right-2 z-10 rounded-md bg-slate-950/80 px-2 py-1 text-[10px] font-bold text-slate-200">
                                        {{ pageNumber }}
                                    </span>
                                </div>
                            </button>
                        </div>
                    </div>
                </template>
            </aside>
        </div>

        <footer class="flex shrink-0 flex-col gap-3 border-t border-slate-800/80 bg-slate-950/90 px-4 py-4 md:flex-row md:items-center md:justify-between md:px-6">
            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="currentPage <= 1 || isRendering"
                    @click="previousPage"
                >
                    Previous
                </button>
                <button
                    type="button"
                    class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white disabled:cursor-not-allowed disabled:opacity-50"
                    :disabled="currentPage >= totalPages || isRendering"
                    @click="nextPage"
                >
                    Next
                </button>
            </div>

            <div class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2 text-center text-sm font-semibold text-white">
                Slide {{ currentPage }} / {{ totalPages }}
            </div>

            <div class="group relative self-start md:self-auto">
                <button type="button" class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2 text-sm font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white">
                    Keyboard shortcuts
                </button>
                <div class="pointer-events-none absolute bottom-full right-0 mb-3 w-64 rounded-2xl border border-slate-800 bg-slate-950 p-4 text-left text-xs text-slate-300 opacity-0 shadow-2xl transition group-hover:opacity-100">
                    <ul class="space-y-2">
                        <li class="flex justify-between"><span>Next</span><kbd class="rounded bg-slate-900 px-2 py-1 text-[10px]">Right / Space</kbd></li>
                        <li class="flex justify-between"><span>Previous</span><kbd class="rounded bg-slate-900 px-2 py-1 text-[10px]">Left</kbd></li>
                        <li class="flex justify-between"><span>Exit</span><kbd class="rounded bg-slate-900 px-2 py-1 text-[10px]">Esc</kbd></li>
                        <li class="flex justify-between"><span>Mic</span><kbd class="rounded bg-slate-900 px-2 py-1 text-[10px]">M</kbd></li>
                    </ul>
                </div>
            </div>
        </footer>
    </div>
</template>

<script setup>
import axios from 'axios';
import { markRaw, nextTick, onBeforeUnmount, onMounted, ref, shallowRef, watch } from 'vue';
import { loadPDF, renderPDFPage, renderPDFThumbnail } from '../services/PDFRenderingService';
import { VoiceRecognitionService } from '../services/VoiceRecognitionService';

const props = defineProps({
    presentationId: {
        type: String,
        required: true,
    },
});

const emit = defineEmits(['exit']);

const canvasOne    = ref(null);
const canvasTwo    = ref(null);
const stageBoxRef  = ref(null);   // inner stage box, not the outer <main>
const thumbCanvasRefs = ref([]);
const pdfDoc       = shallowRef(null);
const presentationTitle = ref('');
const currentPage  = ref(1);
const totalPages   = ref(1);
const isLoading    = ref(true);
const loadError    = ref('');
const isRendering  = ref(false);
const activeCanvasId = ref(1);
const voiceSidebarOpen   = ref(true);
const thumbnailStripOpen = ref(true);
const micActive    = ref(false);
const micError     = ref('');
const language     = ref('en-US');
const wakeWordEnabled = ref(false);
const liveTranscript  = ref('');
const transcriptHistory = ref([]);
const sessionTranscript = ref([]);
const isSavingNotes = ref(false);
const notesSaved   = ref(false);
const notesUrl     = ref('');
const voiceBadge   = ref('');
const mobileNotice = ref(window.innerWidth < 768);

let voiceService = null;
let feedbackTimer = null;
let transcriptTimer = null;
let renderToken = 0;
let currentPageRenderChain = Promise.resolve();
let thumbnailRenderChain   = Promise.resolve();

function setThumbCanvas(el, index) {
    if (el) thumbCanvasRefs.value[index] = el;
}

function setBadge(message) {
    voiceBadge.value = message;
    clearTimeout(feedbackTimer);
    feedbackTimer = setTimeout(() => { voiceBadge.value = ''; }, 1500);
}

function clearTranscript() {
    liveTranscript.value = '';
    transcriptHistory.value = [];
    sessionTranscript.value = [];
    notesSaved.value = false;
    notesUrl.value = '';
}

function handleVoiceCommand(command, label) {
    if (command === 'next')     { nextPage();     setBadge(`-> ${label}`); return; }
    if (command === 'previous') { previousPage(); setBadge(`<- ${label}`); return; }
    if (command === 'exit')     { setBadge(`X ${label}`); setTimeout(() => emit('exit'), 450); }
}

function createVoiceService() {
    if (!voiceService) {
        voiceService = new VoiceRecognitionService({
            language: language.value,
            wakeWordEnabled: wakeWordEnabled.value,
            onCommandRecognized: (command, label) => handleVoiceCommand(command, label),
            onTranscriptChange: (text, isFinal) => {
                liveTranscript.value = text;
                clearTimeout(transcriptTimer);
                transcriptTimer = setTimeout(() => { liveTranscript.value = ''; }, 4000);
                if (isFinal && text.trim()) {
                    const clean = text.trim();
                    transcriptHistory.value = [clean, ...transcriptHistory.value].slice(0, 6);
                    sessionTranscript.value.push({ text: clean, slideNumber: currentPage.value, timestamp: new Date().toISOString() });
                }
            },
            onStateChange: (active, error) => { micActive.value = active; micError.value = error || ''; },
        });
        return;
    }
    voiceService.updateConfig(language.value, wakeWordEnabled.value);
}

function toggleMic() {
    createVoiceService();
    if (micActive.value) { voiceService.stop(); return; }
    voiceService.start();
}

async function saveTranscriptNotes() {
    if (sessionTranscript.value.length === 0) return;
    isSavingNotes.value = true;
    try {
        const { data } = await axios.post(`/api/presentations/${props.presentationId}/notes`, { notes: sessionTranscript.value });
        notesSaved.value = true;
        notesUrl.value = data.notes_url;
    } catch (error) {
        console.error('Failed to save notes', error);
    } finally {
        isSavingNotes.value = false;
    }
}

function nextPage()     { currentPage.value = Math.min(totalPages.value, currentPage.value + 1); }
function previousPage() { currentPage.value = Math.max(1, currentPage.value - 1); }
function jumpToPage(n)  { currentPage.value = Math.min(Math.max(n, 1), totalPages.value); }

async function loadPresentation() {
    isLoading.value = true;
    loadError.value = '';
    try {
        const { data } = await axios.get(`/api/presentations/${props.presentationId}`);
        presentationTitle.value = data.title;

        const doc = await loadPDF(`/api/presentations/${props.presentationId}/pdf`);
        pdfDoc.value = markRaw(doc);
        totalPages.value = doc.numPages;
        currentPage.value = 1;

        await nextTick();
        await renderThumbnails();
    } catch (error) {
        loadError.value = error?.response?.data?.error || error?.message || 'Failed to load presentation.';
    } finally {
        isLoading.value = false;
    }
}

async function renderThumbnails() {
    thumbnailRenderChain = thumbnailRenderChain.then(async () => {
        if (!pdfDoc.value || !thumbnailStripOpen.value) return;
        await nextTick();
        // Use the aside width (256px) minus padding (2 × 16px) for accurate sizing
        const thumbWidth = 256 - 32;
        for (let index = 0; index < totalPages.value; index += 1) {
            const canvas = thumbCanvasRefs.value[index];
            if (canvas) await renderPDFThumbnail(pdfDoc.value, index + 1, canvas, thumbWidth);
        }
    }).catch((error) => {
        if (error?.name !== 'RenderingCancelledException') console.error('Unable to render thumbnails', error);
    });
    return thumbnailRenderChain;
}

async function renderCurrentPage() {
    if (!pdfDoc.value) return;

    const token = ++renderToken;
    currentPageRenderChain = currentPageRenderChain.then(async () => {
        await nextTick();

        const targetCanvas = activeCanvasId.value === 1 ? canvasTwo.value : canvasOne.value;
        if (!pdfDoc.value || !targetCanvas) return;

        isRendering.value = true;
        try {
            // Measure the inner stage box — the true drawable area
            const box = stageBoxRef.value?.getBoundingClientRect();
            // Subtract padding (p-3 = 12px each side)
            const availableWidth  = Math.max(320, (box?.width  ?? 800) - 24);
            const availableHeight = Math.max(240, (box?.height ?? 600) - 24);

            if (token !== renderToken) return;

            await renderPDFPage(pdfDoc.value, currentPage.value, targetCanvas, availableWidth, availableHeight);

            if (token === renderToken) {
                activeCanvasId.value = targetCanvas === canvasOne.value ? 1 : 2;
            }
        } catch (error) {
            if (error?.name === 'RenderingCancelledException') return;
            console.error('Unable to render page', error);
        } finally {
            if (token === renderToken) isRendering.value = false;
        }
    }).catch((error) => {
        if (error?.name !== 'RenderingCancelledException') console.error('Unable to render page', error);
    });
    return currentPageRenderChain;
}

function handleKeydown(event) {
    const tagName = event.target?.tagName?.toLowerCase();
    if (['input', 'textarea', 'select'].includes(tagName)) return;

    if (event.key === 'ArrowRight' || event.code === 'Space') { event.preventDefault(); nextPage();     return; }
    if (event.key === 'ArrowLeft')                             { event.preventDefault(); previousPage(); return; }
    if (event.key === 'Escape')                                { event.preventDefault(); emit('exit');   return; }
    if (event.key === 'm' || event.key === 'M')                { event.preventDefault(); toggleMic();   }
}

function handleResize() {
    mobileNotice.value = window.innerWidth < 768;
    renderCurrentPage();
}

watch(language,       () => { if (voiceService) voiceService.updateConfig(language.value, wakeWordEnabled.value); });
watch(wakeWordEnabled,() => { if (voiceService) voiceService.updateConfig(language.value, wakeWordEnabled.value); });
watch([pdfDoc, currentPage], () => { renderCurrentPage(); });
watch(thumbnailStripOpen,    () => { renderThumbnails(); renderCurrentPage(); });
watch(totalPages,            () => { renderThumbnails(); });
watch([voiceSidebarOpen, thumbnailStripOpen], () => { renderCurrentPage(); });

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
    window.addEventListener('resize', handleResize);
    loadPresentation();
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeydown);
    window.removeEventListener('resize', handleResize);
    clearTimeout(feedbackTimer);
    clearTimeout(transcriptTimer);
    if (voiceService) voiceService.destroy();
});
</script>

<style scoped>
.no-scrollbar {
    scrollbar-width: none;
    -ms-overflow-style: none;
}
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
</style>
