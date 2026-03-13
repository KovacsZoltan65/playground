<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RowActionMenu from '@/Components/RowActionMenu.vue';
import companyService from '@/Services/CompanyService';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import InputText from 'primevue/inputtext';
import Tag from 'primevue/tag';

const companies = ref([]);
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
    if (!window.confirm(`Delete ${company.name}?`)) {
        return;
    }

    await companyService.destroy(company.id);

    if (companies.value.length === 1 && filters.page > 1) {
        filters.page -= 1;
    }

    await fetchCompanies();
};

const buildRowActions = (company) => [
    {
        label: 'Edit',
        icon: 'pi pi-pencil',
        command: () => router.get(route('companies.edit', company.id)),
    },
    {
        label: 'Delete',
        icon: 'pi pi-trash',
        command: () => removeCompany(company),
    },
];

onMounted(fetchCompanies);
</script>

<template>
    <Head title="Companies" />

    <AuthenticatedLayout>
        <template #header>Companies</template>

        <div class="app-grid">
            <Card class="app-card border-0">
                <template #content>
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">Directory</div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Company management</h1>
                            <p class="mt-2 text-slate-500">Manage company records with repository-backed CRUD endpoints.</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row">
                            <InputText
                                v-model="filters.search"
                                class="w-full sm:w-72"
                                placeholder="Search companies"
                                @keyup.enter="onSearch"
                            />
                            <Button label="Search" icon="pi pi-search" severity="secondary" outlined @click="onSearch" />
                            <Link :href="route('companies.create')">
                                <Button label="New company" icon="pi pi-plus" />
                            </Link>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="app-card border-0">
                <template #content>
                    <DataTable
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
                                No companies found for the current filters.
                            </div>
                        </template>

                        <Column field="name" header="Name">
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">{{ data.name }}</div>
                                    <div class="mt-1 text-sm text-slate-500">{{ data.address || 'No address added' }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column field="email" header="Email">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.email || 'N/A' }}</span>
                            </template>
                        </Column>

                        <Column field="phone" header="Phone">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.phone || 'N/A' }}</span>
                            </template>
                        </Column>

                        <Column field="is_active" header="Status">
                            <template #body="{ data }">
                                <Tag :severity="data.is_active ? 'success' : 'secondary'" :value="data.is_active ? 'Active' : 'Inactive'" />
                            </template>
                        </Column>

                        <Column header="Actions" header-class="text-right" body-class="text-right">
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
