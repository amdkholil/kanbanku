<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import draggable from 'vuedraggable';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
    project: Object,
    board: Object
});

const localColumns = ref([]);
const toggleColumnCollapse = (columnId) => {
    const column = localColumns.value.find(c => c.id === columnId);
    if (column) {
        column.is_colapse = !column.is_colapse;
        
        router.patch(route('columns.update', columnId), {
            is_colapse: column.is_colapse
        }, {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                // In case we want to do something after
            },
            onError: (errors) => {
                console.error('Failed to update column collapse state:', errors);
                column.is_colapse = !column.is_colapse;
            }
        });
    }
};

const isCollapsed = (columnId) => {
    const column = localColumns.value.find(c => c.id == columnId);
    return column ? column.is_colapse : false;
};

// Initialize and watch for prop changes
watch(() => props.board.columns, (newVal) => {
    localColumns.value = JSON.parse(JSON.stringify(newVal));
}, { immediate: true, deep: true });

const newTaskForm = useForm({
    title: '',
    column_id: null
});

const selectedTask = ref(null);

const editTaskForm = useForm({
    id: null,
    title: '',
    description: '',
    priority: 'medium',
    status: 'open',
    due_date: null
});

const commentForm = useForm({
    comment: ''
});

const showNewTaskInput = ref(null);
const showEditModal = ref(false);

const openEditModal = (task) => {
    selectedTask.value = task;
    editTaskForm.id = task.id;
    editTaskForm.title = task.title;
    editTaskForm.description = task.description || '';
    editTaskForm.priority = task.priority;
    editTaskForm.status = task.status;
    editTaskForm.due_date = task.due_date;
    showEditModal.value = true;
};

const updateTask = () => {
    editTaskForm.patch(route('tasks.update', editTaskForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
            // The props update will trigger the watcher for localColumns
        }
    });
};

const addComment = () => {
    commentForm.post(route('comments.store', editTaskForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            // Update selectedTask from props
            const updatedTask = props.board.columns.flatMap(c => c.tasks).find(t => t.id === editTaskForm.id);
            if (updatedTask) selectedTask.value = updatedTask;
        }
    });
};

const deleteComment = (commentId) => {
    if (confirm('Delete this comment?')) {
        commentForm.delete(route('comments.destroy', commentId), {
            preserveScroll: true,
            onSuccess: () => {
                 const updatedTask = props.board.columns.flatMap(c => c.tasks).find(t => t.id === editTaskForm.id);
                 if (updatedTask) selectedTask.value = updatedTask;
            }
        });
    }
};

const deleteTask = () => {
    if (confirm('Are you sure you want to delete this task?')) {
        editTaskForm.delete(route('tasks.destroy', editTaskForm.id), {
            onSuccess: () => {
                showEditModal.value = false;
            }
        });
    }
};

const addTask = (columnId) => {
    if (newTaskForm.title.trim() === '') {
        showNewTaskInput.value = null;
        return;
    }
    
    newTaskForm.column_id = columnId;
    newTaskForm.post(route('tasks.store'), {
        onSuccess: () => {
            newTaskForm.reset();
            showNewTaskInput.value = null;
        }
    });
};

const activeColumnMenu = ref(null);
const showColumnModal = ref(false);
const showBulkImportModal = ref(false);
const showAddColumnInput = ref(false);

const columnForm = useForm({
    id: null,
    name: '',
    color: '#94a3b8'
});

const addColumnForm = useForm({
    name: '',
    color: '#94a3b8'
});

const bulkImportForm = useForm({
    column_id: null,
    tasks_text: ''
});

const openColumnModal = (column) => {
    columnForm.id = column.id;
    columnForm.name = column.name;
    columnForm.color = column.color || '#94a3b8';
    showColumnModal.value = true;
    activeColumnMenu.value = null;
};

const openBulkImportModal = (column) => {
    bulkImportForm.column_id = column.id;
    bulkImportForm.tasks_text = '';
    showBulkImportModal.value = true;
    activeColumnMenu.value = null;
};

const updateColumn = () => {
    columnForm.patch(route('columns.update', columnForm.id), {
        onSuccess: () => {
            showColumnModal.value = false;
        }
    });
};

const importTasks = () => {
    bulkImportForm.post(route('tasks.bulk-store', bulkImportForm.column_id), {
        onSuccess: () => {
            showBulkImportModal.value = false;
            bulkImportForm.reset();
        }
    });
};

const addColumn = () => {
    if (addColumnForm.name.trim() === '') {
        showAddColumnInput.value = false;
        return;
    }
    
    addColumnForm.post(route('columns.store', props.board.id), {
        onSuccess: () => {
            addColumnForm.reset();
            showAddColumnInput.value = false;
        }
    });
};

