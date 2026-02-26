<script setup>
import { useEditor, EditorContent } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import Underline from '@tiptap/extension-underline';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import Placeholder from '@tiptap/extension-placeholder';
import { Table } from '@tiptap/extension-table';
import { TableRow } from '@tiptap/extension-table-row';
import { TableHeader } from '@tiptap/extension-table-header';
import { TableCell } from '@tiptap/extension-table-cell';
import { Color } from '@tiptap/extension-color';
import { TextStyle } from '@tiptap/extension-text-style';
import { Highlight } from '@tiptap/extension-highlight';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import { watch, onBeforeUnmount } from 'vue';

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    placeholder: {
        type: String,
        default: 'Write something...',
    },
    editable: {
        type: Boolean,
        default: true,
    },
    minHeight: {
        type: String,
        default: '150px',
    }
});

const emit = defineEmits(['update:modelValue']);

const editor = useEditor({
    content: props.modelValue,
    editable: props.editable,
    extensions: [
        StarterKit,
        Underline,
        Subscript,
        Superscript,
        Placeholder.configure({
            placeholder: props.placeholder,
            emptyEditorClass: 'is-editor-empty',
        }),
        Color,
        TextStyle,
        Highlight.configure({ multicolor: true }),
        Table.configure({
            resizable: true,
            HTMLAttributes: {
                class: 'tiptap-table',
            },
        }),
        TableRow,
        TableHeader,
        TableCell,
        TaskList,
        TaskItem.configure({
            nested: true,
        }),
    ],
    editorProps: {
        attributes: {
            class: 'tiptap focus:outline-none w-full px-4 py-3 text-gray-900',
        },
    },
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML());
    },
});

watch(() => props.modelValue, (value) => {
    const isSame = editor.value.getHTML() === value;
    if (isSame) return;
    editor.value.commands.setContent(value, false);
});

watch(() => props.editable, (value) => {
    editor.value.setEditable(value);
});

onBeforeUnmount(() => {
    editor.value.destroy();
});

const handleEditorClick = () => {
    if (editor.value) {
        editor.value.chain().focus().run();
    }
};
</script>

<template>
    <div class="tiptap-editor-container border border-gray-300 rounded-md overflow-hidden bg-white flex flex-col">
        <!-- Tiptap Toolbar -->
        <div v-if="editor && editable" class="flex flex-wrap gap-0.5 px-1.5 border-b bg-gray-50 flex-none sticky top-0 z-10">
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 1 }) }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="H1">
                <span class="font-bold text-[8pt]">H1</span>
            </button>
            <button type="button" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('heading', { level: 2 }) }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="H2">
                <span class="font-bold text-[8pt]">H2</span>
            </button>
            <div class="w-px h-6 bg-gray-200 mx-1 self-center"></div>
            <button type="button" @click="editor.chain().focus().toggleBold().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bold') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Bold">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 4h8a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/><path d="M6 12h9a4 4 0 0 1 4 4 4 4 0 0 1-4 4H6z"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleItalic().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('italic') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Italic">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="19" y1="4" x2="10" y2="4"/><line x1="14" y1="20" x2="5" y2="20"/><line x1="15" y1="4" x2="9" y2="20"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleUnderline().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('underline') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Underline">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 3v7a6 6 0 0 0 6 6 6 6 0 0 0 6-6V3"/><line x1="4" y1="21" x2="20" y2="21"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleStrike().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('strike') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Strike">
                <del><pre>s</pre></del>
            </button>
            <div class="w-px h-6 bg-gray-200 mx-1 self-center"></div>
            <div class="flex items-center gap-1">
                <div class="relative group flex items-center p-1 rounded hover:bg-gray-200 transition-colors" title="Font Color">
                    <input type="color" @input="editor.chain().focus().setColor($event.target.value).run()" :value="editor.getAttributes('textStyle').color || '#000000'" class="w-4 h-4 p-0 border-0 bg-transparent cursor-pointer">
                    <span class="ml-1 text-[8px] font-bold text-gray-500">A</span>
                </div>
                <div class="relative group flex items-center p-1 rounded hover:bg-gray-200 transition-colors" title="Highlight Color">
                    <input type="color" @input="editor.chain().focus().toggleHighlight({ color: $event.target.value }).run()" :value="editor.getAttributes('highlight').color || '#ffff00'" class="w-4 h-4 p-0 border-0 bg-transparent cursor-pointer">
                    <span class="ml-1 text-[8px] font-bold text-gray-500">H</span>
                </div>
            </div>
            <div class="w-px h-6 bg-gray-200 mx-1 self-center"></div>
            <button type="button" @click="editor.chain().focus().toggleBulletList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('bulletList') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Bullet List">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleOrderedList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('orderedList') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Ordered List">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="10" y1="6" x2="21" y2="6"/><line x1="10" y1="12" x2="21" y2="12"/><line x1="10" y1="18" x2="21" y2="18"/><path d="M4 6h1v4"/><path d="M4 10h2"/><path d="M6 18H4c0-1 2-2 2-3s-1-1.5-2-1"/></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleCodeBlock().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('codeBlock') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Code Block">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
            </button>
            <button type="button" @click="editor.chain().focus().toggleTaskList().run()" :class="{ 'bg-indigo-100 text-indigo-700': editor.isActive('taskList') }" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Task List">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
            </button>
            <div class="w-px h-6 bg-gray-200 mx-1 self-center"></div>
            <button type="button" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()" class="p-1.5 rounded hover:bg-gray-200 transition-colors" title="Insert Table">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/><line x1="15" y1="3" x2="15" y2="21"/></svg>
            </button>
            <div class="w-px h-6 bg-gray-200 mx-1 self-center"></div>
            <button type="button" @click="editor.chain().focus().unsetAllMarks().clearNodes().run()" class="p-1.5 rounded hover:bg-gray-200 transition-colors text-red-500" title="Clear Formatting">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"/><line x1="18" y1="2" x2="6" y2="14"/><line x1="22" y1="6" x2="10" y2="18"/></svg>
            </button>
        </div>

        <!-- Editor Content -->
        <div class="editor-content-wrapper flex-grow overflow-hidden min-h-0 relative">
            <editor-content 
                :editor="editor" 
                class="prose prose-sm max-w-none cursor-text absolute inset-0 overflow-y-auto" 
                :style="{ minHeight: minHeight }"
                @click="handleEditorClick"
            />
        </div>
    </div>
