<template>
    <div class="relative min-h-screen overflow-hidden px-4 py-6 text-slate-100 sm:px-6 lg:px-8">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-gradient-to-b from-indigo-950/30 to-transparent"></div>
        <div class="pointer-events-none absolute right-0 top-24 h-56 w-56 rounded-full bg-violet-600/10 blur-3xl"></div>

        <div class="mx-auto flex w-full max-w-[90vw] flex-col gap-6">
            <header class="flex flex-col gap-4 border-b border-slate-800/70 pb-5 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.35em] text-indigo-300">ADET Presenter</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight text-white sm:text-4xl">Upload, present, and control slides by voice</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-400">
                        Upload PowerPoint decks, wait for conversion, then present with keyboard and voice navigation.
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <button
                        type="button"
                        class="rounded-xl border border-indigo-800 bg-indigo-950/40 px-4 py-2 text-xs font-bold text-indigo-300 shadow-lg shadow-slate-950/40 transition hover:border-indigo-700 hover:text-indigo-200"
                        @click="$emit('open-notes')"
                    >
                        Notes AI
                    </button>
                    <div class="inline-flex items-center gap-2 rounded-full border border-slate-800 bg-slate-950/80 px-4 py-2 text-xs text-slate-300">
                        <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                        Laravel + Vue
                    </div>
                </div>
            </header>

            <div class="grid gap-6 lg:grid-cols-[380px_minmax(0,1fr)]">
                <section class="panel p-5 sm:p-6">
                    <div class="mb-5 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-lg font-bold text-white">Upload presentation</h2>
                            <p class="text-sm text-slate-400">PPT, PPTX, and PDF, up to 50MB.</p>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white"
                            @click="refreshLibrary"
                        >
                            Refresh
                        </button>
                    </div>

                    <form class="space-y-4" @submit.prevent="submitUpload">
                        <div
                            class="rounded-2xl border-2 border-dashed p-5 text-center transition"
                            :class="isDragging ? 'border-indigo-400 bg-indigo-500/10' : 'border-slate-800 bg-slate-950/40 hover:border-slate-700'"
                            @dragenter.prevent="isDragging = true"
                            @dragover.prevent="isDragging = true"
                            @dragleave.prevent="isDragging = false"
                            @drop.prevent="handleDrop"
                            @click="openFilePicker"
                        >
                            <input
                                ref="fileInput"
                                type="file"
                                class="hidden"
                                accept=".ppt,.pptx,.pdf"
                                @change="handleFileInput"
                            >

                            <div v-if="selectedFile" class="space-y-2">
                                <p class="text-sm font-semibold text-white">{{ selectedFile.name }}</p>
                                <p class="text-xs text-slate-400">{{ formattedFileSize }}. Click to replace.</p>
                            </div>
                            <div v-else>
                                <p class="text-sm font-semibold text-slate-100">Drag and drop your deck here</p>
                                <p class="mt-1 text-xs text-slate-500">Or click to browse files</p>
                            </div>
                        </div>

                        <div>
                            <label for="presentation-title" class="mb-2 block text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">
                                Optional title
                            </label>
                            <input
                                id="presentation-title"
                                v-model="title"
                                type="text"
                                class="w-full rounded-xl border border-slate-800 bg-slate-950 px-4 py-3 text-sm text-slate-100 outline-none transition placeholder:text-slate-600 focus:border-indigo-500"
                                placeholder="Auto-fill from filename"
                            >
                        </div>

                        <button
                            type="submit"
                            class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white transition hover:bg-indigo-500 disabled:cursor-not-allowed disabled:bg-slate-800 disabled:text-slate-500"
                            :disabled="!selectedFile || isUploading"
                        >
                            {{ isUploading ? 'Uploading...' : 'Upload presentation' }}
                        </button>

                        <div v-if="isUploading" class="space-y-2">
                            <div class="flex items-center justify-between text-xs text-slate-400">
                                <span>Processing deck</span>
                                <span>{{ progress }}%</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-slate-900">
                                <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 transition-all duration-300" :style="{ width: `${progress}%` }"></div>
                            </div>
                        </div>

                        <div v-if="uploadError" class="rounded-xl border border-red-900/50 bg-red-950/40 p-3 text-sm text-red-200">
                            {{ uploadError }}
                        </div>
                    </form>

                    <div class="mt-6 rounded-2xl border border-slate-800 bg-slate-950/60 p-4">
                        <h3 class="text-xs font-bold uppercase tracking-[0.25em] text-slate-400">Core features</h3>
                        <ul class="mt-3 space-y-2 text-sm text-slate-300">
                            <li>- Upload PPT, PPTX, or PDF files and present them</li>
                            <li>- Voice commands for next, previous, and exit</li>
                            <li>- Keyboard shortcuts for fast navigation</li>
                        </ul>
                    </div>
                </section>

                <section class="panel flex flex-col">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800/80 px-5 py-4">
                        <div class="flex items-baseline gap-2">
                            <h2 class="text-xl font-bold text-white">Presentation library</h2>
                            <span class="text-sm text-slate-400">
                                {{ filteredPresentations.length }}{{ typeFilter !== 'all' || searchQuery ? ` / ${presentations.length}` : '' }} saved deck(s)
                            </span>
                        </div>
                        <button
                            type="button"
                            class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-2 text-xs font-semibold text-slate-300 transition hover:border-slate-700 hover:text-white"
                            @click="refreshLibrary"
                        >
                            Refresh
                        </button>
                    </div>

                    <div class="flex items-center gap-2 border-b border-slate-800/80 px-5 py-3">
                        <div class="relative flex-1">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z"/>
                            </svg>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Search decks..."
                                class="w-full rounded-lg border border-slate-800 bg-slate-950 py-1.5 pl-9 pr-3 text-xs text-slate-100 outline-none transition placeholder:text-slate-600 focus:border-indigo-500"
                            />
                        </div>
                        <div class="flex rounded-lg border border-slate-800 bg-slate-950 text-[11px] font-semibold">
                            <button
                                v-for="t in ['all', 'ppt', 'pptx', 'pdf']"
                                :key="t"
                                class="rounded-lg px-2.5 py-1.5 uppercase tracking-wider transition"
                                :class="typeFilter === t ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:text-slate-200'"
                                @click="typeFilter = t"
                            >{{ t === 'all' ? 'All' : t }}</button>
                        </div>
                        <button
                            class="flex items-center gap-1.5 rounded-lg border border-slate-800 bg-slate-950 px-2.5 py-1.5 text-[11px] font-semibold text-slate-400 transition hover:text-slate-200"
                            @click="sortOrder = sortOrder === 'newest' ? 'oldest' : 'newest'"
                        >
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path v-if="sortOrder === 'newest'" stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"/>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                            </svg>
                            {{ sortOrder === 'newest' ? 'Newest' : 'Oldest' }}
                        </button>
                    </div>

                    <div v-if="loadingList" class="flex min-h-[200px] items-center justify-center p-8 text-slate-400">
                        Loading presentations...
                    </div>

                    <div v-else-if="presentations.length === 0" class="flex min-h-[200px] flex-col items-center justify-center p-8 text-center">
                        <p class="text-lg font-semibold text-white">Your library is empty</p>
                        <p class="mt-2 max-w-md text-sm text-slate-400">Upload your first PowerPoint deck to start presenting.</p>
                    </div>

                    <div v-else-if="filteredPresentations.length === 0" class="flex min-h-[200px] flex-col items-center justify-center p-8 text-center">
                        <p class="text-lg font-semibold text-white">No matches found</p>
                        <p class="mt-2 max-w-md text-sm text-slate-400">Try a different search term or filter.</p>
                    </div>

                    <div v-else ref="listRef" class="overflow-y-auto divide-y divide-slate-800" :style="listStyle">
                        <article
                            v-for="presentation in filteredPresentations"
                            :key="presentation.id"
                            class="flex flex-col gap-3 p-4 transition hover:bg-slate-900/50"
                            :class="cardTone(presentation.status)"
                        >
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-lg font-bold text-white">{{ presentation.title }}</h3>
                                    </div>
                                    <p class="mt-1 truncate text-sm text-slate-400">{{ presentation.original_name }}</p>
                                    <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                                        <span>{{ presentation.slide_count || 0 }} slides</span>
                                        <span>{{ formatDate(presentation.created_at) }}</span>
                                    </div>
                                    <div
                                        v-if="presentation.status === 'failed' && presentation.error_message"
                                        class="mt-3 rounded-xl border border-red-900/50 bg-red-950/30 p-3 text-sm text-red-100"
                                    >
                                        {{ presentation.error_message }}
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <a
                                        class="rounded-xl border border-slate-800 bg-slate-950 px-4 py-2 text-xs font-semibold text-slate-200 transition hover:border-slate-700 hover:text-white"
                                        :href="presentation.pptx_url"
                                    >
                                        Download
                                    </a>
                                    <a
                                        v-if="presentation.notes_url"
                                        class="rounded-xl border border-emerald-800 bg-emerald-950/40 px-4 py-2 text-xs font-semibold text-emerald-300 transition hover:border-emerald-700 hover:text-emerald-200"
                                        :href="presentation.notes_url"
                                        download
                                    >
                                        Notes
                                    </a>
                                    <button
                                        v-if="presentation.status === 'ready'"
                                        type="button"
                                        class="rounded-xl bg-indigo-600 px-4 py-2 text-xs font-bold text-white transition hover:bg-indigo-500"
                                        @click="$emit('present', presentation.id)"
                                    >
                                        Present
                                    </button>
                                    <button
                                        v-if="presentation.status === 'failed'"
                                        type="button"
                                        class="rounded-xl border border-amber-900/60 bg-amber-950/40 px-4 py-2 text-xs font-bold text-amber-200 transition hover:border-amber-700"
                                        @click="retryPresentation(presentation)"
                                    >
                                        Retry
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-xl border border-red-900/50 bg-red-950/40 px-4 py-2 text-xs font-bold text-red-200 transition hover:border-red-700"
                                        @click="confirmDelete(presentation)"
                                    >
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>

        <div
            v-if="deleteTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
        >
            <div class="panel w-full max-w-md p-6">
                <h3 class="text-lg font-bold text-white">Delete presentation?</h3>
                <p class="mt-2 text-sm text-slate-400">
                    This will remove the database record and delete the uploaded files from storage.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border border-slate-800 bg-slate-950 px-4 py-2 text-sm font-semibold text-slate-300"
                        @click="deleteTarget = null"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-4 py-2 text-sm font-bold text-white"
                        @click="deletePresentation"
                    >
                        Delete
                    </button>
                </div>
            </div>
        </div>

        <button
            type="button"
            class="fixed bottom-6 right-6 z-40 flex h-10 w-10 items-center justify-center rounded-full border border-slate-700/60 bg-slate-900/85 text-sm font-bold text-slate-400 shadow-lg shadow-slate-950/60 backdrop-blur transition hover:border-slate-600 hover:text-white"
            @click="showMembersModal = true"
        >?</button>

        <div
            v-if="showMembersModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm"
            @click.self="showMembersModal = false"
        >
            <div class="panel w-full max-w-lg p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-white">Project Members</h3>
                    <button
                        type="button"
                        class="rounded-lg border border-slate-800 bg-slate-950 px-3 py-1.5 text-xs font-semibold text-slate-400 transition hover:text-white"
                        @click="showMembersModal = false"
                    >Close</button>
                </div>
                <div class="space-y-3">
                    <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-600 text-xs font-bold text-white">CJ</div>
                        <div>
                            <p class="text-sm font-semibold text-white">Bola, Chris Jericho M.</p>
                            <p class="text-xs text-slate-400">Main Dev</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-violet-600 text-xs font-bold text-white">PN</div>
                        <div>
                            <p class="text-sm font-semibold text-white">Sevilla, Paulo Neil A.</p>
                            <p class="text-xs text-slate-400">Sub Dev</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-600 text-xs font-bold text-white">IM</div>
                        <div>
                            <p class="text-sm font-semibold text-white">Arabaca, Irish May N.</p>
                            <p class="text-xs text-slate-400">&nbsp;</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-amber-600 text-xs font-bold text-white">JP</div>
                        <div>
                            <p class="text-sm font-semibold text-white">Dumas, John Paul P.</p>
                            <p class="text-xs text-slate-400">&nbsp;</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 rounded-xl border border-slate-800 bg-slate-950/60 p-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-rose-600 text-xs font-bold text-white">LS</div>
                        <div>
                            <p class="text-sm font-semibold text-white">Miñoza, Leovie S.</p>
                            <p class="text-xs text-slate-400">&nbsp;</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import axios from 'axios';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const emit = defineEmits(['present', 'open-notes']);

