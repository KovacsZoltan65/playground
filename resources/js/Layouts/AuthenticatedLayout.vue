<script setup>
import { computed, ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import Avatar from 'primevue/avatar';
import Button from 'primevue/button';

const page = usePage();
const sidebarOpen = ref(false);

const user = computed(() => page.props.auth.user);

const navigationItems = [
    { label: 'Dashboard', icon: 'pi pi-home', route: 'dashboard' },
    { label: 'Profil', icon: 'pi pi-user', route: 'profile.edit' },
];

const isActive = (name) => route().current(name);
</script>

<template>
    <div class="app-shell lg:flex">
        <aside
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            class="fixed inset-y-0 left-0 z-40 flex w-80 flex-col border-r border-slate-200/60 bg-slate-950 px-6 py-6 text-white transition-transform duration-300 lg:static lg:min-h-screen lg:translate-x-0"
        >
            <div class="mb-8 flex items-center justify-between">
                <Link :href="route('dashboard')" class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-3xl bg-gradient-to-br from-emerald-400 to-sky-400 text-xl font-bold text-slate-950">
                        P
                    </div>
                    <div>
                        <div class="text-xs uppercase tracking-[0.35em] text-slate-400">
                            PrimeVue
                        </div>
                        <div class="text-2xl font-semibold tracking-tight">
                            Playground
                        </div>
                    </div>
                </Link>

                <Button
                    icon="pi pi-times"
                    rounded
                    text
                    severity="secondary"
                    class="lg:hidden"
                    @click="sidebarOpen = false"
                />
            </div>

            <div class="mb-8 rounded-[2rem] bg-white/5 p-5 ring-1 ring-white/10">
                <div class="mb-2 text-xs uppercase tracking-[0.3em] text-emerald-300">
                    Sakai style
                </div>
                <p class="text-sm leading-6 text-slate-300">
                    PrimeVue komponensekkel felépített admin shell Laravel 12 + Inertia kornyezetben.
                </p>
            </div>

            <nav class="space-y-2">
                <Link
                    v-for="item in navigationItems"
                    :key="item.route"
                    :href="route(item.route)"
                    :class="[
                        'app-sidebar-link',
                        isActive(item.route) ? 'app-sidebar-link-active' : '',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <i :class="item.icon" class="text-base"></i>
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <div class="mt-auto rounded-[2rem] bg-gradient-to-br from-emerald-500/20 to-sky-400/20 p-5 ring-1 ring-white/10">
                <div class="mb-3 text-sm font-semibold">Deployment tip</div>
                <p class="text-sm leading-6 text-slate-300">
                    Wamp alatt a projekt webrootja a <span class="font-semibold text-white">public</span> mappa legyen.
                </p>
            </div>
        </aside>

        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 border-b border-slate-200/70 bg-white/75 px-4 py-4 backdrop-blur-xl sm:px-6 lg:px-10">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <Button
                            icon="pi pi-bars"
                            rounded
                            text
                            severity="secondary"
                            class="lg:hidden"
                            @click="sidebarOpen = true"
                        />

                        <div>
                            <div class="text-xs uppercase tracking-[0.3em] text-slate-400">
                                Admin workspace
                            </div>
                            <div v-if="$slots.header" class="text-2xl font-semibold tracking-tight text-slate-900">
                                <slot name="header" />
                            </div>
                            <div v-else class="text-2xl font-semibold tracking-tight text-slate-900">
                                Dashboard
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 rounded-full border border-slate-200 bg-white px-3 py-2 shadow-sm">
                        <Avatar
                            shape="circle"
                            :label="user?.name?.charAt(0)?.toUpperCase() ?? 'U'"
                            class="bg-emerald-500 text-white"
                        />
                        <div class="hidden text-sm sm:block">
                            <div class="font-medium text-slate-900">{{ user?.name }}</div>
                            <div class="text-slate-500">{{ user?.email }}</div>
                        </div>
                        <Link :href="route('profile.edit')" class="text-slate-400 transition hover:text-slate-700">
                            <i class="pi pi-cog text-lg"></i>
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="text-slate-400 transition hover:text-rose-500"
                        >
                            <i class="pi pi-sign-out text-lg"></i>
                        </Link>
                    </div>
                </div>
            </header>

            <main class="px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                <slot />
            </main>
        </div>
    </div>
</template>
