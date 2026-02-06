<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    projects: Array
});

const showModal = ref(false);
const showEditModal = ref(false);
const showMemberModal = ref(false);
const showDeleteModal = ref(false);
const activeProjectMenu = ref(null);

const form = useForm({
    name: '',
    description: '',
    color: '#4f46e5'
});

const editForm = useForm({
    id: null,
    name: '',
    description: '',
    color: '#4f46e5'
});

const memberForm = useForm({
    project_id: null,
    email: '',
});

const deleteForm = useForm({
    id: null,
    confirm_email: '',
});

const submit = () => {
    form.post(route('projects.store'), {
        onSuccess: () => {
            showModal.value = false;
            form.reset();
        }
    });
};

const openEditModal = (project) => {
    editForm.id = project.id;
    editForm.name = project.name;
    editForm.description = project.description;
    editForm.color = project.color;
    showEditModal.value = true;
    activeProjectMenu.value = null;
};

const submitUpdate = () => {
    editForm.patch(route('projects.update', editForm.id), {
        onSuccess: () => {
            showEditModal.value = false;
        }
    });
};

const openMemberModal = (project) => {
    memberForm.project_id = project.id;
    memberForm.email = '';
    showMemberModal.value = true;
    activeProjectMenu.value = null;
};

const submitMember = () => {
    memberForm.post(route('projects.members.store', memberForm.project_id), {
        onSuccess: () => {
            showMemberModal.value = false;
            memberForm.reset();
        }
    });
};

const openDeleteModal = (project) => {
    deleteForm.id = project.id;
    deleteForm.confirm_email = '';
    showDeleteModal.value = true;
    activeProjectMenu.value = null;
};

const submitDelete = () => {
    deleteForm.delete(route('projects.destroy', deleteForm.id), {
        onSuccess: () => {
            showDeleteModal.value = false;
        }
    });
};

const closeMenu = () => {
    activeProjectMenu.value = null;
};

onMounted(() => {
    window.addEventListener('click', closeMenu);
});

onUnmounted(() => {
    window.removeEventListener('click', closeMenu);
});
</script>

<template>
    <Head title="Projects" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">My Projects</h2>
                <button @click="showModal = true" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    New Project
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div v-for="project in projects" :key="project.id" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-t-4 hover:shadow-md transition-shadow relative" :style="{ borderTopColor: project.color || '#ccc' }">
                        <!-- Project Menu -->
                        <div v-if="project.owner_id == $page.props.auth.user.id" class="absolute right-2 top-2">
                             <button @click.stop="activeProjectMenu = activeProjectMenu === project.id ? null : project.id" class="text-gray-400 hover:text-gray-600 focus:outline-none p-1">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                     <path d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM18 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                 </svg>
                             </button>

                             <div v-if="activeProjectMenu === project.id" @click.stop class="absolute right-0 mt-1 w-40 bg-white rounded-md shadow-lg z-20 border border-gray-100 py-1">
                                 <button @click="openEditModal(project)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                     Rename Project
                                 </button>
                                 <button @click="openMemberModal(project)" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                     Add Member
                                 </button>
                                 <div class="border-t border-gray-100 my-1"></div>
                                 <button @click="openDeleteModal(project)" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                     Delete Project
                                 </button>
                             </div>
                        </div>

                        <div class="p-6 text-gray-900 flex flex-col h-full">
                            <h3 class="text-lg font-bold pr-6">{{ project.name }}</h3>
                            <p class="text-gray-600 text-sm mt-2 line-clamp-2 h-10">{{ project.description }}</p>
                            
                            <div class="mt-4 flex justify-end">
                                <Link :href="route('projects.show', project.slug)" class="text-indigo-600 hover:text-indigo-900 font-medium text-sm flex items-center">
                                    Open Board
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div @click="showModal = true" class="bg-white overflow-hidden shadow-sm sm:rounded-lg border-2 border-dashed border-gray-300 flex items-center justify-center p-6 cursor-pointer hover:border-indigo-400 hover:bg-gray-50 transition-all duration-200 min-h-[160px]">
                         <div class="text-center">
                             <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-indigo-100 mb-2">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                     <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                 </svg>
                             </div>
                             <p class="text-gray-500 font-medium">Create New Project</p>
                         </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Member Modal -->
        <div v-if="showMemberModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showMemberModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitMember">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Member</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Member Email</label>
                                    <input v-model="memberForm.email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="user@example.com">
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

        <!-- Edit Project Modal -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showEditModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitUpdate">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Rename Project</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Project Name</label>
                                    <input v-model="editForm.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea v-model="editForm.description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Color Theme</label>
                                    <input v-model="editForm.color" type="color" class="mt-2 h-8 w-8 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="editForm.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Changes
                            </button>
                            <button @click="showEditModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Project Modal -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showDeleteModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submitDelete">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-red-600 mb-2">Delete Project</h3>
                            <p class="text-sm text-gray-500 mb-4">This action is permanent and cannot be undone. All tasks, boards, and data will be destroyed.</p>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Please type <span class="font-bold text-gray-900">{{ $page.props.auth.user.email }}</span> to confirm</label>
                                    <input v-model="deleteForm.confirm_email" type="email" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-red-500 focus:ring-red-500 sm:text-sm" :placeholder="$page.props.auth.user.email">
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="deleteForm.processing || deleteForm.confirm_email !== $page.props.auth.user.email" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm disabled:opacity-50">
                                Delete Permanently
                            </button>
                            <button @click="showDeleteModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Create Project Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div @click="showModal = false" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <form @submit.prevent="submit">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4" id="modal-title">
                                Create New Project
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Project Name</label>
                                    <input v-model="form.name" type="text" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="e.g. Website Redesign">
                                    <div v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea v-model="form.description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Briefly describe what this project is about..."></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Color Theme</label>
                                    <div class="mt-2 flex items-center space-x-2">
                                        <input v-model="form.color" type="color" class="h-8 w-8 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-500">{{ form.color }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" :disabled="form.processing" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                                <span v-if="form.processing">Creating...</span>
                                <span v-else>Create Project</span>
                            </button>
                            <button @click="showModal = false" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
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
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;  
  overflow: hidden;
  line-clamp: 2;
}
</style>
