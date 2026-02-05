<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="Welcome to Kanbanku" />
    
    <div class="min-h-screen bg-slate-50 flex flex-col items-center justify-center p-6 text-slate-900">
        <!-- Hero Section -->
        <div class="max-w-4xl w-full text-center space-y-8">
            <div class="flex justify-center mb-4">
                <div class="bg-indigo-600 p-3 rounded-2xl shadow-lg rotate-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
            </div>

            <h1 class="text-5xl md:text-6xl font-extrabold tracking-tight text-slate-900">
                Manage your projects with <span class="text-indigo-600">Kanbanku</span>
            </h1>
            
            <p class="text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
                A simple, powerful, and beautiful Kanban board for modern teams. 
                Organize tasks, collaborate with comments, and streamline your workflow effortlessly.
            </p>

            <!-- Action Buttons -->
            <div v-if="canLogin" class="flex flex-col sm:flex-row items-center justify-center gap-4 mt-10">
                <template v-if="$page.props.auth.user">
                    <Link :href="route('dashboard')" class="px-8 py-4 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                        Go to Dashboard
                    </Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="w-full sm:w-auto px-8 py-4 bg-indigo-600 text-white rounded-xl font-bold shadow-lg hover:bg-indigo-700 transition-all transform hover:-translate-y-1">
                        Get Started
                    </Link>
                    <Link v-if="canRegister" :href="route('register')" class="w-full sm:w-auto px-8 py-4 bg-white text-indigo-600 border-2 border-indigo-50 rounded-xl font-bold hover:bg-indigo-50 transition-all">
                        Create Account
                    </Link>
                </template>
            </div>
        </div>

        <!-- Feature Grid -->
        <div class="max-w-6xl w-full grid md:grid-cols-3 gap-8 mt-24">
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-indigo-600 mb-4 font-bold">01. Drag & Drop</div>
                <h3 class="text-lg font-bold mb-2">Intuitive Interface</h3>
                <p class="text-slate-500 text-sm">Move tasks between columns seamlessly with our smooth drag-and-drop experience.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-indigo-600 mb-4 font-bold">02. Stay Connected</div>
                <h3 class="text-lg font-bold mb-2">Team Collaboration</h3>
                <p class="text-slate-500 text-sm">Every task has its own discussion thread. Mention teammates and share updates in real-time.</p>
            </div>
            <div class="bg-white p-8 rounded-2xl shadow-sm border border-slate-100">
                <div class="text-indigo-600 mb-4 font-bold">03. Bulk Import</div>
                <h3 class="text-lg font-bold mb-2">Lightning Fast</h3>
                <p class="text-slate-500 text-sm">Import dozens of tasks at once just by pasting text. We'll handle the rest.</p>
            </div>
        </div>

        <footer class="mt-24 text-slate-400 text-sm italic">
            &copy; 2026 Kanbanku App. Empowering productivity.
        </footer>
    </div>
</template>

<style scoped>
/* Optional: add some nice subtle background animations or effects here */
</style>
