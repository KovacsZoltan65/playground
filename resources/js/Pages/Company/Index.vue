<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import RowActionMenu from '@/Components/RowActionMenu.vue';
import companyService from '@/Services/CompanyService';
import { currentLocale, trans } from 'laravel-vue-i18n';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import Column from 'primevue/column';
import DataTable from 'primevue/datatable';
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import InputText from 'primevue/inputtext';
import Select from 'primevue/select';
import Tag from 'primevue/tag';

const companies = ref([]);
const selectedCompanies = ref([]);
const loading = ref(false);
const tableFilters = ref({
    global: {
        value: null,
        matchMode: 'contains',
    },
    name: {
        value: null,
        matchMode: 'contains',
    },
    email: {
        value: null,
        matchMode: 'contains',
    },
    phone: {
        value: null,
        matchMode: 'contains',
    },
    is_active: {
        value: null,
        matchMode: 'equals',
    },
});
const tableState = reactive({
    page: 1,
    perPage: 10,
});
const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});
const statusOptions = computed(() => {
    currentLocale.value;

    return [
        { label: trans('All statuses'), value: null },
        { label: trans('Active'), value: true },
        { label: trans('Inactive'), value: false },
    ];
});

const fetchCompanies = async () => {
    loading.value = true;

    try {
        const response = await companyService.list({
            search: tableFilters.value.global.value || undefined,
            name: tableFilters.value.name.value || undefined,
            email: tableFilters.value.email.value || undefined,
            phone: tableFilters.value.phone.value || undefined,
            is_active: tableFilters.value.is_active.value ?? undefined,
            page: tableState.page,
            per_page: tableState.perPage,
        });

        companies.value = response.data;
        Object.assign(meta, response.meta);
    } finally {
        loading.value = false;
    }
};

const onPage = async (event) => {
    tableState.page = Math.floor(event.first / event.rows) + 1;
    tableState.perPage = event.rows;
    await fetchCompanies();
};

const onFilter = async (event) => {
    tableFilters.value = event.filters;
    tableState.page = 1;
    await fetchCompanies();
};

const removeCompany = async (company) => {
    if (!window.confirm(trans('Delete :name?', { name: company.name }))) {
        return;
    }

    await companyService.destroy(company.id);

    if (companies.value.length === 1 && tableState.page > 1) {
        tableState.page -= 1;
    }

    await fetchCompanies();
};

const removeSelectedCompanies = async () => {
    if (selectedCompanies.value.length === 0) {
        return;
    }

    if (
        !window.confirm(
            trans('Delete :count selected companies?', {
                count: selectedCompanies.value.length,
            })
        )
    ) {
        return;
    }

    await companyService.bulkDestroy(
        selectedCompanies.value.map((company) => company.id)
    );
    selectedCompanies.value = [];

    if (companies.value.length === 1 && tableState.page > 1) {
        tableState.page -= 1;
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

const clearFilters = async () => {
    tableFilters.value = {
        global: {
            value: null,
            matchMode: 'contains',
        },
        name: {
            value: null,
            matchMode: 'contains',
        },
        email: {
            value: null,
            matchMode: 'contains',
        },
        phone: {
            value: null,
            matchMode: 'contains',
        },
        is_active: {
            value: null,
            matchMode: 'equals',
        },
    };
    tableState.page = 1;
    await fetchCompanies();
};

onMounted(fetchCompanies);
</script>

<template>
    <Head :title="$t('Companies')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Companies') }}</template>

        <div class="app-grid">
            <Card class="app-card border-0">
                <template #content>
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">{{ $t('Directory') }}</div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $t('Company management') }}</h1>
                            <p class="mt-2 text-slate-500">
                                {{ $t('Manage company records with repository-backed CRUD endpoints.') }}
                            </p>
                            <p class="mt-3 text-sm font-medium text-slate-600">
                                {{ $t('Selected records') }}:
                                <span class="text-slate-950">{{ selectedCompanies.length }}</span>
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
                        v-model:filters="tableFilters"
                        :value="companies"
                        :loading="loading"
                        lazy
                        paginator
                        removableSort
                        filter-display="menu"
                        data-key="id"
                        :rows="meta.per_page"
                        :first="(meta.current_page - 1) * meta.per_page"
                        :total-records="meta.total"
                        :global-filter-fields="['name', 'email', 'phone']"
                        paginator-template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                        :rows-per-page-options="[10, 25, 50]"
                        table-style="min-width: 60rem"
                        @page="onPage"
                        @filter="onFilter"
                    >
                        <template #header>
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                            >
                                <div class="text-sm text-slate-500">
                                    {{ $t('Repository-backed CRUD with PrimeVue components.') }}
                                </div>
                                <div class="flex w-full flex-col gap-3 sm:w-auto sm:flex-row">
                                    <Button
                                        type="button"
                                        icon="pi pi-filter-slash"
                                        severity="secondary"
                                        outlined
                                        :label="$t('Clear')"
                                        @click="clearFilters"
                                    />
                                    <IconField>
                                        <InputIcon>
                                            <i class="pi pi-search" />
                                        </InputIcon>
                                        <InputText
                                            v-model="tableFilters.global.value"
                                            class="w-full sm:w-80"
                                            :placeholder="$t('Keyword Search')"
                                        />
                                    </IconField>
                                </div>
                            </div>
                        </template>

                        <template #empty>
                            <div class="py-10 text-center text-slate-500">
                                {{ $t('No companies found for the current filters.') }}
                            </div>
                        </template>

                        <Column selection-mode="multiple" header-style="width: 3rem" />

                        <Column
                            field="name"
                            :header="$t('Company name')"
                            sortable
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText
                                    v-model="filterModel.value"
                                    class="w-full"
                                    :placeholder="$t('Company name')"
                                    @input="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">
                                        {{ data.name }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">{{ data.address || $t('No address added') }}</div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="email"
                            :header="$t('Email')"
                            sortable
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText
                                    v-model="filterModel.value"
                                    class="w-full"
                                    :placeholder="$t('Email')"
                                    @input="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.email || $t('N/A') }}</span>
                            </template>
                        </Column>

                        <Column
                            field="phone"
                            :header="$t('Phone')"
                            sortable
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText
                                    v-model="filterModel.value"
                                    class="w-full"
                                    :placeholder="$t('Phone')"
                                    @input="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.phone || $t('N/A') }}</span>
                            </template>
                        </Column>

                        <Column
                            field="is_active"
                            :header="$t('Status')"
                            sortable
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <Select
                                    v-model="filterModel.value"
                                    :options="statusOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full min-w-40"
                                    show-clear
                                    :placeholder="$t('All statuses')"
                                    @change="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <Tag
                                    :severity="data.is_active ? 'success' : 'secondary'"
                                    :value="
                                        data.is_active ? $t('Active') : $t('Inactive')
                                    "
                                />
                            </template>
                        </Column>

                        <Column
                            :header="$t('Actions')"
                            header-class="text-right"
                            body-class="text-right"
                        >
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
