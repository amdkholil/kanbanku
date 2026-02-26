<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import TiptapEditor from '@/Components/TiptapEditor.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    notes: Array,
});

const searchQuery = ref('');
const activeTab = ref('all'); // all, favorite
const activeId = ref(null);
const tagInput = ref('');

const tabs = [
    { id: 'all', icon: '📝', label: 'Semua' },
    { id: 'favorite', icon: '★', label: 'Favorit' },
];

const filteredNotes = computed(() => {
    let list = props.notes;
    const q = searchQuery.value.toLowerCase().trim();

    if (q) {
        list = list.filter(n =>
            (n.title && n.title.toLowerCase().includes(q)) ||
            (n.content && n.content.toLowerCase().includes(q)) ||
            (n.tags && n.tags.some(t => t.toLowerCase().includes(q)))
        );
    }

    if (activeTab.value === 'favorite') {
        list = list.filter(n => n.is_favorite);
    }

    return list;
});

const activeNote = computed(() => {
    return props.notes.find(n => n.id === activeId.value) || null;
});

const form = useForm({
    title: '',
    content: '',
    is_favorite: false,
    hide_preview_content: false,
    tags: [],
});

const selectNote = (note) => {
    activeId.value = note.id;
    form.title = note.title || '';
    form.content = note.content || '';
    form.is_favorite = !!note.is_favorite;
    form.hide_preview_content = !!note.hide_preview_content;
    form.tags = Array.isArray(note.tags) ? note.tags : [];
};

const createNewNote = () => {
    router.post(route('notes.store'), {
        title: '',
        content: '<p></p>',
        is_favorite: false,
        tags: [],
    }, {
        onSuccess: (page) => {
            // Find the newly created note (it should be the first one)
            if (props.notes.length > 0) {
                selectNote(props.notes[0]);
            }
        }
    });
};

const deleteNote = (note) => {
    Swal.fire({
        title: 'Hapus Catatan?',
        text: "Catatan yang dihapus tidak dapat dikembalikan!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#9ca3af',
        confirmButtonText: 'Ya, hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('notes.destroy', note.id), {
                onSuccess: () => {
                    if (activeId.value === note.id) {
                        activeId.value = null;
                    }
                    Swal.fire(
                        'Terhapus!',
                        'Catatan berhasil dihapus.',
                        'success'
                    );
                }
            });
        }
    });
};

const toggleFavorite = (note) => {
    router.patch(route('notes.favorite', note.id), {}, {
        onSuccess: () => {
            if (activeId.value === note.id) {
                form.is_favorite = !form.is_favorite;
            }
        }
    });
};

const togglePreview = (note) => {
    router.patch(route('notes.preview.toggle', note.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // State is updated automatically by Inertia, but we might want to update local form if active
            if (activeId.value === note.id) {
                form.hide_preview_content = !form.hide_preview_content;
            }
        }
    });
};

const saveNote = () => {
    if (!activeId.value) return;

    form.patch(route('notes.update', activeId.value), {
        preserveScroll: true,
    });
};

// Debounced save for content changes
let saveTimeout = null;
watch(() => form.content, () => {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(saveNote, 1000);
});

watch(() => form.title, () => {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(saveNote, 1000);
});

const addTag = () => {
    const tag = tagInput.value.trim().toLowerCase();
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag);
        saveNote();
    }
    tagInput.value = '';
};

const removeTag = (index) => {
    form.tags.splice(index, 1);
    saveNote();
};

const stripHtml = (html) => {
    const doc = new DOMParser().parseFromString(html, 'text/html');
    return doc.body.textContent || "";
};

const formatDate = (date, withTime = false) => {
    if (!date) return '';
    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        ...(withTime && { hour: '2-digit', minute: '2-digit' })
    }).format(new Date(date));
};

onMounted(() => {
    if (props.notes.length > 0) {
        selectNote(props.notes[0]);
    }
});
</script>