</template>

<style>
.tiptap-editor-container {
    resize: vertical;
    overflow: hidden;
    min-height: 200px;
}


/* Tiptap Placeholder Style */
.tiptap p.is-editor-empty:first-child::before {
  color: #adb5bd;
  content: attr(data-placeholder);
  float: left;
  height: 0;
  pointer-events: none;
}

.tiptap {
    outline: none !important;
    min-height: 100%;
    font-size: 9pt;
}

.tiptap .ProseMirror {
    outline: none !important;
    min-height: inherit;
    padding: 1rem;
}

.tiptap p {
    margin: 0 0 0.5rem 0;
}

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

.tiptap h1 { font-size: 1.5em; font-weight: bold; margin: 0.5rem 0; }
.tiptap h2 { font-size: 1.25em; font-weight: bold; margin: 0.5rem 0; }

.tiptap pre {
    background: #1e293b;
    color: #e2e8f0;
    font-family: 'JetBrainsMono', monospace;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    margin: 1rem 0;
}

.tiptap pre code {
    color: inherit;
    padding: 0;
    background: none;
    font-size: 0.8rem;
}

.tiptap code {
    background-color: #f1f5f9;
    padding: 0.2rem 0.4rem;
    border-radius: 0.25rem;
    font-size: 0.85em;
}

.tiptap mark {
    background-color: #ffe066;
    padding: 0.1rem 0;
}

/* Table styles */
.tiptap table {
    border-collapse: collapse;
    table-layout: fixed;
    width: 100%;
    margin: 1rem 0;
    overflow: hidden;
    border: 1px solid #e5e7eb;
}

.tiptap table td,
.tiptap table th {
    min-width: 1em;
    border: 1px solid #e5e7eb;
    padding: 0.5rem;
    vertical-align: top;
    box-sizing: border-box;
    position: relative;
}

.tiptap table th {
    font-weight: bold;
    text-align: left;
    background-color: #f9fafb;
}

.tiptap table .selectedCell {
    background-color: #dbeafe;
}

/* Task List styles */
.tiptap ul[data-type="taskList"] {
  list-style: none;
  padding: 0;
  margin: 0.5rem 0;
}

.tiptap ul[data-type="taskList"] li {
  display: flex;
  align-items: flex-start;
  margin-bottom: 0rem;
  margin-top: 0;
}

.tiptap ul[data-type="taskList"] li > label {
  flex: 0 0 auto;
  user-select: none;
  margin-right: 0.5rem;
  margin-top: 0rem;
}

.tiptap ul[data-type="taskList"] li > div {
  flex: 1 1 auto;
}

.tiptap ul[data-type="taskList"] input[type="checkbox"] {
  cursor: pointer;
  width: 0.7rem;
  height: 0.7rem;
  accent-color: #4f46e5;
}

.tiptap ul[data-type="taskList"] li[data-checked="true"] > div > p {
  text-decoration: line-through;
  color: #7c7f89;
}

.tiptap ul[data-type="taskList"] li > div > p {
    margin: 0 !important;
    padding: 0 !important;
}
</style>
