<script setup>
/**
 * A cégkezelő admin listaoldal szerveroldali szűréssel, rendezéssel és tömeges műveletekkel.
 *
 * Ez a projekt referencia PrimeVue index oldala: az oszlopnézet egy része böngészőoldalon
 * marad meg, miközben a listaadatok és a műveletek minden esetben backend végponton futnak át.
 */
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import companyService from "@/Services/CompanyService";
import { requestConfirmation } from "@/Support/confirm/requestConfirmation";
import { formatDateTime } from "@/Support/dates/formatDate";
import { currentLocale, trans } from "laravel-vue-i18n";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import Button from "primevue/button";
import Card from "primevue/card";
import Column from "primevue/column";
import ConfirmDialog from "primevue/confirmdialog";
import DataTable from "primevue/datatable";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import Tag from "primevue/tag";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const COLUMN_VISIBILITY_STORAGE_KEY = "company-index-visible-columns";
const DEFAULT_VISIBLE_COLUMN_KEYS = [
    "name",
    "email",
    "phone",
    "employees_count",
    "is_active",
    "updated_at",
];
const MINIMUM_VISIBLE_COLUMN_KEY = "name";
const SEARCH_DEBOUNCE_MS = 350;

const companies = ref([]);
const selectedCompanies = ref([]);
const loading = ref(false);
const visibleColumnKeys = ref([...DEFAULT_VISIBLE_COLUMN_KEYS]);
const searchInput = ref("");
let searchDebounceTimer = null;
let isProgrammaticSearchUpdate = false;
const confirm = useConfirm();
const toast = useToast();

const tableFilters = ref({
    global: {
        value: null,
        matchMode: "contains",
    },
    name: {
        value: null,
        matchMode: "contains",
    },
    email: {
        value: null,
        matchMode: "contains",
    },
    phone: {
        value: null,
        matchMode: "contains",
    },
    is_active: {
        value: null,
        matchMode: "equals",
    },
});

const tableState = reactive({
    page: 1,
    perPage: 10,
    sortField: "name",
    sortOrder: 1,
});

const meta = reactive({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
});

const availableColumns = computed(() => {
    currentLocale.value;

    return [
        { label: trans("Company name"), value: "name" },
        { label: trans("Email"), value: "email" },
        { label: trans("Phone"), value: "phone" },
        { label: trans("Employees"), value: "employees_count" },
        { label: trans("Status"), value: "is_active" },
        { label: trans("Last updated"), value: "updated_at" },
    ];
});

const visibleColumnsSummary = computed(() => {
    currentLocale.value;

    if (visibleColumnKeys.value.length === availableColumns.value.length) {
        return trans("All visible");
    }

    return trans(":count columns visible", {
        count: visibleColumnKeys.value.length,
    });
});

const selectedColumnsLabel = computed(() =>
    trans(":count columns visible", {
        count: visibleColumnKeys.value.length,
    })
);

const statusOptions = computed(() => {
    currentLocale.value;

    return [
        { label: trans("All statuses"), value: null },
        { label: trans("Active"), value: true },
        { label: trans("Inactive"), value: false },
    ];
});

const quickStats = computed(() => {
    currentLocale.value;

    const activeCount = companies.value.filter((company) => company.is_active).length;
    const missingContactCount = companies.value.filter(
        (company) => !company.email || !company.phone
    ).length;

    return [
        {
            label: trans("Companies on this page"),
            value: companies.value.length,
            caption: trans("Rows currently loaded in the table"),
        },
        {
            label: trans("Active on this page"),
            value: activeCount,
            caption: trans("Active records within the current page"),
        },
        {
            label: trans("Incomplete on this page"),
            value: missingContactCount,
            caption: trans("Rows missing phone or email on this page"),
        },
    ];
});

