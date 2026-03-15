<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RowActionMenu from '@/Components/RowActionMenu.vue';
import companyService from '@/Services/CompanyService';
import { trans } from 'laravel-vue-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';

const companies = ref([]);
const selectedCompanies = ref([]);
const loading = ref(false);
const filters = reactive({
    search: '',
    page: 1,
    perPage: 10,
});
const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const fetchCompanies = async () => {
    loading.value = true;

    try {
        const response = await companyService.list({
            search: filters.search || undefined,
            page: filters.page,
            per_page: filters.perPage,
        });

        companies.value = response.data;
        Object.assign(meta, response.meta);
    } finally {
        loading.value = false;
    }
};

const onPage = async (event) => {
    filters.page = Math.floor(event.first / event.rows) + 1;
    filters.perPage = event.rows;
    await fetchCompanies();
};

const onSearch = async () => {
    filters.page = 1;
    await fetchCompanies();
};

const removeCompany = async (company) => {
    if (!window.confirm(trans('Delete :name?', { name: company.name }))) {
        return;
    }

    await companyService.destroy(company.id);

    if (companies.value.length === 1 && filters.page > 1) {
        filters.page -= 1;
    }

    await fetchCompanies();
};

const removeSelectedCompanies = async () => {
    if (selectedCompanies.value.length === 0) {
        return;
    }

    if (!window.confirm(trans('Delete :count selected companies?', { count: selectedCompanies.value.length }))) {
        return;
    }

    await companyService.bulkDestroy(selectedCompanies.value.map((company) => company.id));
    selectedCompanies.value = [];

    if (companies.value.length === 1 && filters.page > 1) {
        filters.page -= 1;
    }

    await fetchCompanies();
};

const buildRowActions = (company) => [
    {
        label: trans('Edit'),
        icon: 'pi pi-pencil',
        command: () => router.get(route('companies.edit', company.id)),
    },
    {
        label: trans('Delete'),
        icon: 'pi pi-trash',
        command: () => removeCompany(company),
    },
];

onMounted(fetchCompanies);
</script>

<template>
    <Head :title="$t('Companies')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Companies') }}</template>

        <div class="app-grid">
            <Card class="app-card border-0">
                <template #content>
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">{{ $t('Directory') }}</div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $t('Company management') }}</h1>
                            <p class="mt-2 text-slate-500">{{ $t('Manage company records with repository-backed CRUD endpoints.') }}</p>
                            <p class="mt-3 text-sm font-medium text-slate-600">
                                {{ $t('Selected records') }}: <span class="text-slate-950">{{ selectedCompanies.length }}</span>
                            </p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <Button
                                :label="$t('Delete selected')"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                :disabled="selectedCompanies.length === 0"
                                @click="removeSelectedCompanies"
                            />
                            <InputText
                                v-model="filters.search"
                                class="w-full sm:w-72"
                                :placeholder="$t('Search companies')"
                                @keyup.enter="onSearch"
                            />
                            <Button :label="$t('Search')" icon="pi pi-search" severity="secondary" outlined @click="onSearch" />
                            <Link :href="route('companies.create')">
                                <Button :label="$t('New company')" icon="pi pi-plus" />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="app-card border-0">
                <template #content>
                    <DataTable
                        v-model:selection="selectedCompanies"
                        :value="companies"
                        :loading="loading"
                        lazy
                        paginator
                        data-key="id"
                        :rows="meta.per_page"
                        :first="(meta.current_page - 1) * meta.per_page"
                        :total-records="meta.total"
                        paginator-template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                        :rows-per-page-options="[10, 25, 50]"
                        table-style="min-width: 60rem"
                        @page="onPage"
                    >
                        <template #empty>
                            <div class="py-10 text-center text-slate-500">
                                {{ $t('No companies found for the current filters.') }}
                            </div>
                        </template>

                        <Column selection-mode="multiple" header-style="width: 3rem" />

                        <Column field="name" :header="$t('Company name')">
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">{{ data.name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ data.address || $t('No address added') }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="email" :header="$t('Email')">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.email || $t('N/A') }}</span>
                            </template>
                        </Column>

                        <Column field="phone" :header="$t('Phone')">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.phone || $t('N/A') }}</span>
                            </template>
                        </Column>

                        <Column field="is_active" :header="$t('Status')">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? $t('Active') : $t('Inactive')" />
                            </template>
                        </Column>

                        <Column :header="$t('Actions')" header-class="text-right" body-class="text-right">
                            <template #body="{ data }">
                                <RowActionMenu :items="buildRowActions(data)" />
                            </template>
                        </Column>
                    </DataTable>
                </template>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
