<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { currentLocale, trans } from 'laravel-vue-i18n';
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';

const stats = computed(() => {
    currentLocale.value;

    return [
        {
            title: trans('Active projects'),
            value: '12',
            change: '+18%',
            icon: 'pi pi-briefcase',
            tint: 'from-emerald-400/30 to-emerald-500/10',
        },
        {
            title: trans('Open tasks'),
            value: '43',
            change: '+6%',
            icon: 'pi pi-list-check',
            tint: 'from-sky-400/30 to-sky-500/10',
        },
        {
            title: trans('Today revenue'),
            value: 'EUR 8,420',
            change: '+12%',
            icon: 'pi pi-chart-line',
            tint: 'from-amber-300/30 to-orange-400/10',
        },
        {
            title: trans('System status'),
            value: '99.98%',
            change: trans('Stable'),
            icon: 'pi pi-shield',
            tint: 'from-violet-300/30 to-fuchsia-400/10',
        },
    ];
});

const activities = computed(() => {
    currentLocale.value;

    return [
        { title: trans('Frontend build'), meta: trans('Vite production build completed successfully'), time: trans('2 minutes ago') },
        { title: trans('MySQL connection'), meta: trans('Basic configuration completed'), time: trans('10 minutes ago') },
        { title: trans('PrimeVue theme'), meta: trans('Aura preset enabled'), time: trans('just now') },
    ];
});

const progress = 74;
</script>

<template>
    <Head :title="$t('Dashboard')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Dashboard') }}</template>

        <section class="app-grid lg:grid-cols-[1.8fr_1fr]">
            <Card class="app-card overflow-hidden border-0">
                <template #content>
                    <div class="grid gap-8 lg:grid-cols-[1.3fr_0.7fr]">
                        <div>
                            <Tag severity="success" :value="$t('Sakai inspired')" rounded class="mb-4" />
                            <h1 class="max-w-xl text-4xl font-semibold tracking-tight text-slate-950">
                                {{ $t('PrimeVue-powered admin shell for Laravel 12 and Inertia.') }}
                            </h1>
                            <p class="mt-4 max-w-2xl text-base leading-7 text-slate-500">
                                {{ $t('This project now uses PrimeVue components, PrimeIcons, and a Sakai-inspired dashboard shell while keeping the Laravel Breeze auth flow.') }}
                            </p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <Button :label="$t('New module')" icon="pi pi-plus" />
                                <Button :label="$t('Documentation')" icon="pi pi-book" severity="secondary" outlined />
                            </div>
                        </div>

                        <div class="rounded-[2rem] bg-slate-950 p-6 text-white shadow-2xl">
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-300">{{ $t('Sprint overview') }}</div>
                            <div class="mt-6 text-4xl font-semibold">74%</div>
                            <div class="mt-2 text-sm text-slate-300">{{ $t('of progress in the current delivery cycle') }}</div>
                            <ProgressBar :value="progress" class="mt-6" />
                            <div class="mt-6 grid grid-cols-2 gap-4 text-sm">
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <div class="text-slate-400">{{ $t('Release') }}</div>
                                    <div class="mt-1 text-lg font-semibold">v1.0.0</div>
                                </div>
                                <div class="rounded-2xl bg-white/10 p-4">
                                    <div class="text-slate-400">{{ $t('Stack') }}</div>
                                    <div class="mt-1 text-lg font-semibold">Vue 3</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="app-card border-0">
                <template #title>
                    <div class="text-xl font-semibold text-slate-950">{{ $t('Latest activity') }}</div>
                </template>
                <template #content>
                    <div class="space-y-4">
                        <div
                            v-for="activity in activities"
                            :key="activity.title"
                            class="rounded-2xl border border-slate-200/70 bg-slate-50/80 p-4"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="font-medium text-slate-900">{{ activity.title }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ activity.meta }}</div>
                                </div>
                                <span class="text-xs uppercase tracking-[0.2em] text-slate-400">{{ activity.time }}</span>
                            </div>
                        </div>
                    </div>
                </template>
            </Card>
        </section>

        <section class="app-grid mt-6 md:grid-cols-2 xl:grid-cols-4">
            <Card
                v-for="stat in stats"
                :key="stat.title"
                class="app-card border-0"
            >
                <template #content>
                    <div :class="['rounded-[1.75rem] bg-gradient-to-br p-5', stat.tint]">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="text-sm font-medium text-slate-500">{{ stat.title }}</div>
                                <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ stat.value }}</div>
                            </div>
                            <div class="app-stat-icon text-slate-900">
                                <i :class="stat.icon"></i>
                            </div>
                        </div>
                        <div class="mt-6 inline-flex rounded-full bg-white/80 px-3 py-1 text-sm font-medium text-slate-700">
                            {{ stat.change }}
                        </div>
                    </div>
                </template>
            </Card>
        </section>
    </AuthenticatedLayout>
</template>