const activeFilters = computed(() => {
    currentLocale.value;

    const filters = [];

    if (searchInput.value) {
        filters.push({
            key: "global",
            label: `${trans("Keyword Search")}: ${searchInput.value}`,
        });
    }

    if (tableFilters.value.name.value) {
        filters.push({
            key: "name",
            label: `${trans("Company name")}: ${tableFilters.value.name.value}`,
        });
    }

    if (tableFilters.value.email.value) {
        filters.push({
            key: "email",
            label: `${trans("Email")}: ${tableFilters.value.email.value}`,
        });
    }

    if (tableFilters.value.phone.value) {
        filters.push({
            key: "phone",
            label: `${trans("Phone")}: ${tableFilters.value.phone.value}`,
        });
    }

    if (tableFilters.value.is_active.value !== null) {
        filters.push({
            key: "is_active",
            label: `${trans("Status")}: ${
                tableFilters.value.is_active.value ? trans("Active") : trans("Inactive")
            }`,
        });
    }

    return filters;
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
            sort_field: tableState.sortField,
            sort_direction: tableState.sortOrder === -1 ? "desc" : "asc",
            page: tableState.page,
            per_page: tableState.perPage,
        });

        companies.value = response.data;
        Object.assign(meta, response.meta);
    } finally {
        loading.value = false;
    }
};

const showSuccessToast = (detail) => {
    toast.add({
        severity: "success",
        summary: trans("Success"),
        detail,
        life: 3000,
    });
};

