<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import userTemporaryPermissionService from "@/Services/UserTemporaryPermissionService";
import { requestConfirmation } from "@/Support/confirm/requestConfirmation";
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

const props = defineProps({
    userOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
});

const COLUMN_VISIBILITY_STORAGE_KEY = "user-temporary-permission-index-visible-columns";
const DEFAULT_VISIBLE_COLUMN_KEYS = [
    "user_name",
    "permission_name",
    "status",
    "starts_at",
    "ends_at",
    "updated_at",
];
const MINIMUM_VISIBLE_COLUMN_KEY = "user_name";
const SEARCH_DEBOUNCE_MS = 350;

const assignments = ref([]);
const selectedAssignments = ref([]);
const loading = ref(false);
const visibleColumnKeys = ref([...DEFAULT_VISIBLE_COLUMN_KEYS]);
const searchInput = ref("");
let searchDebounceTimer = null;
const confirm = useConfirm();
const toast = useToast();

const tableFilters = ref({
    global: { value: null, matchMode: "contains" },
    user_id: { value: null, matchMode: "equals" },
    permission_id: { value: null, matchMode: "equals" },
    status: { value: null, matchMode: "equals" },
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

const availableColumns = computed(() => {
    currentLocale.value;

    return [
        { label: trans("User"), value: "user_name" },
        { label: trans("Permission"), value: "permission_name" },
        { label: trans("Status"), value: "status" },
        { label: trans("Starts at"), value: "starts_at" },
        { label: trans("Ends at"), value: "ends_at" },
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

const statusOptions = computed(() => {
    currentLocale.value;

    return [
        { label: trans("All statuses"), value: null },
        { label: trans("Active"), value: "active" },
        { label: trans("Upcoming"), value: "upcoming" },
        { label: trans("Expired"), value: "expired" },
    ];
});

const quickStats = computed(() => {
    currentLocale.value;

    return [
        {
            label: trans("Assignments on this page"),
            value: assignments.value.length,
            caption: trans("Rows currently loaded in the table"),
        },
        {
            label: trans("Active on this page"),
            value: assignments.value.filter((assignment) => assignment.status === "active").length,
            caption: trans("Assignments currently effective"),
        },
        {
            label: trans("Expired on this page"),
            value: assignments.value.filter((assignment) => assignment.status === "expired").length,
            caption: trans("Assignments no longer effective"),
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

    if (tableFilters.value.user_id.value) {
        const selectedUser = props.userOptions.find((option) => option.value === tableFilters.value.user_id.value);

        filters.push({
            key: "user_id",
            label: `${trans("User")}: ${selectedUser?.label ?? tableFilters.value.user_id.value}`,
        });
    }

    if (tableFilters.value.permission_id.value) {
        const selectedPermission = props.permissionOptions.find(
            (option) => option.value === tableFilters.value.permission_id.value,
        );

        filters.push({
            key: "permission_id",
            label: `${trans("Permission")}: ${selectedPermission?.label ?? tableFilters.value.permission_id.value}`,
        });
    }

    if (tableFilters.value.status.value) {
        filters.push({
            key: "status",
            label: `${trans("Status")}: ${statusLabel(tableFilters.value.status.value)}`,
        });
    }

    return filters;
});

const fetchAssignments = async () => {
    loading.value = true;

    try {
        const response = await userTemporaryPermissionService.list({
            search: tableFilters.value.global.value || undefined,
            user_id: tableFilters.value.user_id.value || undefined,
            permission_id: tableFilters.value.permission_id.value || undefined,
            status: tableFilters.value.status.value || undefined,
            page: tableState.page,
            per_page: tableState.perPage,
        });

        assignments.value = response.data;
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
    await fetchAssignments();
};

const onFilter = async (event) => {
    tableFilters.value = event.filters;
    tableState.page = 1;
    await fetchAssignments();
};

const removeAssignment = async (assignment) => {
    const accepted = await requestConfirmation(confirm, {
        message: trans("Delete temporary permission for :name?", { name: assignment.user_name }),
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
        await userTemporaryPermissionService.destroy(assignment.id);

        if (assignments.value.length === 1 && tableState.page > 1) {
            tableState.page -= 1;
        }

        await fetchAssignments();
        showSuccessToast(trans("Temporary permission deleted successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const removeSelectedAssignments = async () => {
    if (selectedAssignments.value.length === 0) {
        return;
    }

    const accepted = await requestConfirmation(confirm, {
        message: trans("Delete :count selected temporary permissions?", {
            count: selectedAssignments.value.length,
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
        await userTemporaryPermissionService.bulkDestroy(
            selectedAssignments.value.map((assignment) => assignment.id),
        );
        selectedAssignments.value = [];

        if (assignments.value.length === 1 && tableState.page > 1) {
            tableState.page -= 1;
        }

        await fetchAssignments();
        showSuccessToast(trans("Temporary permissions deleted successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const refreshAssignments = async () => {
    try {
        await fetchAssignments();
        showSuccessToast(trans("Temporary permissions refreshed."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const buildRowActions = (assignment) => [
    {
        label: trans("Edit"),
        icon: "pi pi-pencil",
        command: () => router.get(route("user-temporary-permissions.edit", assignment.id)),
    },
    {
        label: trans("Delete"),
        icon: "pi pi-trash",
        command: () => removeAssignment(assignment),
    },
];

const clearFilters = async () => {
    searchInput.value = "";
    tableFilters.value = {
        global: { value: null, matchMode: "contains" },
        user_id: { value: null, matchMode: "equals" },
        permission_id: { value: null, matchMode: "equals" },
        status: { value: null, matchMode: "equals" },
    };
    tableState.page = 1;
    await fetchAssignments();
};

const clearSingleFilter = async (key) => {
    if (key === "global") {
        searchInput.value = "";
        tableFilters.value.global.value = null;
    } else if (tableFilters.value[key]) {
        tableFilters.value[key].value = null;
    }

    tableState.page = 1;
    await fetchAssignments();
};

const isColumnVisible = (key) => visibleColumnKeys.value.includes(key);

const applyVisibleColumns = (keys) => {
    const nextKeys = [...new Set(keys)];

    if (!nextKeys.includes(MINIMUM_VISIBLE_COLUMN_KEY)) {
        nextKeys.unshift(MINIMUM_VISIBLE_COLUMN_KEY);
    }

    visibleColumnKeys.value = nextKeys.filter((key) =>
        availableColumns.value.some((column) => column.value === key),
    );
};

const restoreVisibleColumns = () => {
    const saved = window.localStorage.getItem(COLUMN_VISIBILITY_STORAGE_KEY);

    if (!saved) {
        return;
    }

    try {
        const parsed = JSON.parse(saved);

        if (Array.isArray(parsed)) {
            applyVisibleColumns(parsed);
        }
    } catch {
        window.localStorage.removeItem(COLUMN_VISIBILITY_STORAGE_KEY);
    }
};

const persistVisibleColumns = () => {
    window.localStorage.setItem(
        COLUMN_VISIBILITY_STORAGE_KEY,
        JSON.stringify(visibleColumnKeys.value),
    );
};

const formatDateTime = (value) => {
    if (!value) {
        return trans("N/A");
    }

    return new Intl.DateTimeFormat(currentLocale.value, {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(value));
};

const statusSeverity = (status) => ({
    active: "success",
    upcoming: "info",
    expired: "secondary",
}[status] ?? "secondary");

const statusLabel = (status) =>
    ({
        active: trans("Active"),
        upcoming: trans("Upcoming"),
        expired: trans("Expired"),
    })[status] ?? status;

watch(searchInput, (value) => {
    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(async () => {
        tableFilters.value.global.value = value || null;
        tableState.page = 1;
        await fetchAssignments();
    }, SEARCH_DEBOUNCE_MS);
});

watch(
    visibleColumnKeys,
    () => {
        persistVisibleColumns();
    },
    { deep: true },
);

onMounted(async () => {
    restoreVisibleColumns();
    await fetchAssignments();
});

onBeforeUnmount(() => {
    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
    }
});
</script>

<template>
    <Head :title="$t('Temporary Permissions')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Temporary Permissions") }}</template>

        <div class="space-y-6">
            <ConfirmDialog />

            <div class="grid gap-4 xl:grid-cols-3">
                <Card v-for="stat in quickStats" :key="stat.label" class="app-card border-0">
                    <template #content>
                        <div class="text-sm font-medium uppercase tracking-[0.3em] text-slate-500">
                            {{ stat.label }}
                        </div>
                        <div class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ stat.value }}
                        </div>
                        <div class="mt-2 text-sm text-slate-500">
                            {{ stat.caption }}
                        </div>
                    </template>
                </Card>
            </div>

            <Card class="app-card border-0">
                <template #content>
                    <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                                {{ $t("Access Control") }}
                            </div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                {{ $t("Manage temporary permissions") }}
                            </h1>
                            <p class="mt-2 max-w-3xl text-slate-500">
                                {{ $t("Grant direct user permissions that activate only within a defined time window.") }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button
                                :label="$t('Refresh')"
                                icon="pi pi-refresh"
                                severity="secondary"
                                outlined
                                @click="refreshAssignments"
                            />
                            <Link :href="route('user-temporary-permissions.create')">
                                <Button :label="$t('Create temporary permission')" icon="pi pi-plus" />
                            </Link>
                        </div>
                    </div>

                    <div class="mb-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex flex-wrap gap-3">
                            <Button
                                :label="$t('Delete selected')"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                :disabled="selectedAssignments.length === 0"
                                @click="removeSelectedAssignments"
                            />
                        </div>

                        <MultiSelect
                            v-model="visibleColumnKeys"
                            :options="availableColumns"
                            option-label="label"
                            option-value="value"
                            class="w-full xl:w-80"
                            :max-selected-labels="0"
                            :selected-items-label="visibleColumnsSummary"
                            :placeholder="$t('Visible columns')"
                            @update:model-value="applyVisibleColumns"
                        />
                    </div>

                    <DataTable
                        v-model:selection="selectedAssignments"
                        v-model:filters="tableFilters"
                        :value="assignments"
                        :loading="loading"
                        lazy
                        paginator
                        removableSort
                        filter-display="menu"
                        data-key="id"
                        :rows="meta.per_page"
                        :first="(meta.current_page - 1) * meta.per_page"
                        :total-records="meta.total"
                        :global-filter-fields="['user_name', 'permission_name', 'reason']"
                        paginator-template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                        :rows-per-page-options="[10, 25, 50]"
                        table-style="min-width: 64rem"
                        @page="onPage"
                        @filter="onFilter"
                    >
                        <template #header>
                            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                <div class="text-sm text-slate-500">
                                    {{ $t("Repository-backed CRUD with PrimeVue components.") }}
                                </div>
                                <div class="flex w-full flex-col gap-3 xl:w-auto xl:flex-row">
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
                                    {{ $t("No temporary permissions found for the current filters.") }}
                                </div>
                                <div class="mt-2 text-sm text-slate-500">
                                    {{ $t("Try clearing filters or broadening your search.") }}
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
                                {{ $t("Loading temporary permissions...") }}
                            </div>
                        </template>

                        <Column selection-mode="multiple" header-style="width: 3rem" />

                        <Column field="user_name" :header="$t('User')" sortable v-if="isColumnVisible('user_name')">
                            <template #filter="{ filterModel, filterCallback }">
                                <Select
                                    v-model="filterModel.value"
                                    :options="userOptions"
                                    option-label="label"
                                    option-value="value"
                                    filter
                                    class="w-full min-w-56"
                                    show-clear
                                    :placeholder="$t('All users')"
                                    @change="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">
                                        <Link
                                            :href="route('user-temporary-permissions.edit', data.id)"
                                            class="inline-flex items-center gap-2 transition hover:text-emerald-700"
                                        >
                                            {{ data.user_name }}
                                            <i class="pi pi-arrow-up-right text-xs text-emerald-600" />
                                        </Link>
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ data.reason || $t("No reason added") }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="permission_name"
                            :header="$t('Permission')"
                            sortable
                            v-if="isColumnVisible('permission_name')"
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <Select
                                    v-model="filterModel.value"
                                    :options="permissionOptions"
                                    option-label="label"
                                    option-value="value"
                                    filter
                                    class="w-full min-w-56"
                                    show-clear
                                    :placeholder="$t('All permissions')"
                                    @change="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <span class="text-slate-700">{{ data.permission_name }}</span>
                            </template>
                        </Column>

                        <Column field="status" :header="$t('Status')" sortable v-if="isColumnVisible('status')">
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
                                <Tag :severity="statusSeverity(data.status)" :value="statusLabel(data.status)" />
                            </template>
                        </Column>

                        <Column field="starts_at" :header="$t('Starts at')" sortable v-if="isColumnVisible('starts_at')">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ formatDateTime(data.starts_at) }}</span>
                            </template>
                        </Column>

                        <Column field="ends_at" :header="$t('Ends at')" sortable v-if="isColumnVisible('ends_at')">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ formatDateTime(data.ends_at) }}</span>
                            </template>
                        </Column>

                        <Column field="updated_at" :header="$t('Last updated')" sortable v-if="isColumnVisible('updated_at')">
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ formatDateTime(data.updated_at) }}</span>
                            </template>
                        </Column>

                        <Column :header="$t('Actions')" header-class="text-right" body-class="text-right">
                            <template #body="{ data }">
                                <RowActionMenu :items="buildRowActions(data)" />
                            </template>
                        </Column>
                    </DataTable>

                    <div v-if="activeFilters.length > 0" class="mt-5 flex flex-wrap gap-2">
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
                        <Button :label="$t('Clear all filters')" icon="pi pi-times" text @click="clearFilters" />
                    </div>
                </template>
            </Card>
        </div>
    </AuthenticatedLayout>
</template>
