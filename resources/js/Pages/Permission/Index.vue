<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import CreateModal from "@/Pages/Permission/CreateModal.vue";
import EditModal from "@/Pages/Permission/EditModal.vue";
import permissionService from "@/Services/PermissionService";
import { requestConfirmation } from "@/Support/confirm/requestConfirmation";
import { currentLocale, trans } from "laravel-vue-i18n";
import { Head } from "@inertiajs/vue3";
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
    guardOptions: { type: Array, default: () => [] },
});

const COLUMN_VISIBILITY_STORAGE_KEY = "permission-index-visible-columns";
const DEFAULT_VISIBLE_COLUMN_KEYS = ["name", "guard_name", "roles_count", "updated_at"];
const MINIMUM_VISIBLE_COLUMN_KEY = "name";
const SEARCH_DEBOUNCE_MS = 350;

const permissions = ref([]);
const selectedPermissions = ref([]);
const loading = ref(false);
const actionLoading = ref(false);
const createOpen = ref(false);
const editOpen = ref(false);
const editPermission = ref(null);
const visibleColumnKeys = ref([...DEFAULT_VISIBLE_COLUMN_KEYS]);
const searchInput = ref("");
let searchDebounceTimer = null;
const confirm = useConfirm();
const toast = useToast();

const tableFilters = ref({
    global: { value: null, matchMode: "contains" },
    name: { value: null, matchMode: "contains" },
    guard_name: { value: null, matchMode: "equals" },
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
        { label: trans("Permission name"), value: "name" },
        { label: trans("Guard"), value: "guard_name" },
        { label: trans("Assigned roles"), value: "roles_count" },
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

const guardFilterOptions = computed(() => {
    currentLocale.value;

    return [{ label: trans("All guards"), value: null }, ...props.guardOptions];
});

const quickStats = computed(() => {
    currentLocale.value;

    return [
        {
            label: trans("Permissions on this page"),
            value: permissions.value.length,
            caption: trans("Rows currently loaded in the table"),
        },
        {
            label: trans("Assigned on this page"),
            value: permissions.value.filter((permission) => permission.roles_count > 0).length,
            caption: trans("Permissions already used by at least one role"),
        },
        {
            label: trans("Guard"),
            value: tableFilters.value.guard_name.value || trans("All guards"),
            caption: trans("Current guard filter applied to the list"),
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
            label: `${trans("Permission name")}: ${tableFilters.value.name.value}`,
        });
    }

    if (tableFilters.value.guard_name.value) {
        filters.push({
            key: "guard_name",
            label: `${trans("Guard")}: ${tableFilters.value.guard_name.value}`,
        });
    }

    return filters;
});

const fetchPermissions = async () => {
    loading.value = true;

    try {
        const response = await permissionService.list({
            search: tableFilters.value.global.value || undefined,
            name: tableFilters.value.name.value || undefined,
            guard_name: tableFilters.value.guard_name.value || undefined,
            page: tableState.page,
            per_page: tableState.perPage,
        });

        permissions.value = response.data;
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
    await fetchPermissions();
};

const onFilter = async (event) => {
    tableFilters.value = event.filters;
    tableState.page = 1;
    await fetchPermissions();
};

const removePermission = async (permission) => {
    const accepted = await requestConfirmation(confirm, {
        message: trans("Delete :name?", { name: permission.name }),
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
        await permissionService.destroy(permission.id);

        if (permissions.value.length === 1 && tableState.page > 1) {
            tableState.page -= 1;
        }

        await fetchPermissions();
        showSuccessToast(trans("Permission deleted successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const removeSelectedPermissions = async () => {
    if (selectedPermissions.value.length === 0) {
        return;
    }

    const accepted = await requestConfirmation(confirm, {
        message: trans("Delete :count selected permissions?", {
            count: selectedPermissions.value.length,
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
        await permissionService.bulkDestroy(
            selectedPermissions.value.map((permission) => permission.id),
        );
        selectedPermissions.value = [];

        if (permissions.value.length === 1 && tableState.page > 1) {
            tableState.page -= 1;
        }

        await fetchPermissions();
        showSuccessToast(trans("Permissions deleted successfully."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const refreshPermissions = async () => {
    try {
        await fetchPermissions();
        showSuccessToast(trans("Permissions refreshed."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const openCreate = () => {
    createOpen.value = true;
};

const openEditModal = async (permission) => {
    actionLoading.value = true;

    try {
        const response = await permissionService.show(permission.id);
        editPermission.value = response.data;
        editOpen.value = true;
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    } finally {
        actionLoading.value = false;
    }
};

const handleSaved = async (message) => {
    selectedPermissions.value = [];
    await fetchPermissions();
    showSuccessToast(message);
};

const buildRowActions = (permission) => [
    {
        label: trans("Edit"),
        icon: "pi pi-pencil",
        disabled: actionLoading.value,
        command: () => openEditModal(permission),
    },
    {
        label: trans("Delete"),
        icon: "pi pi-trash",
        disabled: actionLoading.value,
        command: () => removePermission(permission),
    },
];

const clearFilters = async () => {
    searchInput.value = "";
    tableFilters.value = {
        global: { value: null, matchMode: "contains" },
        name: { value: null, matchMode: "contains" },
        guard_name: { value: null, matchMode: "equals" },
    };
    tableState.page = 1;
    await fetchPermissions();
};

const clearSingleFilter = async (key) => {
    if (key === "global") {
        searchInput.value = "";
        tableFilters.value.global.value = null;
    } else if (tableFilters.value[key]) {
        tableFilters.value[key].value = null;
    }

    tableState.page = 1;
    await fetchPermissions();
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

watch(searchInput, (value) => {
    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = window.setTimeout(async () => {
        tableFilters.value.global.value = value || null;
        tableState.page = 1;
        await fetchPermissions();
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
    await fetchPermissions();
});

onBeforeUnmount(() => {
    if (searchDebounceTimer) {
        window.clearTimeout(searchDebounceTimer);
    }
});
</script>

<template>
    <Head :title="$t('Permissions')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Permissions") }}</template>

        <div class="space-y-6">
            <ConfirmDialog />
            <CreateModal
                v-model="createOpen"
                :guard-options="guardOptions"
                @saved="handleSaved"
            />
            <EditModal
                v-model="editOpen"
                :permission="editPermission"
                :guard-options="guardOptions"
                @saved="handleSaved"
            />

            <div class="grid gap-4 xl:grid-cols-3">
                <Card
                    v-for="stat in quickStats"
                    :key="stat.label"
                    class="app-card border-0"
                >
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
                                {{ $t("Manage permissions") }}
                            </h1>
                            <p class="mt-2 max-w-3xl text-slate-500">
                                {{ $t("Maintain the permission catalog used by roles and authorization checks across the application.") }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Button
                                :label="$t('Refresh')"
                                icon="pi pi-refresh"
                                severity="secondary"
                                outlined
                                @click="refreshPermissions"
                            />
                            <Button
                                :label="$t('Create permission')"
                                icon="pi pi-plus"
                                @click="openCreate"
                            />
                        </div>
                    </div>

                    <div class="mb-5 flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                        <div class="flex flex-wrap gap-3">
                            <Button
                                :label="$t('Delete selected')"
                                icon="pi pi-trash"
                                severity="danger"
                                outlined
                                :disabled="selectedPermissions.length === 0"
                                @click="removeSelectedPermissions"
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
                        v-model:selection="selectedPermissions"
                        v-model:filters="tableFilters"
                        :value="permissions"
                        :loading="loading"
                        lazy
                        paginator
                        removableSort
                        filter-display="menu"
                        data-key="id"
                        :rows="meta.per_page"
                        :first="(meta.current_page - 1) * meta.per_page"
                        :total-records="meta.total"
                        :global-filter-fields="['name', 'guard_name']"
                        paginator-template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                        :rows-per-page-options="[10, 25, 50]"
                        table-style="min-width: 56rem"
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
                                    {{ $t("No permissions found for the current filters.") }}
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
                                {{ $t("Loading permissions...") }}
                            </div>
                        </template>

                        <Column selection-mode="multiple" header-style="width: 3rem" />

                        <Column
                            field="name"
                            :header="$t('Permission name')"
                            sortable
                            v-if="isColumnVisible('name')"
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <InputText
                                    v-model="filterModel.value"
                                    class="w-full"
                                    :placeholder="$t('Permission name')"
                                    @input="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <div class="cursor-pointer py-1" @dblclick="openEditModal(data)">
                                    <div class="font-medium text-slate-900">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-2 transition hover:text-emerald-700"
                                            :disabled="actionLoading"
                                            @click="openEditModal(data)"
                                        >
                                            {{ data.name }}
                                            <i class="pi pi-arrow-up-right text-xs text-emerald-600" />
                                        </button>
                                    </div>
                                    <div class="mt-2">
                                        <Tag
                                            :severity="data.roles_count > 0 ? 'success' : 'secondary'"
                                            :value="data.roles_count > 0 ? $t('Assigned') : $t('Unused')"
                                        />
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="guard_name"
                            :header="$t('Guard')"
                            sortable
                            v-if="isColumnVisible('guard_name')"
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <Select
                                    v-model="filterModel.value"
                                    :options="guardFilterOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full min-w-40"
                                    show-clear
                                    :placeholder="$t('All guards')"
                                    @change="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <Tag severity="info" :value="data.guard_name" />
                            </template>
                        </Column>

                        <Column
                            field="roles_count"
                            :header="$t('Assigned roles')"
                            sortable
                            v-if="isColumnVisible('roles_count')"
                        >
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ data.roles_count }}</span>
                            </template>
                        </Column>

                        <Column
                            field="updated_at"
                            :header="$t('Last updated')"
                            sortable
                            v-if="isColumnVisible('updated_at')"
                        >
                            <template #body="{ data }">
                                <span class="text-slate-600">{{ formatDateTime(data.updated_at) }}</span>
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