<template>

    <Head>
        <title>Notes</title>
    </Head>

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight shrink-0">My Notes</h2>

                <div class="flex-1 max-w-md relative">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </span>
                    <input v-model="searchQuery" type="text" placeholder="Cari catatan..."
                        class="block w-full pl-10 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm" />
                </div>

                <div class="flex items-center gap-2">
                    <template v-if="activeId">
                        <div v-if="form.isDirty"
                            class="flex items-center gap-1.5 px-2 py-1 rounded-full bg-indigo-50 text-[10px] text-indigo-600 font-medium animate-pulse border border-indigo-100 mr-2">
                            <span class="w-1 h-1 rounded-full bg-indigo-600"></span>
                            Menyimpan
                        </div>
                        <div v-else class="w-[88px]"></div>
                        <button @click="toggleFavorite(activeNote)" :class="[
                            'p-2 rounded-lg transition-colors border',
                            activeNote.is_favorite ? 'text-yellow-500 bg-yellow-50 border-yellow-100' : 'text-gray-400 hover:bg-gray-50 border-gray-100'
                        ]" title="Favorit">
                            <svg class="h-5 w-5" :fill="activeNote.is_favorite ? 'currentColor' : 'none'"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.382-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                            </svg>
                        </button>
                        <button @click="deleteNote(activeNote)"
                            class="p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors border border-gray-100 hover:border-red-100"
                            title="Hapus">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                        <div class="w-px h-6 bg-gray-200 mx-2"></div>
                    </template>
                    <button @click="createNewNote"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                        New Note
                    </button>
                </div>
            </div>
        </template>

        <div class="flex h-[calc(100vh-120px)] overflow-hidden max-w-7xl mx-auto py-4">
            <!-- Sidebar -->
            <aside class="w-80 bg-white flex flex-col flex-shrink-0 m-2 rounded-lg">
                <!-- Tabs -->
                <div class="p-4 border-b">
                    <div class="flex gap-1">
                        <button v-for="tab in tabs" :key="tab.id" @click="activeTab = tab.id" :class="[
                            'flex-1 flex items-center justify-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium transition-all',
                            activeTab === tab.id
                                ? 'bg-indigo-50 text-indigo-700'
                                : 'text-gray-500 hover:bg-gray-100'
                        ]">
                            <span>{{ tab.icon }}</span>
                            <span>{{ tab.label }}</span>
                        </button>
                    </div>
                </div>

                <!-- Note List -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="filteredNotes.length === 0" class="p-8 text-center text-gray-400">
                        <div class="mb-2 flex justify-center">
                            <svg class="h-12 w-12 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <p class="text-sm">Belum ada catatan</p>
                    </div>

                    <div v-for="note in filteredNotes" :key="note.id" @click="selectNote(note)" :class="[
                        'px-4 py-2 cursor-pointer border-b transition-colors relative group',
                        activeId === note.id ? 'bg-indigo-50/50' : 'hover:bg-gray-50'
                    ]">
                        <!-- Active Indicator -->
                        <div v-if="activeId === note.id" class="absolute left-0 top-0 bottom-0 w-1 bg-indigo-600"></div>

                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-semibold text-gray-900 text-sm truncate flex-1">
                                {{ note.title || 'Tanpa Judul' }}
                            </h3>
                            <span v-if="note.is_favorite" class="text-yellow-400 flex-shrink-0">★</span>
                        </div>
                        <div class="flex justify-between items-center gap-2 mb-1">
                            <span v-if="!note.hide_preview_content" class="text-xs text-gray-500 line-clamp-1 flex-1">
                                {{ stripHtml(note.content) || 'Tidak ada konten' }}
                            </span>
                            <span v-else class="text-xs text-gray-400 italic line-clamp-1 flex-1">
                                ********************************
                            </span>
                            <button @click.stop="togglePreview(note)"
                                class="text-indigo-400 hover:text-indigo-600 transition-colors flex-shrink-0 p-1"
                                :title="note.hide_preview_content ? 'Tampilkan Preview' : 'Sembunyikan Preview'">
                                <svg v-if="note.hide_preview_content" xmlns="http://www.w3.org/2000/svg"
                                    class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7 1.274-4.057 5.064-7 9.542-7 1.274 0 2.458.26 3.535.732M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18" />
                                </svg>
                            </button>
                        </div>
                        <div class="flex items-center justify-between">
                            <div class="flex gap-1 overflow-hidden">
                                <span v-for="tag in (note.tags || []).slice(0, 2)" :key="tag"
                                    class="px-1.5 py-0.5 bg-blue-100 text-blue-500 rounded text-[9px]">
                                    #{{ tag }}
                                </span>
                            </div>
                            <span class="text-[9px] text-gray-400 whitespace-nowrap">
                                {{ formatDate(note.updated_at, true) }}
                            </span>

                        </div>
                    </div>
                </div>

                <!-- Footer Stats -->
                <div
                    class="p-3 border-t bg-gray-50 text-[10px] text-gray-400 flex justify-between uppercase tracking-wider font-semibold">
                    <span>{{ notes.length }} Catatan</span>
                    <span>Kanbanku Notes</span>
                </div>
            </aside>

            <!-- Editor Main Area -->
            <main class="flex-1 flex flex-col min-w-0 bg-white m-2 rounded-lg">
                <template v-if="activeId">
                    <!-- Editor Content -->
                    <div class="flex-1 overflow-y-auto">
                        <div class="max-w-4xl mx-auto px-8 py-6">
                            <!-- Title -->
                            <div class="border-b border-gray-300 mb-6 pb-2">
                                <input v-model="form.title" type="text" placeholder="Judul Catatan..."
                                    class="w-full text-xl font-bold border-none focus:ring-0 px-2 py-0 placeholder-gray-300" />
                            </div>

                            <!-- Tiptap Editor -->
                            <div class="">
                                <TiptapEditor v-model="form.content"
                                    placeholder="Mulai menulis sesuatu yang luar biasa..." min-height="350px" />
                            </div>

                            <!-- Tags Section -->
                            <div class="mt-2 pt-2 border-t border-gray-100">
                                <h4 class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-2">Tags</h4>
                                <div class="flex flex-wrap gap-2">
                                    <span v-for="(tag, index) in form.tags" :key="index"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 bg-blue-50 text-blue-700 rounded-full text-xs">
                                        #{{ tag }}
                                        <button @click="removeTag(index)"
                                            class="hover:text-blue-900 focus:outline-none">
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 4.293z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </span>
                                    <input v-model="tagInput" @keydown.enter.prevent="addTag" type="text"
                                        placeholder="Tambah tag..."
                                        class="border-none focus:ring-0 p-0 text-xs text-gray-500 placeholder-gray-300 bg-transparent" />
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div v-else class="flex-1 flex items-center justify-center text-gray-400 flex-col gap-4">
                    <div class="p-6 bg-gray-50 rounded-2xl">
                        <svg class="h-16 w-16 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                    </div>
                    <div class="text-center">
                        <p class="font-medium text-gray-500">Pilih catatan untuk mulai mengedit</p>
                        <p class="text-sm">atau buat catatan baru untuk merangkum ide-idemu.</p>
                    </div>
                    <button @click="createNewNote"
                        class="mt-2 flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors shadow-sm text-sm font-medium">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Buat Catatan Baru
                    </button>
                </div>
            </main>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Editor prose styling from template */
