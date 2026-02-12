<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import { ref, watch, onMounted, onUnmounted } from 'vue';
import ProjectHeader from '@/Components/ProjectHeader.vue';
import axios from 'axios';

const props = defineProps({
    project: Object,
    board: Object,
    allFlags: Array
});

const localColumns = ref([]);

// Initialize and watch for prop changes
watch(() => props.board.columns, (newVal) => {
    localColumns.value = JSON.parse(JSON.stringify(newVal));
}, { immediate: true, deep: true });

const newTaskForm = useForm({
    title: '',
    column_id: null,
    flags: ''
});

const selectedTask = ref(null);

const editTaskForm = useForm({
    id: null,
    title: '',
    description: '',
    priority: 'medium',
    status: 'open',
    column_id: null,
    due_date: null,
    flags: ''
});

const commentForm = useForm({
    comment: ''
});

const showEditModal = ref(false);

const openEditModal = (task) => {
    selectedTask.value = task;
    editTaskForm.id = task.id;
    editTaskForm.title = task.title;
    editTaskForm.description = task.description || '';
    editTaskForm.priority = task.priority;
    editTaskForm.status = task.status;
    editTaskForm.column_id = task.column_id;
    editTaskForm.due_date = task.due_date;
    editTaskForm.flags = task.flags?.map(f => f.name).join(', ') || '';
    showEditModal.value = true;
};