const presentations = ref([]);
const loadingList = ref(true);
const selectedFile = ref(null);
const title = ref('');
const isUploading = ref(false);
const uploadError = ref('');
const progress = ref(0);
const isDragging = ref(false);
const deleteTarget = ref(null);
const fileInput = ref(null);
const listRef = ref(null);
const listStyle = ref({});
const searchQuery = ref('');
const typeFilter = ref('all');
const sortOrder = ref('newest');
const showMembersModal = ref(false);

let refreshTimer = null;

function getExtension(name) {
    return name.split('.').pop()?.toLowerCase() ?? '';
}

const filteredPresentations = computed(() => {
    let list = presentations.value;

    // search by title or original name
    const query = searchQuery.value.trim().toLowerCase();
    if (query) {
        list = list.filter((p) => p.title.toLowerCase().includes(query) || p.original_name.toLowerCase().includes(query));
    }

    // filter by file type
    if (typeFilter.value !== 'all') {
        list = list.filter((p) => getExtension(p.original_name) === typeFilter.value);
    }

    // sort by date
    list = [...list].sort((a, b) => {
        const dateA = new Date(a.created_at).getTime();
        const dateB = new Date(b.created_at).getTime();
        return sortOrder.value === 'newest' ? dateB - dateA : dateA - dateB;
    });

    return list;
});

