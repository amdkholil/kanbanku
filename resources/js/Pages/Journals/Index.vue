<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';
import { Editor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import Placeholder from '@tiptap/extension-placeholder';

const props = defineProps({
    initialJournal: Object,
    selectedDate: String,
    datesWithEntries: Array,
});

const today = new Date();
const selectedMonth = ref(new Date(props.selectedDate).getMonth());
const selectedYear = ref(new Date(props.selectedDate).getFullYear());
const selectedDay = ref(new Date(props.selectedDate).getDate());
const localDatesWithEntries = ref(props.datesWithEntries || []);

const journalForm = useForm({
    entry_date: props.selectedDate,
    content: props.initialJournal?.content || '',
    is_favorite: props.initialJournal?.is_favorite || false,
});

const editor = ref(null);

onMounted(() => {
    editor.value = new Editor({
        content: journalForm.content,
        editable: true, // Explicitly enable editing
        autofocus: 'end', // Auto focus at the end of content
        extensions: [
            StarterKit,
            Underline,
            Subscript,
            Superscript,
            Placeholder.configure({
                placeholder: 'Tulis catatan di sini...',
                emptyEditorClass: 'is-editor-empty',
            }),
        ],
        editorProps: {
            attributes: {
                class: 'tiptap focus:outline-none min-h-[300px] h-full w-full px-4 py-3 text-gray-900',
            },
        },
        onUpdate: ({ editor }) => {
            const html = editor.getHTML();
            if (journalForm.content !== html) {
                journalForm.content = html;
                handleInput();
            }
        },
    });

    scrollMobileCalendar();
});

onBeforeUnmount(() => {
    editor.value?.destroy();
});

const months = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
const daysOfWeek = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

const years = computed(() => {
    const current = new Date().getFullYear();
    let arr = [];
    for(let i = current - 5; i <= current + 5; i++) arr.push(i);
    return arr;
});

const daysInMonth = computed(() => {
    return new Date(selectedYear.value, selectedMonth.value + 1, 0).getDate();
});

const firstDayOffset = computed(() => {
    return new Date(selectedYear.value, selectedMonth.value, 1).getDay();
});

const isWeekend = (date) => {
    const day = new Date(selectedYear.value, selectedMonth.value, date).getDay();
    return day === 0 || day === 6;
};

const isToday = (date) => {
    const checkDate = new Date(selectedYear.value, selectedMonth.value, date);
    const todayDate = new Date();
    return checkDate.getDate() === todayDate.getDate() &&
           checkDate.getMonth() === todayDate.getMonth() &&
           checkDate.getFullYear() === todayDate.getFullYear();
};

const getDayName = (date) => {
    const dayIndex = new Date(selectedYear.value, selectedMonth.value, date).getDay();
    return daysOfWeek[dayIndex];
};

const currentDateString = computed(() => {
    const month = (selectedMonth.value + 1).toString().padStart(2, '0');
    const day = selectedDay.value.toString().padStart(2, '0');
    return `${selectedYear.value}-${month}-${day}`;
});

const formattedDate = computed(() => {
    return `${selectedDay.value} ${months[selectedMonth.value]} ${selectedYear.value}`;
});

const fetchJournal = async (date) => {
    try {
        const response = await axios.get(route('api.journals.show', date));
        const journal = response.data;
        journalForm.entry_date = date;
        journalForm.content = journal?.content || '';
        journalForm.is_favorite = journal?.is_favorite || false;
        
        if (editor.value) {
            editor.value.commands.setContent(journalForm.content);
        }
    } catch (error) {
        console.error('Failed to fetch journal:', error);
    }
};

const handleEditorClick = () => {
    if (editor.value) {
        editor.value.chain().focus().run();
    }
};

const fetchActiveDates = async () => {
    try {
        const response = await axios.get(route('api.journals.dates'), {
            params: {
                month: selectedMonth.value + 1,
                year: selectedYear.value
            }
        });
        localDatesWithEntries.value = response.data;
    } catch (error) {
        console.error('Failed to fetch active dates:', error);
    }
};

const saveStatus = ref('saved'); // 'saved', 'saving', 'unsaved'

let autosaveTimeout = null;

const saveNote = () => {
    saveStatus.value = 'saving';
    journalForm.post(route('journals.store'), {
        preserveScroll: true,
        onSuccess: () => {
            fetchActiveDates();
            saveStatus.value = 'saved';
        },
        onError: () => {
            saveStatus.value = 'unsaved';
        }
    });
};

const handleInput = () => {
    saveStatus.value = 'unsaved';
    if (autosaveTimeout) clearTimeout(autosaveTimeout);
    autosaveTimeout = setTimeout(() => {
        saveNote();
    }, 5000);
};

watch(() => journalForm.is_favorite, () => {
    saveNote();
});

const goToToday = () => {
    const today = new Date();
    selectedMonth.value = today.getMonth();
    selectedYear.value = today.getFullYear();
    selectedDay.value = today.getDate();
};

const mobileScrollContainer = ref(null);

const scrollMobileCalendar = () => {
    const container = mobileScrollContainer.value;
    if (!container) return;
    
    setTimeout(() => {
        const activeBtn = container.querySelector('.bg-indigo-600');
        if (activeBtn) {
            const containerWidth = container.offsetWidth;
            const btnWidth = activeBtn.offsetWidth;
            const btnLeft = activeBtn.offsetLeft;
            
            container.scrollTo({
                left: btnLeft - (containerWidth / 2) + (btnWidth / 2),
                behavior: 'smooth'
            });
        }
    }, 100);
};

watch(currentDateString, (newDate) => {
    if (autosaveTimeout) clearTimeout(autosaveTimeout);
    if (saveStatus.value === 'unsaved') {
        saveNote();
    }
    fetchJournal(newDate).then(() => {
        saveStatus.value = 'saved';
    });
    scrollMobileCalendar();
});

// Reset day if it exceeds days in month when changing month/year
watch([selectedMonth, selectedYear], () => {
    if (selectedDay.value > daysInMonth.value) {
        selectedDay.value = daysInMonth.value;
    }
    fetchActiveDates();
    scrollMobileCalendar();
});

onMounted(() => {
    scrollMobileCalendar();
});
</script>

<template>
    <Head title="Daily Journal" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center text-gray-900">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Daily Journal</h2>
                <div class="flex items-center gap-2">
                    <button @click="saveNote" 
                            :disabled="journalForm.processing || saveStatus === 'saved'"
                            :class="[
                                'px-4 py-1.5 rounded-md text-sm font-medium flex items-center gap-2 shadow-sm transition-all focus:ring-2 focus:ring-indigo-500',
                                saveStatus === 'unsaved' 
                                    ? 'bg-indigo-600 text-white hover:bg-indigo-700' 
                                    : 'bg-white border border-gray-300 text-gray-400 cursor-not-allowed'
                            ]">
                        <svg v-if="saveStatus === 'saving'" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        {{ saveStatus === 'saving' ? 'Saving...' : 'Save' }}
                    </button>
                    <button @click="goToToday" 
                            class="bg-white border border-gray-300 text-gray-700 px-4 py-1.5 rounded-md text-sm font-medium hover:bg-gray-50 flex items-center gap-2 shadow-sm transition-all focus:ring-2 focus:ring-indigo-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Today
                    </button>
                </div>
            </div>
        </template>

        <div class="py-4 sm:py-6 md:py-8 lg:py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Responsive Layout -->
                <div class="flex flex-col md:flex-row gap-4 md:gap-6 px-4 md:px-0">
                    <!-- Calendar Section -->
                    <div class="w-full md:w-1/3">
                        <!-- Desktop Calendar -->
                        <div class="hidden md:block bg-white p-6 rounded-lg shadow-sm border border-gray-200 h-fit">
                            <div class="flex gap-2 mb-4">
                                <select v-model="selectedMonth" class="border-gray-300 rounded-md p-2 flex-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer text-sm font-medium">
                                    <option v-for="(m, index) in months" :key="index" :value="index">{{ m }}</option>
                                </select>
                                <select v-model="selectedYear" class="border-gray-300 rounded-md p-2 flex-1 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer text-sm font-medium">
                                    <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                                </select>
                            </div>

                            <div class="grid grid-cols-7 gap-1 text-center border border-gray-100 p-2 rounded-lg bg-gray-50">
                                <div v-for="(day, index) in daysOfWeek" :key="day"
                                     :class="['text-[10px] font-bold py-2 uppercase tracking-wider', (index === 0 || index === 6) ? 'text-red-500' : 'text-gray-400']">
                                    {{ day }}
                                </div>

                                <div v-for="blank in firstDayOffset" :key="'blank-'+blank" class="p-2"></div>

                                <button
                                    v-for="date in daysInMonth"
                                    :key="date"
                                    @click="selectedDay = date"
                                    :class="[
                                        'p-2 rounded-lg text-sm transition font-medium relative flex flex-col items-center justify-center min-h-[44px] w-full',
                                        selectedDay === date 
                                            ? 'bg-indigo-600 text-white z-10 shadow-md ring-2 ring-indigo-300 ring-offset-1' 
                                            : (localDatesWithEntries.includes(date) 
                                                ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' 
                                                : 'hover:bg-gray-100 text-gray-700'),
                                        (isWeekend(date) && selectedDay !== date && !localDatesWithEntries.includes(date)) ? 'text-red-500' : ''
                                    ]"
                                >
                                    <span class="z-10" :class="{ 'underline decoration-2': isToday(date) }">{{ date }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Mobile Month/Year Selector -->
                        <div class="md:hidden bg-white p-4 rounded-lg shadow-sm border border-gray-200 flex justify-between items-center">
                            <select v-model="selectedMonth" class="bg-transparent border-none font-bold text-lg focus:ring-0 outline-none p-0 pr-8">
                                <option v-for="(m, index) in months" :key="index" :value="index">{{ m }}</option>
                            </select>
                            <select v-model="selectedYear" class="bg-transparent border-none font-bold text-lg focus:ring-0 outline-none p-0 pr-8">
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                        </div>

                        <!-- Mobile Date Scroller -->
                        <div ref="mobileScrollContainer" class="md:hidden flex overflow-x-auto gap-3 py-4 hide-scrollbar -mx-4 px-4 font-sans">
                            <button
                                v-for="date in daysInMonth"
                                :key="date"
                                @click="selectedDay = date"
                                :class="[
                                    'flex-shrink-0 w-14 h-14 flex flex-col items-center justify-center rounded-2xl border transition-all relative',
                                    selectedDay === date 
                                        ? 'bg-indigo-600 text-white border-indigo-600 shadow-md scale-105 z-10' 
                                        : (localDatesWithEntries.includes(date)
                                            ? 'bg-indigo-50 border-indigo-200 text-indigo-700'
                                            : 'bg-white border-gray-200 text-gray-700'),
                                    (isWeekend(date) && selectedDay !== date && !localDatesWithEntries.includes(date)) ? 'text-red-500 border-red-100 bg-red-50' : ''
                                ]"
                            >
                                <span class="text-[9px] uppercase font-bold opacity-70">{{ getDayName(date) }}</span>
                                <span class="font-bold text-base" :class="{ 'underline decoration-2': isToday(date) }">{{ date }}</span>
                            </button>
                        </div>
                    </div>

                    <!-- Editor Section -->
                    <div class="w-full md:w-2/3">
                        <div class="bg-white rounded-lg md:rounded-xl shadow-sm border border-gray-200 flex flex-col min-h-[500px] overflow-hidden">
                            <!-- Header -->
                            <div class="px-4 md:px-6 py-3 md:py-4 flex justify-between items-center border-b bg-gray-50/50">
                                <h2 class="text-sm md:text-lg font-semibold text-gray-800">
                                    <span class="hidden md:inline">Catatan: </span>{{ formattedDate }}
                                </h2>
                                <button @click="journalForm.is_favorite = !journalForm.is_favorite" 
                                        :class="journalForm.is_favorite ? 'text-yellow-500' : 'text-gray-300'"
                                        class="hover:scale-110 transition-transform">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 md:h-6 w-5 md:w-6" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/>
                                    </svg>
                                </button>
                            </div>
                            
                            <!-- Tiptap Toolbar -->
                            <div v-if="editor" class="flex overflow-x-auto gap-1 p-2 border-b bg-white hide-scrollbar md:flex-wrap sticky top-0 z-20">
                                <!-- Desktop Toolbar (full) -->
                                <template v-if="true">
                                    <button @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 1 }) }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0" title="H1">
                                        <span class="font-bold text-xs md:text-sm">H1</span>
                                    </button>
                                    <button @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 2 }) }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="H2">
                                        <span class="font-bold text-xs md:text-sm">H2</span>
                                    </button>
                                    <button @click="editor.chain().focus().toggleHeading({ level: 3 }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 3 }) }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="H3">
                                        <span class="font-bold text-xs md:text-sm">H3</span>
                                    </button>
                                    <button @click="editor.chain().focus().toggleHeading({ level: 4 }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 4 }) }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="H4">
                                        <span class="font-bold text-xs md:text-sm">H4</span>
                                    </button>
                                    <div class="w-px h-6 bg-gray-200 mx-1 self-center hidden md:block"></div>
                                    <button @click="editor.chain().focus().toggleBold().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bold') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0" title="Bold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
                                    </button>
                                    <button @click="editor.chain().focus().toggleItalic().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('italic') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0" title="Italic">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
                                    </button>
                                    <button @click="editor.chain().focus().toggleUnderline().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('underline') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0" title="Underline">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
                                    </button>
                                    <button @click="editor.chain().focus().toggleStrike().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('strike') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="Strike">
                                        <del>S&nbsp;</del>
                                    </button>
                                    <div class="w-px h-6 bg-gray-200 mx-1 self-center hidden md:block"></div>
                                    <button @click="editor.chain().focus().toggleSubscript().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('subscript') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="Subscript">
                                        <span class="text-xs font-bold">X<sub>2</sub></span>
                                    </button>
                                    <button @click="editor.chain().focus().toggleSuperscript().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('superscript') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="Superscript">
                                        <span class="text-xs font-bold">X<sup>2</sup></span>
                                    </button>
                                    <div class="w-px h-6 bg-gray-200 mx-1 self-center hidden md:block"></div>
                                    <button @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bulletList') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0" title="Bullet List">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                                    </button>
                                    <button @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('orderedList') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0" title="Ordered List">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
                                    </button>
                                    <button @click="editor.chain().focus().toggleCode().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('code') }" class="p-1.5 md:p-1.5 rounded hover:bg-gray-100 transition-colors shrink-0 hidden md:inline-flex" title="Code">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                                    </button>
                                </template>
                            </div>

                            <!-- Editor Content (Single Instance) -->
                            <editor-content 
                                :editor="editor" 
                                class="flex-grow overflow-y-auto bg-white cursor-text relative p-2 md:px-4 md:py-3" 
                                @click="handleEditorClick" 
                                tabindex="0"
                            />
                            
                            <!-- Footer -->
                            <div class="flex items-center justify-between text-[10px] md:text-[11px] text-gray-400 italic px-4 md:px-6 py-2 md:py-2 border-t bg-gray-50/30">
                                <div class="hidden md:block">Rich Text Editor Enabled</div>
                                <div class="md:hidden">Tiptap Editor</div>
                                <div class="flex items-center gap-2">
                                    <span v-if="saveStatus === 'saving'" class="text-indigo-600 flex items-center gap-1">
                                        <svg class="animate-spin h-3 w-3" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="hidden md:inline">Sedang menyimpan...</span>
                                        <span class="md:hidden">Simpan...</span>
                                    </span>
                                    <span v-else-if="saveStatus === 'saved'" class="text-green-600 flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span class="hidden md:inline">Tersimpan otomatis</span>
                                        <span class="md:hidden">Terarsip</span>
                                    </span>
                                    <span v-else-if="saveStatus === 'unsaved'" class="text-amber-600 flex items-center gap-1">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span class="hidden md:inline">Berubah (Simpan dalam 5s)</span>
                                        <span class="md:hidden">Draft</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style>