const moveColumn = (columnId, direction) => {
    router.post(route('columns.move', columnId), { direction }, {
        preserveScroll: true,
        onSuccess: () => {
            activeColumnMenu.value = null;
        }
    });
};

const onMove = (evt) => {
    const taskId = evt.item.__draggable_context.element.id;
    const newColumnId = evt.to.dataset.columnId;
    const newPosition = evt.newIndex;

    axios.post(route('tasks.move'), {
        task_id: taskId,
        column_id: newColumnId,
        position: newPosition
    }).catch(error => {
        console.error('Failed to move task:', error);
        // Better: revert the UI state
    });
};

const vFocus = {
  mounted: (el) => el.focus()
};

const closeMenu = () => {
    activeColumnMenu.value = null;
};

onMounted(() => {
    window.addEventListener('click', closeMenu);
});

onUnmounted(() => {
    window.removeEventListener('click', closeMenu);
});


</script>

<template>
    <Head :title="project.name" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ project.name }} / {{ board.name }}
                </h2>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium hover:bg-gray-50">Filter</button>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-medium hover:bg-indigo-700">Share</button>
                </div>
            </div>
        </template>

        <div class="py-6 h-[calc(100vh-140px)] overflow-x-auto overflow-y-hidden">
            <div class="px-4 sm:px-4 lg:px-4 h-full">
                <div class="flex h-full space-x-4 pb-4">
                    <!-- Columns -->
                    <div v-for="(column, index) in localColumns" :key="column.id" 
                        class="flex-shrink-0 bg-gray-100 rounded-lg flex flex-col max-h-full border border-gray-200 transition-all duration-300"
                        :class="isCollapsed(column.id) ? 'w-6 py-2' : 'w-80'"
                    >
                        <!-- Collapsed View -->
                        <div v-if="isCollapsed(column.id)" 
                             @click="toggleColumnCollapse(column.id)"
                             class="h-full flex flex-col items-center cursor-pointer rounded-lg transition-colors py-4 px-0"
                             title="Click to expand"
                        >
                            <div class="mb-4 pt-0">
                               <span class="block w-3 h-3 rounded-full" :style="{ backgroundColor: column.color || '#ccc' }"></span>
                            </div>
                            <div class="flex-1 flex items-center justify-center min-h-0 overflow-hidden py-4">
                                 <h3 class="font-bold text-gray-700 whitespace-nowrap text-sm tracking-wide select-none" style="writing-mode: vertical-rl; text-orientation: mixed;">
                                    {{ column.name }} 
                                    <span class="text-xs text-gray-500 font-normal ml-1">({{ column.tasks.length }})</span>
                                 </h3>
                            </div>
                            <div class="mt-4 pb-2 text-gray-400">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                      <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h4a1 1 0 010 2H6.414l5.293 5.293a1 1 0 01-1.414 1.414L5 6.414V8a1 1 0 01-2 0V4zm9 13a1 1 0 011-1h1.586l-5.293-5.293a1 1 0 111.414-1.414L16 13.586V12a1 1 0 112 0v4a1 1 0 01-1 1h-4a1 1 0 01-1-1z" clip-rule="evenodd" />
                                 </svg>
                            </div>
                        </div>

                        <!-- Expanded View -->
                        <template v-else>
                            <div class="p-4 flex justify-between items-center relative">
                                <h3 class="font-bold text-gray-700 flex items-center">
                                    <span class="w-3 h-3 rounded-full mr-2" :style="{ backgroundColor: column.color || '#ccc' }"></span>
                                    {{ column.name }}
                                    <span class="ml-2 text-xs text-gray-400 font-normal">({{ column.tasks.length }})</span>
                                </h3>
                                <div class="flex items-center">
                                    <button @click="toggleColumnCollapse(column.id)" class="text-gray-400 hover:text-gray-600 focus:outline-none mr-1 p-1 rounded hover:bg-gray-200" title="Collapse Column">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                                        </svg>
                                    </button>
                                    
                                    <div class="relative">
                                        <button @click.stop="activeColumnMenu = activeColumnMenu === column.id ? null : column.id" class="text-gray-400 hover:text-gray-600 focus:outline-none p-1 rounded hover:bg-gray-200">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>
                                        
                                        <!-- Column Menu -->
                                        <div v-if="activeColumnMenu === column.id" @click.stop class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-20 border border-gray-200 py-1">
                                            <button @click="openColumnModal(column)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 italic">
                                                Rename / Change Color
                                            </button>
                                            <button @click="openBulkImportModal(column)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Import Tasks (Bulk)
                                            </button>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <button v-if="index > 0" @click="moveColumn(column.id, 'left')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Move Left
                                            </button>
                                            <button v-if="index < localColumns.length - 1" @click="moveColumn(column.id, 'right')" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                Move Right
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
    
                            <!-- Draggable Tasks -->
                            <draggable 
                                class="flex-1 overflow-y-auto px-2 space-y-3 pb-4 min-h-[50px]"
                                :list="column.tasks"
                                group="tasks"
                                item-key="id"
                                @end="onMove"
                                :data-column-id="column.id"
                            >
                                <template #item="{ element }">
                                    <div @click="openEditModal(element)" class="bg-white p-2.5 rounded-lg shadow-sm border border-gray-200 cursor-pointer hover:shadow-md transition-shadow relative overflow-hidden">
                                        <div class="absolute left-0 top-0 bottom-0 w-1" :class="{
                                            'bg-red-500': element.priority === 'urgent',
                                            'bg-orange-500': element.priority === 'high',
                                            'bg-blue-500': element.priority === 'medium',
                                            'bg-gray-300': element.priority === 'low'
                                        }"></div>
                                        
                                        <div class="pl-1.5">
                                            <div class="flex items-start justify-between gap-2">
                                                <h4 class="text-xs font-semibold text-gray-900 leading-snug flex-1">{{ element.title }}</h4>
                                                
                                                <!-- Status Indicators -->
                                                <div class="flex-shrink-0 flex space-x-1">
                                                    <span v-if="element.status === 'blocked'" title="Blocked" class="text-red-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                    <span v-if="element.status === 'completed'" title="Completed" class="text-green-500">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                                        </svg>
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <p v-if="element.description" class="text-[10px] text-gray-500 line-clamp-1 mt-1">{{ element.description }}</p>
                                            
                                            <div class="mt-2.5 flex items-center justify-between">
                                                <div class="flex items-center space-x-2">
                                                    <div class="w-5 h-5 rounded-full bg-indigo-100 flex items-center justify-center text-[9px] font-bold text-indigo-700 uppercase">
                                                        {{ element.creator?.name?.charAt(0) || 'U' }}
                                                    </div>
                                                    <div v-if="element.comments?.length" class="flex items-center text-gray-400 text-[10px]">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                                        </svg>
                                                        <span class="font-medium">{{ element.comments.length }}</span>
                                                    </div>
                                                </div>
                                                <div v-if="element.due_date" class="flex items-center text-gray-400 text-[9px] font-medium">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-2.5 w-2.5 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                    </svg>
                                                    {{ new Date(element.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </draggable>
    
                            <!-- Add Task -->
                            <div class="p-2 border-t border-gray-200">
                                <div v-if="showNewTaskInput === column.id">
                                    <textarea 
                                        v-model="newTaskForm.title"
                                        class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        rows="2"
                                        placeholder="Enter task title..."
                                        @keyup.enter.prevent="addTask(column.id)"
                                        @blur="addTask(column.id)"
                                        v-focus
                                    ></textarea>
                                </div>
                                <button 
                                    v-else
                                    @click="showNewTaskInput = column.id"
                                    class="w-full text-left px-3 py-2 text-sm text-gray-500 hover:bg-gray-200 rounded-md flex items-center"
                                >
                                    <span class="mr-2 text-lg">+</span> Add a card
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Add Column -->
                    <div class="flex-shrink-0 w-80">
                        <div v-if="showAddColumnInput" class="bg-gray-100 rounded-lg p-3 border border-gray-200">
                            <input 
                                v-model="addColumnForm.name"
                                type="text" 
                                class="w-full border-gray-300 rounded-md shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 mb-2"
                                placeholder="Column name..."
                                @keyup.enter="addColumn"
                                @keyup.esc="showAddColumnInput = false"
                                v-focus
                            >
                            <div class="flex space-x-2">
                                <button @click="addColumn" class="px-3 py-1 bg-indigo-600 text-white rounded text-xs font-medium hover:bg-indigo-700">Add Column</button>
                                <button @click="showAddColumnInput = false" class="px-3 py-1 bg-white border border-gray-300 rounded text-xs font-medium hover:bg-gray-50 text-gray-700">Cancel</button>
                            </div>
                        </div>
                        <div 
                            v-else 
                            @click="showAddColumnInput = true"
                            class="bg-gray-100/50 rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center cursor-pointer hover:bg-gray-100 transition-colors h-14"
                        >
                            <span class="text-gray-500 font-medium">+ Add Section</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Task Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showEditModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="updateTask">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-gray-900">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Edit Task
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Task Title</label>
                                    <input v-model="editTaskForm.title" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="editTaskForm.errors.title" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.title }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea v-model="editTaskForm.description" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Add a more detailed description..."></textarea>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Priority</label>
                                        <select v-model="editTaskForm.priority" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="low">Low</option>
                                            <option value="medium">Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                        <div v-if="editTaskForm.errors.priority" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.priority }}</div>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Status</label>
                                        <select v-model="editTaskForm.status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="open">Open</option>
                                            <option value="in_progress">In Progress</option>
                                            <option value="completed">Completed</option>
                                            <option value="blocked">Blocked</option>
                                        </select>
                                        <div v-if="editTaskForm.errors.status" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.status }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Due Date</label>
                                    <input v-model="editTaskForm.due_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="editTaskForm.errors.due_date" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.due_date }}</div>
                                </div>

                                <!-- Comments Section -->
                                <div class="border-t border-gray-200 pt-6 mt-6">
                                    <h4 class="text-md font-bold text-gray-900 mb-4 flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        Comments
                                    </h4>
                                    
                                    <div class="space-y-4 mb-6 max-h-60 overflow-y-auto pr-2">
                                        <div v-for="comment in selectedTask?.comments" :key="comment.id" class="flex space-x-3">
                                            <div class="flex-shrink-0">
                                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-xs font-bold text-indigo-700 uppercase">
                                                    {{ comment.user.name.charAt(0) }}
                                                </div>
                                            </div>
                                            <div class="flex-1 bg-gray-50 rounded-lg p-3">
                                                <div class="flex items-center justify-between mb-1">
                                                    <span class="text-xs font-bold text-gray-900">{{ comment.user.name }}</span>
                                                    <div class="flex items-center space-x-2">
                                                        <span class="text-[10px] text-gray-500">{{ new Date(comment.created_at).toLocaleString() }}</span>
                                                        <button v-if="comment.user_id === $page.props.auth.user.id" @click.stop.prevent="deleteComment(comment.id)" class="text-red-400 hover:text-red-600">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor">
                                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ comment.comment }}</p>
                                            </div>
                                        </div>
                                        <div v-if="!selectedTask?.comments?.length" class="text-center py-4 text-sm text-gray-400 italic">
                                            No comments yet. Be the first to chime in!
                                        </div>
                                    </div>

                                    <!-- New Comment Input -->
                                    <div class="flex space-x-3 mt-4">
                                        <div class="flex-shrink-0 pt-1">
                                            <div class="h-8 w-8 rounded-full bg-indigo-500 flex items-center justify-center text-xs font-bold text-white uppercase">
                                                {{ $page.props.auth.user.name.charAt(0) }}
                                            </div>
                                        </div>
                                        <div class="flex-1">
                                            <textarea v-model="commentForm.comment" rows="2" class="block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Write a comment..."></textarea>
                                            <div class="mt-2 flex justify-end">
                                                <button @click.stop.prevent="addComment" :disabled="commentForm.processing || !commentForm.comment.trim()" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 transition-colors">
                                                    Post Comment
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="editTaskForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Changes
                            </button>
                            <button @click="deleteTask" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-red-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-red-600 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Delete Task
                            </button>
                            <button @click="showEditModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Column Modal -->
        <div v-if="showColumnModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showColumnModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="updateColumn">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-gray-900">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Edit Column
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Column Name</label>
                                    <input v-model="columnForm.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Column Color</label>
                                    <div class="mt-2 flex items-center space-x-2">
                                        <input v-model="columnForm.color" type="color" class="h-8 w-8 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-500">{{ columnForm.color }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="columnForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Changes
                            </button>
                            <button @click="showColumnModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Import Tasks Modal -->
        <div v-if="showBulkImportModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showBulkImportModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="importTasks">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-gray-900">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-2" id="modal-title">
                                Bulk Import Tasks
                            </h3>
                            <p class="text-sm text-gray-500 mb-4">Paste your tasks below. Each line will create a new task.</p>
                            <div class="space-y-4">
                                <div>
                                    <textarea 
                                        v-model="bulkImportForm.tasks_text" 
                                        rows="10" 
                                        required 
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Task 1&#10;Task 2&#10;Task 3..."
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="bulkImportForm.processing || !bulkImportForm.tasks_text.trim()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Import Tasks
                            </button>
                            <button @click="showBulkImportModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  line-clamp: 2;
  -webkit-line-clamp: 2;
  line-clamp: 2;
  -webkit-box-orient: vertical;  
  overflow: hidden;
}
</style>
