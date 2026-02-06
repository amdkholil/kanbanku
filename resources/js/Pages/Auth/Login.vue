<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const mode = ref('login'); // 'login' or 'register'

const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const registerForm = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submitLogin = () => {
    loginForm.post(route('login'), {
        onFinish: () => loginForm.reset('password'),
    });
};

const submitRegister = () => {
    registerForm.post(route('register'), {
        onFinish: () => registerForm.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head :title="mode === 'login' ? 'Log in' : 'Register'" />

        <!-- Mode Toggle -->
        <div class="mb-8 flex p-1 bg-slate-100 rounded-xl">
            <button 
                @click="mode = 'login'"
                :class="mode === 'login' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-2 text-sm font-bold rounded-lg transition-all"
            >
                Log In
            </button>
            <button 
                @click="mode = 'register'"
                :class="mode === 'register' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500 hover:text-slate-700'"
                class="flex-1 py-2 text-sm font-bold rounded-lg transition-all"
            >
                Register
            </button>
        </div>

        <div v-if="status" class="mb-4 text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <!-- Login Form -->
        <form v-if="mode === 'login'" @submit.prevent="submitLogin" class="animate-in fade-in duration-500">
            <div>
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="loginForm.email"
                    required
                    autofocus
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="loginForm.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="loginForm.password"
                    required
                    autocomplete="current-password"
                />
                <InputError class="mt-2" :message="loginForm.errors.password" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="loginForm.remember" />
                    <span class="ms-2 text-sm text-gray-600">Remember me</span>
                </label>
                
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-indigo-600 hover:text-indigo-500 font-medium"
                >
                    Forgot password?
                </Link>
            </div>

            <div class="mt-8">
                <PrimaryButton
                    class="w-full justify-center py-3 text-base shadow-lg shadow-indigo-200"
                    :class="{ 'opacity-25': loginForm.processing }"
                    :disabled="loginForm.processing"
                >
                    Login
                </PrimaryButton>
            </div>
        </form>

        <!-- Register Form -->
        <form v-else @submit.prevent="submitRegister" class="animate-in fade-in duration-500">
            <div>
                <InputLabel for="name" value="Full Name" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="registerForm.name"
                    required
                    autofocus
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="registerForm.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email Address" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="registerForm.email"
                    required
                    autocomplete="username"
                />
                <InputError class="mt-2" :message="registerForm.errors.email" />
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <InputLabel for="password" value="Password" />
                    <TextInput
                        id="password"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="registerForm.password"
                        required
                        autocomplete="new-password"
                    />
                </div>
                <div>
                    <InputLabel for="password_confirmation" value="Confirm" />
                    <TextInput
                        id="password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        v-model="registerForm.password_confirmation"
                        required
                        autocomplete="new-password"
                    />
                </div>
            </div>
            <InputError class="mt-2" :message="registerForm.errors.password" />
            <InputError class="mt-2" :message="registerForm.errors.password_confirmation" />

            <div class="mt-8">
                <PrimaryButton
                    class="w-full justify-center py-3 text-base shadow-lg shadow-indigo-200"
                    :class="{ 'opacity-25': registerForm.processing }"
                    :disabled="registerForm.processing"
                >
                    Create Account
                </PrimaryButton>
            </div>
            
            <p class="mt-4 text-center text-xs text-slate-500">
                By joining, you agree to our Terms of Service and Privacy Policy.
            </p>
        </form>
    </GuestLayout>
</template>
