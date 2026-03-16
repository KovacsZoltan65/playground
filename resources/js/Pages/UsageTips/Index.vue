<script setup>
import RowActionMenu from '@/Components/RowActionMenu.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import sidebarTipPageService from '@/Services/SidebarTipPageService';
import { currentLocale, trans } from 'laravel-vue-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';

const tipPages = ref([]);
const loading = ref(false);
const filters = reactive({
    search: '',
    perPage: 10,
});
const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const pageLabelMap = computed(() => {
    currentLocale.value;

    return Object.fromEntries(
        tipPages.value.map((page) => [page.page_component, trans(page.page_label_key ?? page.page_component)])
    );
});

const fetchTipPages = async () => {
    loading.value = true;

    try {
        const response = await sidebarTipPageService.list({
            search: filters.search || undefined,
            per_page: filters.perPage,
        });

        tipPages.value = response.data;
        Object.assign(meta, response.meta);
    } finally {
        loading.value = false;
    }
};

const deleteTipPage = async (tipPage) => {
    if (!window.confirm(trans('Delete usage tips for :page?', { page: trans(tipPage.page_label_key ?? tipPage.page_component) }))) {
        return;
    }

    await sidebarTipPageService.destroy(tipPage.id);
    await fetchTipPages();
};

const buildRowActions = (tipPage) => [
    {
        label: trans('Edit'),
        icon: 'pi pi-pencil',
        command: () => router.get(route('usage-tips.edit', tipPage.id)),
    },
    {
        label: trans('Delete'),
        icon: 'pi pi-trash',
        command: () => deleteTipPage(tipPage),
    },
];

onMounted(fetchTipPages);
</script>

<template>
    <Head :title="$t('Usage tips')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Usage tips') }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">{{ $t('Settings') }}</div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $t('Manage sidebar usage tips') }}</h1>
                        <p class="mt-2 text-slate-500">{{ $t('Configure per-page idea visibility, rotation interval, and ordered sidebar tips.') }}</p>
                    </div>

                    <Link :href="route('usage-tips.create')">
                        <Button :label="$t('New usage tips')" icon="pi pi-plus" />
                    </Link>
                </div>

                <div class="mb-6 flex flex-wrap gap-4">
                    <InputText
                        v-model="filters.search"
                        class="w-full max-w-md"
                        :placeholder="$t('Search usage tips')"
                        @keyup.enter="fetchTipPages"
                    />
                    <Button :label="$t('Search')" icon="pi pi-search" severity="secondary" @click="fetchTipPages" />
                </div>

                <DataTable :value="tipPages" :loading="loading" data-key="id" responsive-layout="scroll">
                    <Column field="page_component" :header="$t('Target page')">
                        <template #body="{ data }">
                            {{ pageLabelMap[data.page_component] }}
                        </template>
                    </Column>
                    <Column field="is_visible" :header="$t('Visible')">
                        <template #body="{ data }">
                            <Tag :severity="data.is_visible ? 'success' : 'secondary'" :value="data.is_visible ? $t('Active') : $t('Inactive')" />
                        </template>
                    </Column>
                    <Column field="rotation_interval_seconds" :header="$t('Idea rotation in seconds')" />
                    <Column field="tips_count" :header="$t('Ideas')" />
                    <Column field="active_tips_count" :header="$t('Active ideas')" />
                    <Column :header="$t('Actions')" header-class="text-right" body-class="text-right">
                        <template #body="{ data }">
                            <RowActionMenu :items="buildRowActions(data)" />
                        </template>
                    </Column>
                </DataTable>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
