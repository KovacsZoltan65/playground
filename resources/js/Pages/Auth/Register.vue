<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import InputText from 'primevue/inputtext';
import Password from 'primevue/password';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Register" />

    <div class="app-shell relative min-h-screen overflow-hidden">
        <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_left,_rgba(16,185,129,0.18),_transparent_28%),radial-gradient(circle_at_bottom_right,_rgba(14,165,233,0.14),_transparent_28%)]"></div>

        <div class="relative grid min-h-screen lg:grid-cols-[1.05fr_0.95fr]">
            <section class="hidden bg-slate-950 px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between xl:px-16">
                <div>
                    <Link href="/" class="inline-flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-[1.5rem] bg-gradient-to-br from-emerald-400 to-sky-400 text-2xl font-bold text-slate-950">
                            P
                        </div>
                        <div>
                            <div class="text-xs uppercase tracking-[0.35em] text-emerald-300">Sakai inspired</div>
                            <div class="text-3xl font-semibold tracking-tight">Playground</div>
                        </div>
                    </Link>

                    <div class="mt-16 max-w-xl">
                        <div class="mb-4 inline-flex rounded-full bg-white/10 px-4 py-2 text-sm text-emerald-200 ring-1 ring-white/10">
                            Create your workspace
                        </div>
                        <h1 class="text-5xl font-semibold leading-tight tracking-tight">
                            Epits fel egy uj fiokot percek alatt.
                        </h1>
                        <p class="mt-6 text-lg leading-8 text-slate-300">
                            A regisztracios oldal ugyanazt a Sakai ihletesu vizualis rendszert hasznalja, mint a login,
                            es kozvetlenul a Laravel Breeze auth flow-ra csatlakozik.
                        </p>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-[1.75rem] bg-white/5 p-5 ring-1 ring-white/10">
                        <div class="text-sm text-slate-400">Onboarding</div>
                        <div class="mt-2 text-xl font-semibold">Fast setup</div>
                    </div>
                    <div class="rounded-[1.75rem] bg-white/5 p-5 ring-1 ring-white/10">
                        <div class="text-sm text-slate-400">Validation</div>
                        <div class="mt-2 text-xl font-semibold">Laravel</div>
                    </div>
                    <div class="rounded-[1.75rem] bg-white/5 p-5 ring-1 ring-white/10">
                        <div class="text-sm text-slate-400">UI</div>
                        <div class="mt-2 text-xl font-semibold">PrimeVue</div>
                    </div>
                </div>
            </section>

            <section class="flex items-center justify-center px-4 py-10 sm:px-6 lg:px-10">
                <div class="w-full max-w-xl">
                    <div class="mb-8 text-center lg:hidden">
                        <Link href="/" class="inline-flex items-center gap-3">
                            <div class="flex h-12 w-12 items-center justify-center rounded-[1.25rem] bg-gradient-to-br from-emerald-400 to-sky-400 text-xl font-bold text-slate-950">
                                P
                            </div>
                            <div class="text-left">
                                <div class="text-xs uppercase tracking-[0.3em] text-emerald-600">PrimeVue</div>
                                <div class="text-2xl font-semibold tracking-tight text-slate-950">Playground</div>
                            </div>
                        </Link>
                    </div>

                    <div class="app-card border-0 p-8 sm:p-10">
                        <div class="mb-8">
                            <div class="text-sm font-medium uppercase tracking-[0.3em] text-emerald-600">
                                Join now
                            </div>
                            <h2 class="mt-3 text-4xl font-semibold tracking-tight text-slate-950">
                                Create account
                            </h2>
                            <p class="mt-3 text-base leading-7 text-slate-500">
                                Toltse ki az adatokat az uj fiok letrehozasahoz.
                            </p>
                        </div>

                        <form class="space-y-5" @submit.prevent="submit">
                            <div class="space-y-2">
                                <label for="name" class="text-sm font-medium text-slate-700">Name</label>
                                <InputText
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    autocomplete="name"
                                    class="w-full"
                                    input-class="w-full"
                                    placeholder="John Doe"
                                    required
                                    autofocus
                                />
                                <small v-if="form.errors.name" class="block text-sm text-rose-500">
                                    {{ form.errors.name }}
                                </small>
                            </div>

                            <div class="space-y-2">
                                <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                                <InputText
                                    id="email"
                                    v-model="form.email"
                                    type="email"
                                    autocomplete="username"
                                    class="w-full"
                                    input-class="w-full"
                                    placeholder="name@example.com"
                                    required
                                />
                                <small v-if="form.errors.email" class="block text-sm text-rose-500">
                                    {{ form.errors.email }}
                                </small>
                            </div>

                            <div class="space-y-2">
                                <label for="password" class="text-sm font-medium text-slate-700">Password</label>
                                <Password
                                    id="password"
                                    v-model="form.password"
                                    input-class="w-full"
                                    class="w-full"
                                    toggle-mask
                                    autocomplete="new-password"
                                    placeholder="Password"
                                    required
                                    fluid
                                />
                                <small v-if="form.errors.password" class="block text-sm text-rose-500">
                                    {{ form.errors.password }}
                                </small>
                            </div>

                            <div class="space-y-2">
                                <label for="password_confirmation" class="text-sm font-medium text-slate-700">
                                    Confirm password
                                </label>
                                <Password
                                    id="password_confirmation"
                                    v-model="form.password_confirmation"
                                    input-class="w-full"
                                    class="w-full"
                                    :feedback="false"
                                    toggle-mask
                                    autocomplete="new-password"
                                    placeholder="Confirm password"
                                    required
                                    fluid
                                />
                                <small v-if="form.errors.password_confirmation" class="block text-sm text-rose-500">
                                    {{ form.errors.password_confirmation }}
                                </small>
                            </div>

                            <Button
                                type="submit"
                                label="Create Account"
                                icon="pi pi-user-plus"
                                icon-pos="right"
                                class="w-full"
                                :loading="form.processing"
                            />
                        </form>

                        <div class="mt-8 border-t border-slate-200 pt-6 text-center text-sm text-slate-500">
                            Mar van fiokod?
                            <Link
                                :href="route('login')"
                                class="font-semibold text-emerald-600 transition hover:text-emerald-700"
                            >
                                Bejelentkezes
                            </Link>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