watch(filteredPresentations, () => updateListHeight(), { flush: 'post' });

function updateListHeight() {
    nextTick(() => {
        if (!listRef.value || listRef.value.children.length === 0) {
            return;
        }

        const firstEntry = listRef.value.children[0];
        const entryHeight = firstEntry.offsetHeight;
        const totalHeight = entryHeight * 3;
        listStyle.value = { maxHeight: `${totalHeight}px` };
    });
}

const formattedFileSize = computed(() => {
    if (!selectedFile.value) {
        return '';
    }

    return `${(selectedFile.value.size / (1024 * 1024)).toFixed(2)} MB`;
});

function formatDate(value) {
    if (!value) {
        return 'Just now';
    }

    return new Date(value).toLocaleString();
}

function cardTone(status) {
    if (status === 'ready') {
        return 'border-l-[3px] border-l-emerald-500/50';
    }

    if (status === 'failed') {
        return 'border-l-[3px] border-l-red-500/50';
    }

    return 'border-l-[3px] border-l-indigo-500/50';
}

function autoRefresh() {
    const hasProcessing = presentations.value.some((item) => ['processing', 'uploaded'].includes(item.status));

    clearInterval(refreshTimer);

    if (hasProcessing) {
        refreshTimer = setInterval(() => {
            refreshLibrary();
        }, 2000);
    }
}