const showErrorToast = (detail = trans("Action failed.")) => {
    toast.add({
        severity: "error",
        summary: trans("Error"),
        detail,
        life: 4000,
    });
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

const onSort = async (event) => {
    tableState.sortField = event.sortField || "name";
    tableState.sortOrder = event.sortOrder || 1;
    tableState.page = 1;
    await fetchCompanies();
};

const removeCompany = async (company) => {
    const accepted = await requestConfirmation(confirm, {
        message: trans("Delete :name?", { name: company.name }),
        header: trans("Delete"),
        icon: "pi pi-exclamation-triangle",
        acceptLabel: trans("Delete"),
        rejectLabel: trans("Cancel"),
        acceptClass: "p-button-danger",
    });

    if (!accepted) {
        return;
    }

    try {
        await companyService.destroy(company.id);

        if (companies.value.length === 1 && tableState.page > 1) {
            tableState.page -= 1;
        }

        await fetchCompanies();
        showSuccessToast(trans("Company deleted successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const removeSelectedCompanies = async () => {
    if (selectedCompanies.value.length === 0) {
        return;
    }

    const accepted = await requestConfirmation(confirm, {
        message: trans("Delete :count selected companies?", {
            count: selectedCompanies.value.length,
        }),
        header: trans("Delete selected"),
        icon: "pi pi-exclamation-triangle",
        acceptLabel: trans("Delete"),
        rejectLabel: trans("Cancel"),
        acceptClass: "p-button-danger",
    });

    if (!accepted) {
        return;
    }

    try {
        await companyService.bulkDestroy(
            selectedCompanies.value.map((company) => company.id)
        );
        selectedCompanies.value = [];

        if (companies.value.length === 1 && tableState.page > 1) {
            tableState.page -= 1;
        }

        await fetchCompanies();
        showSuccessToast(trans("Companies deleted successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const activateSelectedCompanies = async () => {
    if (selectedCompanies.value.length === 0) {
        return;
    }

    const accepted = await requestConfirmation(confirm, {
        message: trans("Activate :count selected companies?", {
            count: selectedCompanies.value.length,
        }),
        header: trans("Activate selected"),
        icon: "pi pi-check-circle",
        acceptLabel: trans("Activate"),
        rejectLabel: trans("Cancel"),
        acceptClass: "p-button-success",
    });

    if (!accepted) {
        return;
    }

    try {
        await companyService.bulkActivate(
            selectedCompanies.value.map((company) => company.id)
        );
        selectedCompanies.value = [];

        await fetchCompanies();
        showSuccessToast(trans("Companies activated successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const deactivateSelectedCompanies = async () => {
    if (selectedCompanies.value.length === 0) {
        return;
    }

    const accepted = await requestConfirmation(confirm, {
        message: trans("Deactivate :count selected companies?", {
            count: selectedCompanies.value.length,
        }),
        header: trans("Deactivate selected"),
        icon: "pi pi-ban",
        acceptLabel: trans("Deactivate"),
        rejectLabel: trans("Cancel"),
        acceptClass: "p-button-danger",
    });

    if (!accepted) {
        return;
    }

    try {
        await companyService.bulkDeactivate(
            selectedCompanies.value.map((company) => company.id)
        );
        selectedCompanies.value = [];

        await fetchCompanies();
        showSuccessToast(trans("Companies deactivated successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const toggleCompanyActiveStatus = async (company) => {
    try {
        await companyService.toggleActiveStatus(company.id);
        await fetchCompanies();
        showSuccessToast(trans("Company status updated successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const refreshCompanies = async () => {
    try {
        await fetchCompanies();
        showSuccessToast(trans("Companies refreshed."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const buildRowActions = (company) => [
    {
        label: trans("Edit"),
        icon: "pi pi-pencil",
        command: () => router.get(route("companies.edit", company.id)),
    },
    {
        label: trans("Delete"),
        icon: "pi pi-trash",
        command: () => removeCompany(company),
    },
    {
        label: company.is_active ? trans("Deactivate") : trans("Activate"),
        icon: company.is_active ? "pi pi-eye-slash" : "pi pi-check-circle",
        command: () => toggleCompanyActiveStatus(company),
    },
];

const clearFilters = async () => {
    searchInput.value = "";
    tableFilters.value = {
        global: {
            value: null,
            matchMode: "contains",
        },
        name: {
            value: null,
            matchMode: "contains",
        },
        email: {
            value: null,
            matchMode: "contains",
        },
        phone: {
            value: null,
            matchMode: "contains",
        },
        is_active: {
            value: null,
            matchMode: "equals",
        },
    };
    tableState.page = 1;
    await fetchCompanies();
};

const isColumnVisible = (columnKey) => visibleColumnKeys.value.includes(columnKey);

const resetVisibleColumns = () => {
    visibleColumnKeys.value = [...DEFAULT_VISIBLE_COLUMN_KEYS];
};

const clearSingleFilter = async (filterKey) => {
    if (filterKey === "global") {
        isProgrammaticSearchUpdate = true;
        searchInput.value = "";
        tableFilters.value.global.value = null;
        tableState.page = 1;
        await fetchCompanies();
        isProgrammaticSearchUpdate = false;
        return;
    } else {
        tableFilters.value[filterKey].value = null;
    }

    tableState.page = 1;
    await fetchCompanies();
};

const editCompany = (company) => {
    router.get(route("companies.edit", company.id));
};

const getContactHealth = (company) => {
    if (company.email && company.phone) {
        return {
            severity: "success",
            value: trans("Complete"),
        };
    }

    return {
        severity: "warn",
        value: trans("Needs review"),
    };
};

// A tárolt oszlopkulcsok hibás vagy már nem támogatott beállítás esetén visszaállnak az alapnézetre.
const restoreVisibleColumns = () => {
    const savedColumns = window.localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY);

    if (!savedColumns) {
        return;
    }

    try {
        const parsedColumns = JSON.parse(savedColumns);

        if (!Array.isArray(parsedColumns)) {
            return;
        }

        const allowedColumnKeys = availableColumns.value.map((column) => column.value);
        const sanitizedColumns = parsedColumns.filter((column) =>
            allowedColumnKeys.includes(column)
        );

        if (sanitizedColumns.length > 0) {
            visibleColumnKeys.value = sanitizedColumns;
        }
    } catch {
        window.localStorage.removeItem(COLUMN_VISIBILITY_STORAGE_KEY);
    }
};

watch(
    visibleColumnKeys,
    (columns) => {
        if (columns.length === 0) {
            visibleColumnKeys.value = [MINIMUM_VISIBLE_COLUMN_KEY];
            return;
        }

        window.localStorage.setItem(
            COLUMN_VISIBILITY_STORAGE_KEY,
            JSON.stringify(columns)
        );
    },
    { deep: true }
);

// A globális keresés debounce-olva frissíti a filter modellt, hogy ne fusson kérés minden leütésre.
watch(searchInput, (value) => {
    if (isProgrammaticSearchUpdate) {
        return;
    }

    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(async () => {
        tableFilters.value.global.value = value || null;
        tableState.page = 1;
        await fetchCompanies();
    }, SEARCH_DEBOUNCE_MS);
});

onMounted(async () => {
    restoreVisibleColumns();
    searchInput.value = tableFilters.value.global.value ?? "";
    await fetchCompanies();
});

onBeforeUnmount(() => {
    if (searchDebounceTimer !== null) {
        window.clearTimeout(searchDebounceTimer);
    }
});
</script>

<template>
    <Head :title="$t('Companies')" />

    <AuthenticatedLayout>
        <ConfirmDialog />

        <template #header>{{ $t("Companies") }}</template>

        <div class="app-grid">
            <section class="app-grid md:grid-cols-3">
                <Card
                    v-for="stat in quickStats"
                    :key="stat.label"
                    class="app-card border-0"
                >
                    <template #content>
                        <div class="rounded-[1.75rem] bg-slate-50 p-5">
                            <div class="text-sm font-medium text-slate-500">
                                {{ stat.label }}
                            </div>
                            <div
                                class="mt-3 text-3xl font-semibold tracking-tight text-slate-950"
                            >
                                {{ stat.value }}
                            </div>
                            <div class="mt-2 text-sm text-slate-500">
                                {{ stat.caption }}
                            </div>
                        </div>
                    </template>
                </Card>
            </section>

            <Card class="app-card border-0">
                <template #content>
                    <div
                        class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <div
                                class="text-sm uppercase tracking-[0.3em] text-emerald-600"
                            >
                                {{ $t("Directory") }}
                            </div>
                            <h1
                                class="mt-2 text-3xl font-semibold tracking-tight text-slate-950"
                            >
                                {{ $t("Company management") }}
                            </h1>
                            <p class="mt-2 text-slate-500">
                                {{
                                    $t(
                                        "Manage company records with repository-backed CRUD endpoints."
                                    )
                                }}
                            </p>
                        </div>

                        <div
                            class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start sm:justify-end"
                        >
                            <div
                                class="flex flex-col gap-2 rounded-[1.5rem] border border-slate-200/80 bg-slate-50 px-4 py-3 sm:max-w-[26rem]"
                            >
                                <div class="flex items-center justify-between gap-3">
                                    <div
                                        class="text-xs font-medium uppercase tracking-[0.2em] text-slate-500"
                                    >
                                        {{ $t("View options") }}
                                    </div>
                                    <div class="text-xs font-medium text-slate-500">
                                        {{ visibleColumnsSummary }}
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-2 sm:flex-row sm:items-center"
                                >
                                    <MultiSelect
                                        v-model="visibleColumnKeys"
                                        :options="availableColumns"
                                        option-label="label"
                                        option-value="value"
                                        :placeholder="$t('Visible columns')"
                                        :selected-items-label="selectedColumnsLabel"
                                        :max-selected-labels="0"
                                        class="w-full"
                                    />
                                    <Button
                                        type="button"
                                        icon="pi pi-refresh"
                                        severity="secondary"
                                        outlined
                                        :label="$t('Reset')"
                                        @click="resetVisibleColumns"
                                    />
                                </div>
                            </div>

                            <div class="flex flex-col gap-2">
                                <Link :href="route('companies.create')">
                                    <Button
                                        :label="$t('New company')"
                                        icon="pi pi-plus"
                                    />
                                </Link>

                                <Button
                                    :label="$t('Refresh')"
                                    icon="pi pi-refresh"
                                    severity="secondary"
                                    size="small"
                                    :disabled="loading"
                                    :loading="loading"
                                    @click="refreshCompanies"
                                    data-testid="companies-refresh"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card
                v-if="selectedCompanies.length > 0"
                class="app-card sticky top-24 z-10 border-0"
            >
                <template #content>
                    <div
                        class="flex flex-col gap-4 rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 lg:flex-row lg:items-center lg:justify-between"
                    >
                        <div>
                            <div class="text-sm font-medium text-emerald-700">
                                {{ $t("Selected records") }}
                            </div>
                            <div class="mt-1 text-lg font-semibold text-emerald-950">
                                {{ selectedCompanies.length }}
                                {{ $t("companies selected") }}
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button
                                :label="$t('Clear selection')"
                                icon="pi pi-times"
                                severity="secondary"
                                outlined
                                @click="selectedCompanies = []"
                            />
                            <Button
                                :label="$t('Activate selected')"
                                icon="pi pi-check"
                                severity="success"
                                @click="activateSelectedCompanies"
                            />
                            <Button
                                :label="$t('Deactivate selected')"
                                icon="pi pi-ban"
                                severity="danger"
                                outlined
                                @click="deactivateSelectedCompanies"
                            />
                            <Button
                                :label="$t('Delete selected')"
                                icon="pi pi-trash"
                                severity="danger"
                                @click="removeSelectedCompanies"
                            />
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
                        :sort-field="tableState.sortField"
                        :sort-order="tableState.sortOrder"
                        :rows="meta.per_page"
                        :first="(meta.current_page - 1) * meta.per_page"
                        :total-records="meta.total"
                        :global-filter-fields="['name', 'email', 'phone']"
                        paginator-template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                        :rows-per-page-options="[10, 25, 50]"
                        table-style="min-width: 60rem"
                        @page="onPage"
                        @filter="onFilter"
                        @sort="onSort"
                    >
                        <template #header>
                            <div
                                class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                            >
                                <div class="text-sm text-slate-500">
                                    {{
                                        $t(
                                            "Repository-backed CRUD with PrimeVue components."
                                        )
                                    }}
                                </div>
                                <div
                                    class="flex w-full flex-col gap-3 xl:w-auto xl:flex-row"
                                >
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
                                            v-model="searchInput"
                                            class="w-full xl:w-80"
                                            :placeholder="$t('Keyword Search')"
                                        />
                                    </IconField>
                                </div>
                            </div>
                        </template>

                        <template #paginatorstart>
                            <span class="text-sm text-slate-500">
                                {{ meta.total }} {{ $t("total records") }}
                            </span>
                        </template>

                        <template #empty>
                            <div class="py-10 text-center">
                                <div class="text-lg font-medium text-slate-700">
                                    {{
                                        $t("No companies found for the current filters.")
                                    }}
                                </div>
                                <div class="mt-2 text-sm text-slate-500">
                                    {{
                                        $t(
                                            "Try clearing filters or broadening your search."
                                        )
                                    }}
                                </div>
                                <Button
                                    class="mt-4"
                                    :label="$t('Clear')"
                                    icon="pi pi-filter-slash"
                                    severity="secondary"
                                    outlined
                                    @click="clearFilters"
                                />
                            </div>
                        </template>

                        <template #loading>
                            <div class="py-10 text-center text-slate-500">
                                {{ $t("Loading companies...") }}
                            </div>
                        </template>

                        <Column selection-mode="multiple" header-style="width: 3rem" />

                        <Column
                            field="name"
                            :header="$t('Company name')"
                            sortable
                            v-if="isColumnVisible('name')"
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
                                <div
                                    class="cursor-pointer py-1"
                                    @dblclick="editCompany(data)"
                                >
                                    <div class="font-medium text-slate-900">
                                        <Link
                                            :href="route('companies.edit', data.id)"
                                            class="inline-flex items-center gap-2 transition hover:text-emerald-700"
                                        >
                                            {{ data.name }}
                                            <i
                                                class="pi pi-arrow-up-right text-xs text-emerald-600"
                                            />
                                        </Link>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ data.address || $t("No address added") }}
                                    </div>
                                    <div class="mt-2">
                                        <Tag
                                            :severity="getContactHealth(data).severity"
                                            :value="getContactHealth(data).value"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="email"
                            :header="$t('Email')"
                            sortable
                            v-if="isColumnVisible('email')"
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
                                <span class="text-slate-600">{{
                                    data.email || $t("N/A")
                                }}</span>
                            </template>
                        </Column>

                        <Column
                            field="phone"
                            :header="$t('Phone')"
                            sortable
                            v-if="isColumnVisible('phone')"
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
                                <span class="text-slate-600">{{
                                    data.phone || $t("N/A")
                                }}</span>
                            </template>
                        </Column>

                        <Column
                            field="employees_count"
                            :header="$t('Employees')"
                            sortable
                            v-if="isColumnVisible('employees_count')"
                        >
                            <template #body="{ data }">
                                <span class="text-slate-600">
                                    {{ data.employees_count }}
                                </span>
                            </template>
                        </Column>

                        <Column
                            field="is_active"
                            :header="$t('Status')"
                            sortable
                            v-if="isColumnVisible('is_active')"
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
                            field="updated_at"
                            :header="$t('Last updated')"
                            sortable
                            v-if="isColumnVisible('updated_at')"
                        >
                            <template #body="{ data }">
                                <span class="text-slate-600">
                                    {{ formatDateTime(data.updated_at) }}
                                </span>
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

                    <div
                        v-if="activeFilters.length > 0"
                        class="mt-5 flex flex-wrap gap-2"
                    >
                        <div
                            v-for="filter in activeFilters"
                            :key="filter.key"
                            class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 text-sm text-slate-700"
                        >
                            <span>{{ filter.label }}</span>
                            <button
                                type="button"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-200 hover:text-slate-700"
                                @click="clearSingleFilter(filter.key)"
                            >
                                <i class="pi pi-times text-xs" />
                            </button>
                        </div>
                        <Button
                            :label="$t('Clear all filters')"
                            icon="pi pi-times"
                            text
                            @click="clearFilters"
                        />
                    </div>
                </template>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