/* Minimal Tiptap Placeholder Style */
.tiptap p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

/* Ensure editor is visible and usable */
.tiptap {
    outline: none !important;
    min-height: 150px;
    display: block;
    cursor: text;
    caret-color: #000;
}

/* Make sure ProseMirror is editable */
.tiptap .ProseMirror {
    outline: none !important;
    cursor: text;
}

/* When editor is focused */
.ProseMirror-focused {
    outline: none !important;
}

/* Ensure proper paragraph spacing */
.tiptap p {
    margin: 0 0 0.5rem 0;
    min-height: 1.5em;
}

/* Ensure empty paragraphs are clickable */
.tiptap p:empty::before {
    content: '';
    display: inline-block;
}

/* List styles that might be reset by Tailwind */
.tiptap ul {
    list-style-type: disc;
    padding-left: 1.5em;
    margin: 0.5rem 0;
}

.tiptap ol {
    list-style-type: decimal;
    padding-left: 1.5em;
    margin: 0.5rem 0;
}

/* Heading styles */
.tiptap h1 {
    font-size: 2em;
    font-weight: bold;
    margin: 0.5rem 0;
}

.tiptap h2 {
    font-size: 1.5em;
    font-weight: bold;
    margin: 0.5rem 0;
}

.tiptap h3 {
    font-size: 1.25em;
    font-weight: bold;
    margin: 0.5rem 0;
}

.tiptap h4 {
    font-size: 1.1em;
    font-weight: bold;
    margin: 0.5rem 0;
}

/* Code styles */
.tiptap code {
    background-color: #f3f4f6;
    padding: 0.2em 0.4em;
    border-radius: 0.25rem;
    font-family: monospace;
}
</style>

<style scoped>
.hide-scrollbar::-webkit-scrollbar { display: none; }
.hide-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>
