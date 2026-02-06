<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>

    <Head title="Welcome to Kanbanku" />

    <div class="min-h-screen bg-slate-50 flex flex-col text-slate-900 font-sans selection:bg-blue-100 selection:text-blue-900">
        <!-- Navbar -->
        <nav class="w-full max-w-7xl mx-auto px-6 py-6 flex justify-between items-center z-10">
            <div class="flex items-center gap-3 group cursor-pointer">
                 <div class="bg-blue-600 p-2 rounded-xl -rotate-3 group-hover:rotate-0 transition-all duration-300 shadow-lg shadow-blue-200">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                 </div>
                 <span class="text-xl font-bold tracking-tight text-slate-800 group-hover:text-blue-600 transition-colors">Kanbanku</span>
            </div>
            
            <div v-if="canLogin" class="flex items-center gap-4">
                <template v-if="$page.props.auth.user">
                     <Link :href="route('dashboard')" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition-colors">Dashboard</Link>
                </template>
                <template v-else>
                    <Link :href="route('login')" class="text-sm font-bold text-slate-600 hover:text-blue-600 transition-colors">Log in</Link>
                    <Link v-if="canRegister" :href="route('register')" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-200 hover:bg-blue-700 hover:shadow-xl hover:-translate-y-0.5 transition-all">Get Started</Link>
                </template>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1 w-full max-w-7xl mx-auto px-6 flex flex-col items-center justify-center py-12 lg:py-20">
            
            <!-- Hero -->
            <div class="text-center max-w-4xl mx-auto space-y-8 mb-24 relative">
                <!-- Decorative blob -->
                <div class="absolute -top-20 -left-20 w-72 h-72 bg-blue-200 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob"></div>
                <div class="absolute -bottom-20 -right-20 w-72 h-72 bg-purple-200 rounded-full mix-blend-multiply filter blur-2xl opacity-30 animate-blob animation-delay-2000"></div>

                <div class="relative">
                    <div class="inline-block mb-4 px-4 py-1.5 rounded-full bg-blue-50 border border-blue-100 text-blue-600 text-sm font-semibold tracking-wide uppercase">
                        v1.0 is now live
                    </div>
                    <h1 class="text-5xl md:text-7xl font-extrabold tracking-tight text-slate-900 leading-[1.1]">
                        Manage your projects<br class="hidden md:block" />
                        <span class="text-transparent bg-clip-text bg-gradient-to-r from-blue-600 to-indigo-600">with Kanbanku</span>
                    </h1>
                </div>

                <p class="text-lg md:text-xl text-slate-600 max-w-2xl mx-auto leading-relaxed">
                    A simple, powerful, and beautiful Kanban board for modern teams. Organize tasks, collaborate with comments, and streamline your workflow effortlessly.
                </p>

                <!-- CTA -->
                 <div class="flex flex-col sm:flex-row justify-center gap-4 mt-8">
                    <template v-if="$page.props.auth.user">
                        <Link :href="route('dashboard')" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold text-lg shadow-xl shadow-blue-200 hover:shadow-2xl hover:bg-blue-700 transition-all transform hover:-translate-y-1">
                            Go to Dashboard
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="route('register')" class="px-8 py-4 bg-blue-600 text-white rounded-2xl font-bold text-lg shadow-xl shadow-blue-200 hover:shadow-2xl hover:bg-blue-700 transition-all transform hover:-translate-y-1">
                            Start for free
                        </Link>
                        <Link :href="route('login')" class="px-8 py-4 bg-white text-slate-700 border border-slate-200 rounded-2xl font-bold text-lg shadow-lg shadow-slate-100 hover:bg-slate-50 hover:border-slate-300 transition-all transform hover:-translate-y-1">
                            Log in
                        </Link>
                    </template>
                 </div>
            </div>

            <!-- Features -->
            <div id="features" class="grid md:grid-cols-3 gap-8 w-full">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-slate-200/50 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center mb-6 text-blue-600 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </div>
                    <div class="text-blue-600 text-xs font-bold tracking-wider uppercase mb-2">Drag & Drop</div>
                    <h3 class="text-xl font-bold mb-3 text-slate-900">Intuitive Interface</h3>
                    <p class="text-slate-500 leading-relaxed">Move tasks between columns seamlessly with our smooth drag-and-drop experience. It feels just right.</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-slate-200/50 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-purple-50 rounded-2xl flex items-center justify-center mb-6 text-purple-600 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z" />
                        </svg>
                    </div>
                    <div class="text-purple-600 text-xs font-bold tracking-wider uppercase mb-2">Collaboration</div>
                    <h3 class="text-xl font-bold mb-3 text-slate-900">Stay Connected</h3>
                    <p class="text-slate-500 leading-relaxed">Every task has its own discussion thread. Mention teammates and share updates in real-time.</p>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-slate-100 hover:shadow-xl hover:shadow-slate-200/50 hover:-translate-y-1 transition-all duration-300 group">
                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center mb-6 text-indigo-600 group-hover:scale-110 transition-transform duration-300">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="text-indigo-600 text-xs font-bold tracking-wider uppercase mb-2">Lightning Fast</div>
                    <h3 class="text-xl font-bold mb-3 text-slate-900">Bulk Import</h3>
                    <p class="text-slate-500 leading-relaxed">Need to add many tasks? Import dozens at once just by pasting text. We'll handle the parsing.</p>
                </div>
            </div>

        </main>

        <footer class="w-full border-t border-slate-100 bg-white/50 backdrop-blur-sm mt-auto">
            <div class="max-w-7xl mx-auto px-6 py-8 flex flex-col md:flex-row justify-between items-center text-slate-400 text-sm font-medium">
                <p>&copy; 2026 Kanbanku. Empowering productivity.</p>
                <div class="flex gap-8 mt-4 md:mt-0">
                    <a href="#" class="hover:text-blue-600 transition-colors">Privacy Policy</a>
                    <a href="#" class="hover:text-blue-600 transition-colors">Terms of Service</a>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
/* Optional: add some nice subtle background animations or effects here */
</style>