:deep(.tiptap) {
    line-height: 1.8;
    font-size: 0.9375rem;
    color: #3a3a3c;
    min-height: 100%;
}

:deep(.tiptap h1) {
    font-size: 1.35rem;
    font-weight: 700;
    color: #1c1c1e;
    margin: 1rem 0 0.4rem;
}

:deep(.tiptap h2) {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1c1c1e;
    margin: 0.9rem 0 0.3rem;
}

:deep(.tiptap strong) {
    color: #1c1c1e;
    font-weight: 600;
}

:deep(.tiptap em) {
    color: #6e6e73;
    font-size: 1.05em;
}

:deep(.tiptap ul) {
    list-style: disc;
    padding-left: 1.4rem;
    margin: 0.5rem 0;
}

:deep(.tiptap ol) {
    list-style: decimal;
    padding-left: 1.4rem;
    margin: 0.5rem 0;
}

:deep(.tiptap blockquote) {
    border-left: 3px solid #c7d2fe;
    padding-left: 1rem;
    color: #6e6e73;
    font-style: italic;
    font-size: 1.05em;
    margin: 0.6rem 0;
}

:deep(.tiptap pre) {
    background: #f4f3ef;
    border: 1px solid #eae8e2;
    border-radius: 6px;
    padding: 0.75rem 1rem;
    font-family: 'Menlo', monospace;
    font-size: 0.83em;
    overflow-x: auto;
    margin: 0.6rem 0;
}
</style>