const updateTask = () => {
    editTaskForm.patch(route('tasks.update', editTaskForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};

const addComment = () => {
    commentForm.post(route('comments.store', editTaskForm.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
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
        return;
    }
    
    newTaskForm.column_id = columnId;
    newTaskForm.post(route('tasks.store'), {
        onSuccess: () => {
            newTaskForm.reset();
        }
    });
};

const getPriorityColor = (priority) => {
    switch (priority) {
        case 'urgent': return 'bg-red-500';
        case 'high': return 'bg-orange-500';
        case 'medium': return 'bg-blue-500';
        case 'low': return 'bg-gray-300';
        default: return 'bg-gray-300';
    }
};

const getStatusColor = (status) => {
    switch (status) {
        case 'completed': return 'text-green-600 bg-green-50 border-green-200';
        case 'blocked': return 'text-red-600 bg-red-50 border-red-200';
        case 'in_progress': return 'text-blue-600 bg-blue-50 border-blue-200';
        default: return 'text-gray-600 bg-gray-50 border-gray-200';
    }
};

const vFocus = {
  mounted: (el) => el.focus()
};

const showQuickAddTask = ref(null);
const showMemberModal = ref(false);

const memberForm = useForm({
    email: '',
});

const openMemberModal = () => {
    memberForm.email = '';
    showMemberModal.value = true;
};

const submitMember = () => {
    memberForm.post(route('projects.members.store', props.project.id), {
        onSuccess: () => {
            showMemberModal.value = false;
            memberForm.reset();
        }
    });
};

</script>

<template>
    <Head :title="project.name + ' - List View'" />

    <AuthenticatedLayout>
        <template #header>
            <ProjectHeader 
                :project="project" 
                :board="board" 
                @open-member-modal="openMemberModal" 
            />
        </template>

        <div class="py-4 sm:py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="space-y-6 sm:y-8">
                <div v-for="column in localColumns" :key="column.id" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    <!-- Column Header -->
                    <div class="px-4 sm:px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-gray-50/50">
                        <div class="flex items-center space-x-3">
                            <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: column.color || '#ccc' }"></span>
                            <h3 class="text-sm font-bold text-gray-900 uppercase tracking-wider">{{ column.name }}</h3>
                            <span class="px-2 py-0.5 bg-gray-200 rounded-full text-[10px] font-bold text-gray-600">{{ column.tasks.length }}</span>
                        </div>
                        <button @click="showQuickAddTask = column.id" class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            <span class="hidden sm:inline">Add Task</span>
                            <span class="sm:hidden">Add</span>
                        </button>
                    </div>

                    <!-- Quick Add Task -->
                    <div v-if="showQuickAddTask === column.id" class="px-4 sm:px-6 py-4 bg-indigo-50/30 border-b border-indigo-100">
                        <div class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                            <input 
                                v-model="newTaskForm.title" 
                                v-focus
                                type="text" 
                                placeholder="What needs to be done?"
                                class="flex-1 text-sm border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500"
                                @keyup.enter="addTask(column.id)"
                            >
                            <div class="flex gap-2">
                                <button 
                                    @click="addTask(column.id)"
                                    class="flex-1 sm:flex-none px-4 py-2 bg-indigo-600 text-white text-sm font-bold rounded-lg hover:bg-indigo-700 transition-colors"
                                >
                                    Add Task
                                </button>
                                <button 
                                    @click="showQuickAddTask = null"
                                    class="flex-1 sm:flex-none px-4 py-2 text-gray-500 text-sm font-bold hover:text-gray-700 bg-white border border-gray-200 rounded-lg sm:border-0"
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Task List Mobile (Card Based) -->
                    <div class="block sm:hidden divide-y divide-gray-100">
                        <div v-for="task in column.tasks" :key="task.id" 
                            @click="openEditModal(task)"
                            class="p-4 active:bg-indigo-50 transition-colors flex gap-3"
                        >
                            <div class="w-1 rounded-full flex-shrink-0" :class="getPriorityColor(task.priority)"></div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start mb-1 gap-2">
                                    <h4 class="text-sm font-semibold text-gray-900 truncate">{{ task.title }}</h4>
                                    <div class="flex-shrink-0 flex items-center text-gray-400 text-[10px]">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                        </svg>
                                        {{ task.comments?.length || 0 }}
                                    </div>
                                </div>
                                
                                <p v-if="task.description" class="text-xs text-gray-500 line-clamp-1 mb-2">{{ task.description }}</p>
                                
                                <div class="flex flex-wrap gap-1.5 mb-2">
                                    <span v-for="flag in task.flags" :key="flag.id" 
                                        class="px-1.5 py-0.5 rounded text-[8px] font-bold text-white uppercase"
                                        :style="{ backgroundColor: flag.color || '#6b7280' }">
                                        {{ flag.name }}
                                    </span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-full text-[9px] font-bold border capitalize" 
                                            :class="getStatusColor(task.status)">
                                            {{ task.status.replace('_', ' ') }}
                                        </span>
                                        <span class="text-[9px] font-bold uppercase" :class="{
                                            'text-red-600': task.priority === 'urgent',
                                            'text-orange-600': task.priority === 'high',
                                            'text-blue-600': task.priority === 'medium',
                                            'text-gray-500': task.priority === 'low',
                                        }">
                                            {{ task.priority }}
                                        </span>
                                    </div>
                                    <div v-if="task.due_date" class="text-[9px] text-gray-500 font-medium flex items-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        {{ new Date(task.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric' }) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-if="!column.tasks.length" class="p-8 text-center text-xs text-gray-400 italic">
                            No tasks in this section yet.
                        </div>
                    </div>

                    <!-- Task List Desktop -->
                    <div class="hidden sm:block overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50/30">
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider w-1"></th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Title</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Flags</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider italic">Priority</th>
                                    <!-- <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Status</th> -->
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Due Date</th>
                                    <th class="px-6 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider">Comments</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                <tr v-for="task in column.tasks" :key="task.id" 
                                    @click="openEditModal(task)"
                                    class="group hover:bg-indigo-50/20 cursor-pointer transition-colors"
                                >
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="w-1.5 h-10 rounded-full" :class="getPriorityColor(task.priority)"></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">{{ task.title }}</span>
                                            <span v-if="task.description" class="text-xs text-gray-500 line-clamp-1 mt-0.5">{{ task.description }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap gap-1">
                                            <span v-for="flag in task.flags" :key="flag.id" 
                                                class="px-2 py-0.5 rounded text-[9px] font-bold text-white uppercase"
                                                :style="{ backgroundColor: flag.color || '#6b7280' }">
                                                {{ flag.name }}
                                            </span>
                                            <span v-if="!task.flags?.length" class="text-[10px] text-gray-300 italic">No flags</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-[10px] font-bold uppercase" :class="{
                                            'text-red-600': task.priority === 'urgent',
                                            'text-orange-600': task.priority === 'high',
                                            'text-blue-600': task.priority === 'medium',
                                            'text-gray-500': task.priority === 'low',
                                        }">
                                            {{ task.priority }}
                                        </span>
                                    </td>
                                    <!-- <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold border capitalize" 
                                            :class="getStatusColor(task.status)">
                                            {{ task.status.replace('_', ' ') }}
                                        </span>
                                    </td> -->
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div v-if="task.due_date" class="flex items-center text-xs text-gray-600 font-medium">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 mr-1.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            {{ new Date(task.due_date).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' }) }}
                                        </div>
                                        <span v-else class="text-[10px] text-gray-300 italic">No date</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-gray-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                            </svg>
                                            <span class="text-xs font-bold">{{ task.comments?.length || 0 }}</span>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-if="!column.tasks.length">
                                    <td colspan="7" class="px-6 py-10 text-center text-sm text-gray-400 italic bg-gray-50/20">
                                        No tasks in this section yet.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Task Modal (same as Show.vue) -->
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
                                        <label class="block text-sm font-medium text-gray-700">Status (Column)</label>
                                        <select v-model="editTaskForm.column_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option v-for="column in board.columns" :key="column.id" :value="column.id">
                                                {{ column.name }}
                                            </option>
                                        </select>
                                        <div v-if="editTaskForm.errors.column_id" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.column_id }}</div>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Due Date</label>
                                    <input v-model="editTaskForm.due_date" type="date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <div v-if="editTaskForm.errors.due_date" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.due_date }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Flags (comma separated)</label>
                                    <input 
                                        v-model="editTaskForm.flags" 
                                        type="text" 
                                        list="flags-list"
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" 
                                        placeholder="e.g. urgent, frontend, bug"
                                    >
                                    <div v-if="editTaskForm.errors.flags" class="text-red-500 text-xs mt-1">{{ editTaskForm.errors.flags }}</div>
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

        <datalist id="flags-list">
            <option v-for="flag in allFlags" :key="flag.id" :value="flag.name" />
        </datalist>

        <!-- Add Member Modal -->
        <div v-if="showMemberModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showMemberModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitMember">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-gray-900">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Member to {{ project.name }}</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Member Email</label>
                                    <input v-model="memberForm.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="user@example.com" v-focus>
                                    <div v-if="memberForm.errors.email" class="text-red-500 text-xs mt-1">{{ memberForm.errors.email }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="memberForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Invite Member
                            </button>
                            <button @click="showMemberModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