async function refreshLibrary() {
    loadingList.value = true;

    try {
        const { data } = await axios.get('/api/presentations');
        presentations.value = data;
        autoRefresh();
        updateListHeight();
    } finally {
        loadingList.value = false;
    }
}

function openFilePicker() {
    fileInput.value?.click();
}

function validateFile(file) {
    if (!file) {
        return 'Please choose a file.';
    }

    const extension = file.name.split('.').pop()?.toLowerCase();

    if (!['ppt', 'pptx', 'pdf'].includes(extension)) {
        return 'Only PPT, PPTX, and PDF files are supported.';
    }

    if (file.size > 50 * 1024 * 1024) {
        return 'File must be 50MB or smaller.';
    }

    return '';
}

function applySelectedFile(file) {
    const error = validateFile(file);

    if (error) {
        uploadError.value = error;
        selectedFile.value = null;
        return;
    }

    selectedFile.value = file;
    uploadError.value = '';

    if (!title.value.trim()) {
        const baseName = file.name.replace(/\.[^.]+$/, '');
        title.value = baseName.replace(/[_-]+/g, ' ');
    }
}

function handleFileInput(event) {
    applySelectedFile(event.target.files?.[0] ?? null);
}

function handleDrop(event) {
    isDragging.value = false;
    applySelectedFile(event.dataTransfer.files?.[0] ?? null);
}

async function submitUpload() {
    if (!selectedFile.value) {
        return;
    }

    uploadError.value = '';
    isUploading.value = true;
    progress.value = 10;

    try {
        const formData = new FormData();
        formData.append('file', selectedFile.value);

        if (title.value.trim()) {
            formData.append('title', title.value.trim());
        }

        progress.value = 50;
        const { data } = await axios.post('/api/presentations', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        progress.value = 100;

        if (data.status === 'failed' && data.error_message) {
            uploadError.value = data.error_message;
        }

        selectedFile.value = null;
        title.value = '';

        if (fileInput.value) {
            fileInput.value.value = '';
        }

        await refreshLibrary();
    } catch (error) {
        uploadError.value = error?.response?.data?.error || error?.message || 'Upload failed.';
    } finally {
        isUploading.value = false;
        setTimeout(() => {
            progress.value = 0;
        }, 250);
    }
}

async function retryPresentation(presentation) {
    try {
        await axios.post(`/api/presentations/${presentation.id}/retry`);
        await refreshLibrary();
    } catch (error) {
        uploadError.value = error?.response?.data?.error || 'Retry failed.';
    }
}

function confirmDelete(presentation) {
    deleteTarget.value = presentation;
}

async function deletePresentation() {
    if (!deleteTarget.value) {
        return;
    }

    const presentation = deleteTarget.value;
    deleteTarget.value = null;

    await axios.delete(`/api/presentations/${presentation.id}`);
    await refreshLibrary();
}

onMounted(refreshLibrary);

onBeforeUnmount(() => {
    clearInterval(refreshTimer);
});
</script>
