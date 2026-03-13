<script setup>
import { Head, Link } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Tag from 'primevue/tag';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    laravelVersion: {
        type: String,
        required: true,
    },
    phpVersion: {
        type: String,
        required: true,
    },
});

const features = [
    {
        title: 'PrimeVue komponensek',
        text: 'Gombok, kartyak, tagek es dashboard elemek keszen allnak a tovabbi modulokhoz.',
        icon: 'pi pi-star-fill',
    },
    {
        title: 'Sakai stilus',
        text: 'Modern, vilagos admin shell uvegfeluletekkel, oldalsavval es hangsulyos stat kartyakkal.',
        icon: 'pi pi-palette',
    },
    {
        title: 'Laravel 12 stack',
        text: 'Inertia, Vue 3, Vite es MySQL alappal gyorsan tovabbfejlesztheto uzleti alkalmazas.',
        icon: 'pi pi-bolt',
    },
];
</script>

<template>
    <Head title="Welcome" />

    <div class="app-shell min-h-screen px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
        <div class="mx-auto max-w-7xl">
            <header class="flex flex-wrap items-center justify-between gap-4 rounded-[2rem] border border-white/70 bg-white/75 px-6 py-4 shadow-lg backdrop-blur-xl">
                <div>
                    <div class="text-xs uppercase tracking-[0.35em] text-emerald-600">Laravel + PrimeVue</div>
                    <div class="text-2xl font-semibold tracking-tight text-slate-950">Playground</div>
                </div>

                <nav v-if="canLogin" class="flex items-center gap-3">
                    <Link v-if="$page.props.auth.user" :href="route('dashboard')">
                        <Button label="Dashboard" icon="pi pi-arrow-right" />
                    </Link>
                    <template v-else>
                        <Link :href="route('login')">
                            <Button label="Belepes" severity="secondary" outlined />
                        </Link>
                        <Link v-if="canRegister" :href="route('register')">
                            <Button label="Regisztracio" icon="pi pi-user-plus" />
                        </Link>
                    </template>
                </nav>
            </header>

            <section class="grid gap-6 py-8 lg:grid-cols-[1.35fr_0.65fr] lg:py-12">
                <Card class="app-card border-0">
                    <template #content>
                        <Tag value="Sakai inspired setup" severity="success" rounded class="mb-5" />
                        <h1 class="max-w-3xl text-5xl font-semibold tracking-tight text-slate-950">
                            PrimeVue admin alap, kesz Laravel backenddel.
                        </h1>
                        <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-500">
                            A projekt most egy PrimeVue-vel bekotott, Sakai hangulatu indulo feluletet ad, amit
                            uzleti dashboardda, CRM-me vagy belso admin rendszerren lehet tovabbepiteni.
                        </p>
                        <div class="mt-8 flex flex-wrap gap-3">
                            <a href="https://sakai.primevue.org/" target="_blank" rel="noreferrer">
                                <Button label="Sakai minta" icon="pi pi-external-link" />
                            </a>
                            <a href="https://primevue.org/" target="_blank" rel="noreferrer">
                                <Button label="PrimeVue docs" icon="pi pi-book" severity="secondary" outlined />
                            </a>
                        </div>
                    </template>
                </Card>

                <Card class="border-0 bg-slate-950 text-white shadow-2xl">
                    <template #content>
                        <div class="text-xs uppercase tracking-[0.35em] text-emerald-300">Environment</div>
                        <div class="mt-6 space-y-4 text-sm">
                            <div class="rounded-2xl bg-white/10 p-4">
                                <div class="text-slate-400">Laravel</div>
                                <div class="mt-1 text-xl font-semibold">v{{ laravelVersion }}</div>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4">
                                <div class="text-slate-400">PHP</div>
                                <div class="mt-1 text-xl font-semibold">v{{ phpVersion }}</div>
                            </div>
                            <div class="rounded-2xl bg-white/10 p-4">
                                <div class="text-slate-400">Frontend</div>
                                <div class="mt-1 text-xl font-semibold">Vue 3 + Vite</div>
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <section class="grid gap-6 md:grid-cols-3">
                <Card
                    v-for="feature in features"
                    :key="feature.title"
                    class="app-card border-0"
                >
                    <template #content>
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-sky-400 text-2xl text-white shadow-lg">
                            <i :class="feature.icon"></i>
                        </div>
                        <h2 class="mt-5 text-xl font-semibold text-slate-950">{{ feature.title }}</h2>
                        <p class="mt-3 leading-7 text-slate-500">{{ feature.text }}</p>
                    </template>
                </Card>
            </section>
        </div>
    </div>
</template>
