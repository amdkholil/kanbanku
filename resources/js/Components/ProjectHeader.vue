<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    project: Object,
    board: Object
});

defineEmits(['open-member-modal']);
</script>

<template>
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
        <h2 class="font-semibold text-md text-gray-800 leading-tight">
            {{ project.name }} / {{ board.name }}
        </h2>
        <div class="flex justify-between sm:items-center gap-4 w-full sm:w-auto">
            <div class="flex bg-gray-100 p-1 rounded-md sm:w-auto">
                <Link :href="route('projects.show', project.slug)" 
                    class="flex-1 sm:flex-none text-center px-4 py-1.5 sm:py-1 text-xs font-medium rounded-md transition-colors"
                    :class="route().current('projects.show') ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    Board
                </Link>
                <Link :href="route('projects.list', project.slug)" 
                    class="flex-1 sm:flex-none text-center px-4 py-1.5 sm:py-1 text-xs font-medium rounded-md transition-colors"
                    :class="route().current('projects.list') ? 'bg-white shadow-sm text-indigo-600' : 'text-gray-500 hover:text-gray-700'"
                >
                    List
                </Link>
            </div>
            <div class="flex items-center justify-between sm:justify-end sm:w-auto">
                <div class="lg:flex -space-x-2 overflow-hidden mr-3 hidden">
                    <div v-for="member in (project.members ? project.members.slice(0, 5) : [])" :key="member.id" 
                        class="h-8 w-8 sm:h-7 sm:w-7 rounded-full ring-2 ring-white bg-indigo-100 flex items-center justify-center text-[10px] sm:text-[9px] font-bold text-indigo-700 uppercase"
                        :title="member.user?.name">
                        {{ member.user?.name?.charAt(0) || 'U' }}
                    </div>
                    <div v-if="(project.members ? project.members.length : 0) > 5" 
                        class="h-8 w-8 sm:h-7 sm:w-7 rounded-full ring-2 ring-white bg-gray-100 flex items-center justify-center text-[10px] sm:text-[9px] font-bold text-gray-600">
                        +{{ (project.members ? project.members.length : 0) - 5 }}
                    </div>
                </div>
                <button @click="$emit('open-member-modal')" class="p-2 sm:p-1 sm:px-2 border border-blue-100 bg-blue-50 sm:bg-transparent sm:border-gray-300 rounded-lg sm:rounded-md text-xs sm:text-[10px] font-bold text-blue-600 sm:text-gray-600 hover:bg-blue-100 sm:hover:bg-gray-50 flex items-center uppercase tracking-wider transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 sm:h-3 sm:w-3 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Invite
                </button>
            </div>
        </div>
    </div>
</template>
