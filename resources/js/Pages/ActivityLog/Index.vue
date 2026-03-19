<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import RowActionMenu from "@/Components/RowActionMenu.vue";
import activityLogService from "@/Services/ActivityLogService.js";
import { formatDateTime } from "@/Support/dates/formatDate";
import { createDebouncedRequestManager } from "@/Support/tables/createDebouncedRequestManager";
import { currentLocale, trans } from "laravel-vue-i18n";
import { Head } from "@inertiajs/vue3";
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from "vue";
import Button from "primevue/button";
import Card from "primevue/card";
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Dialog from "primevue/dialog";
import IconField from "primevue/iconfield";
import InputIcon from "primevue/inputicon";
import InputText from "primevue/inputtext";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import TabPanel from "primevue/tabpanel";
import TabPanels from "primevue/tabpanels";
import Tabs from "primevue/tabs";
import Tag from "primevue/tag";
import Timeline from "primevue/timeline";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    logNameOptions: { type: Array, default: () => [] },
    eventOptions: { type: Array, default: () => [] },
});

const COLUMN_VISIBILITY_STORAGE_KEY = "activity-log-index-visible-columns";
const DEFAULT_VISIBLE_COLUMN_KEYS = [
    "description",
    "log_name",
    "event",
    "causer_label",
    "subject_label",
    "created_at",
];
const MINIMUM_VISIBLE_COLUMN_KEY = "description";
const SEARCH_DEBOUNCE_MS = 350;

const activities = ref([]);
const loading = ref(false);
const activeView = ref("table");
const visibleColumnKeys = ref([...DEFAULT_VISIBLE_COLUMN_KEYS]);
const selectedActivity = ref(null);
const detailsVisible = ref(false);
const searchInput = ref("");
let isProgrammaticSearchUpdate = false;
const debouncedRequests = createDebouncedRequestManager(SEARCH_DEBOUNCE_MS);
const toast = useToast();

const tableFilters = ref({
    global: { value: null, matchMode: "contains" },
    log_name: { value: null, matchMode: "equals" },
    event: { value: null, matchMode: "equals" },
});

const tableState = reactive({
    page: 1,
    perPage: 10,
    sortField: "created_at",
    sortOrder: -1,
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
        { label: trans("Summary"), value: "description" },
        { label: trans("Log name"), value: "log_name" },
        { label: trans("Event"), value: "event" },
        { label: trans("Actor"), value: "causer_label" },
        { label: trans("Subject"), value: "subject_label" },
        { label: trans("Properties"), value: "properties" },
        { label: trans("Created at"), value: "created_at" },
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

const logNameFilterOptions = computed(() => {
    currentLocale.value;

    return [{ label: trans("All log names"), value: null }, ...props.logNameOptions];
});

const eventFilterOptions = computed(() => {
    currentLocale.value;

    return [{ label: trans("All events"), value: null }, ...props.eventOptions];
});

const quickStats = computed(() => {
    currentLocale.value;

    return [
        {
            label: trans("Entries on this page"),
            value: activities.value.length,
            caption: trans("Rows currently loaded in the table"),
        },
        {
            label: trans("Exceptions on this page"),
            value: activities.value.filter((activity) => activity.event === "exception")
                .length,
            caption: trans("Logged backend exceptions in the current view"),
        },
        {
            label: trans("Frontend errors on this page"),
            value: activities.value.filter(
                (activity) => activity.event === "frontend-error"
            ).length,
            caption: trans("Client-side issues recorded in the current view"),
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

    if (tableFilters.value.log_name.value) {
        filters.push({
            key: "log_name",
            label: `${trans("Log name")}: ${tableFilters.value.log_name.value}`,
        });
    }

    if (tableFilters.value.event.value) {
        filters.push({
            key: "event",
            label: `${trans("Event")}: ${tableFilters.value.event.value}`,
        });
    }

    return filters;
});

const fetchActivities = async () => {
    loading.value = true;

    try {
        const response = await activityLogService.list({
            search: tableFilters.value.global.value || undefined,
            log_name: tableFilters.value.log_name.value || undefined,
            event: tableFilters.value.event.value || undefined,
            sort_field: tableState.sortField,
            sort_direction: tableState.sortOrder === 1 ? "asc" : "desc",
            page: tableState.page,
            per_page: tableState.perPage,
        });

        activities.value = response.data;
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
    await fetchActivities();
};

const onFilter = async (event) => {
    tableFilters.value = event.filters;
    tableState.page = 1;
    await fetchActivities();
};

const onSort = async (event) => {
    tableState.sortField = event.sortField || "created_at";
    tableState.sortOrder = event.sortOrder || -1;
    tableState.page = 1;
    await fetchActivities();
};

const clearFilters = async () => {
    debouncedRequests.clearAll();
    searchInput.value = "";
    tableFilters.value = {
        global: { value: null, matchMode: "contains" },
        log_name: { value: null, matchMode: "equals" },
        event: { value: null, matchMode: "equals" },
    };
    tableState.page = 1;
    await fetchActivities();
};

const refreshActivities = async () => {
    try {
        await fetchActivities();
        showSuccessToast(trans("Activity logs refreshed."));
    } catch (error) {
        showErrorToast(error?.response?.data?.message);
    }
};

const isColumnVisible = (columnKey) => visibleColumnKeys.value.includes(columnKey);

const resetVisibleColumns = () => {
    visibleColumnKeys.value = [...DEFAULT_VISIBLE_COLUMN_KEYS];
};

const clearSingleFilter = async (filterKey) => {
    if (filterKey === "global") {
        isProgrammaticSearchUpdate = true;
        debouncedRequests.clear("global-search");
        searchInput.value = "";
        tableFilters.value.global.value = null;
        tableState.page = 1;
        await fetchActivities();
        isProgrammaticSearchUpdate = false;
        return;
    }

    if (tableFilters.value[filterKey]) {
        tableFilters.value[filterKey].value = null;
    }

    tableState.page = 1;
    await fetchActivities();
};

const applyVisibleColumns = (keys) => {
    const nextKeys = [...new Set(keys)];

    if (!nextKeys.includes(MINIMUM_VISIBLE_COLUMN_KEY)) {
        nextKeys.unshift(MINIMUM_VISIBLE_COLUMN_KEY);
    }

    visibleColumnKeys.value = nextKeys.filter((key) =>
        availableColumns.value.some((column) => column.value === key)
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

const eventSeverity = (event) =>
    ({
        created: "success",
        updated: "info",
        deleted: "danger",
        exception: "danger",
        "frontend-error": "warn",
    }[event] ?? "secondary");

const formatModelType = (value) => {
    if (!value) {
        return trans("N/A");
    }

    const segments = value.split("\\");

    return segments[segments.length - 1];
};

const formatProperties = (properties) => {
    if (!properties || Object.keys(properties).length === 0) {
        return trans("No properties");
    }

    return JSON.stringify(properties, null, 2);
};

const openDetails = (activity) => {
    selectedActivity.value = activity;
    detailsVisible.value = true;
};

const closeDetails = () => {
    detailsVisible.value = false;
    selectedActivity.value = null;
};

const buildRowActions = (activity) => [
    {
        label: trans("Details"),
        icon: "pi pi-eye",
        command: () => openDetails(activity),
    },
];

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

watch(searchInput, (value) => {
    if (isProgrammaticSearchUpdate) {
        return;
    }

    debouncedRequests.schedule("global-search", async () => {
        tableFilters.value.global.value = value || null;
        tableState.page = 1;
        await fetchActivities();
    });
});

onMounted(async () => {
    restoreVisibleColumns();
    searchInput.value = tableFilters.value.global.value ?? "";
    await fetchActivities();
});

onBeforeUnmount(() => {
    debouncedRequests.clearAll();
});
</script>

<template>
    <Head :title="$t('Activity logs')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Activity logs") }}</template>

        <div class="app-grid">
            <Dialog
                v-model:visible="detailsVisible"
                modal
                :header="$t('Activity log details')"
                :style="{ width: 'min(56rem, 96vw)' }"
                class="max-w-full"
                @hide="closeDetails"
            >
                <div
                    v-if="selectedActivity"
                    class="grid gap-6 md:grid-cols-2"
                >
                    <div class="space-y-4">
                        <div>
                            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                {{ $t("Summary") }}
                            </div>
                            <div class="mt-2 text-base font-medium text-slate-900">
                                {{ selectedActivity.description }}
                            </div>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                    {{ $t("Log name") }}
                                </div>
                                <div class="mt-2">
                                    <Tag
                                        severity="secondary"
                                        :value="selectedActivity.log_name"
                                    />
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                    {{ $t("Event") }}
                                </div>
                                <div class="mt-2">
                                    <Tag
                                        :severity="eventSeverity(selectedActivity.event)"
                                        :value="selectedActivity.event || $t('N/A')"
                                    />
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                    {{ $t("Created at") }}
                                </div>
                                <div class="mt-2 text-sm text-slate-700">
                                    {{
                                        formatDateTime(selectedActivity.created_at, {
                                            pattern: "yyyy-mm-dd HH:MM",
                                        })
                                    }}
                                </div>
                            </div>

                            <div>
                                <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                    {{ $t("Batch") }}
                                </div>
                                <div class="mt-2 text-sm text-slate-700 break-all">
                                    {{ selectedActivity.batch_uuid || $t("No batch") }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                {{ $t("Actor") }}
                            </div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ selectedActivity.causer_label || $t("System") }}
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                {{ formatModelType(selectedActivity.causer_type) }}
                            </div>
                            <div
                                v-if="selectedActivity.causer_id"
                                class="mt-1 text-xs text-slate-500"
                            >
                                ID: {{ selectedActivity.causer_id }}
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                            <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                {{ $t("Subject") }}
                            </div>
                            <div class="mt-2 text-sm font-medium text-slate-900">
                                {{ selectedActivity.subject_label || $t("N/A") }}
                            </div>
                            <div class="mt-1 text-sm text-slate-500">
                                {{ formatModelType(selectedActivity.subject_type) }}
                            </div>
                            <div
                                v-if="selectedActivity.subject_id"
                                class="mt-1 text-xs text-slate-500"
                            >
                                ID: {{ selectedActivity.subject_id }}
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-xs uppercase tracking-[0.2em] text-slate-500">
                            {{ $t("Properties") }}
                        </div>
                        <pre
                            class="mt-3 max-h-[24rem] overflow-auto whitespace-pre-wrap rounded-2xl bg-slate-950 px-4 py-3 text-xs text-slate-100"
                        ><code>{{ formatProperties(selectedActivity.properties) }}</code></pre>
                    </div>
                </div>
            </Dialog>

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
                                {{ $t("Observability") }}
                            </div>
                            <h1
                                class="mt-2 text-3xl font-semibold tracking-tight text-slate-950"
                            >
                                {{ $t("Activity log viewer") }}
                            </h1>
                            <p class="mt-2 text-slate-500">
                                {{
                                    $t(
                                        "Review recorded system activity, exceptions, and frontend errors from a single admin table."
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
                                        @update:model-value="applyVisibleColumns"
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
                                <Button
                                    :label="$t('Refresh')"
                                    icon="pi pi-refresh"
                                    severity="secondary"
                                    size="small"
                                    :disabled="loading"
                                    :loading="loading"
                                    @click="refreshActivities"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <Card class="app-card border-0">
                <template #content>
                    <div class="flex flex-col gap-5">
                        <div
                            class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"
                        >
                            <div class="space-y-2">
                                <div class="text-sm text-slate-500">
                                    {{
                                        activeView === "table"
                                            ? $t(
                                                  "Read-only monitoring table backed by the activity_log repository layer."
                                              )
                                            : $t(
                                                  "Review the same filtered activity feed in a chronological timeline."
                                              )
                                    }}
                                </div>
                                <Tabs
                                    v-model:value="activeView"
                                    data-test-id="activity-view-tabs"
                                >
                                    <TabList>
                                        <Tab
                                            value="table"
                                            data-test-id="activity-view-tab-table"
                                        >
                                            {{ $t("Table") }}
                                        </Tab>
                                        <Tab
                                            value="timeline"
                                            data-test-id="activity-view-tab-timeline"
                                        >
                                            {{ $t("Timeline") }}
                                        </Tab>
                                    </TabList>
                                    <TabPanels class="hidden">
                                        <TabPanel value="table" />
                                        <TabPanel value="timeline" />
                                    </TabPanels>
                                </Tabs>
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

                        <div
                            v-if="activeView === 'table'"
                            data-test-id="activity-view-panel-table"
                        >
                            <DataTable
                                v-model:filters="tableFilters"
                                :value="activities"
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
                                :global-filter-fields="[
                                    'description',
                                    'log_name',
                                    'event',
                                    'causer_label',
                                    'subject_label',
                                ]"
                                paginator-template="PrevPageLink PageLinks NextPageLink RowsPerPageDropdown"
                                :rows-per-page-options="[10, 25, 50]"
                                table-style="min-width: 72rem"
                                @page="onPage"
                                @filter="onFilter"
                                @sort="onSort"
                            >

                        <template #paginatorstart>
                            <span class="text-sm text-slate-500">
                                {{ meta.total }} {{ $t("total records") }}
                            </span>
                        </template>

                        <template #empty>
                            <div class="py-10 text-center">
                                <div class="text-lg font-medium text-slate-700">
                                    {{
                                        $t(
                                            "No activity log entries found for the current filters."
                                        )
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
                                {{ $t("Loading activity logs...") }}
                            </div>
                        </template>

                        <Column
                            field="description"
                            :header="$t('Summary')"
                            sortable
                            v-if="isColumnVisible('description')"
                        >
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">
                                        {{ data.description }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{
                                            data.batch_uuid
                                                ? `${$t('Batch')}: ${data.batch_uuid}`
                                                : $t("No batch")
                                        }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="log_name"
                            :header="$t('Log name')"
                            sortable
                            v-if="isColumnVisible('log_name')"
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <Select
                                    v-model="filterModel.value"
                                    :options="logNameFilterOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full min-w-48"
                                    show-clear
                                    :placeholder="$t('All log names')"
                                    @change="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <Tag severity="secondary" :value="data.log_name" />
                            </template>
                        </Column>

                        <Column
                            field="event"
                            :header="$t('Event')"
                            sortable
                            v-if="isColumnVisible('event')"
                        >
                            <template #filter="{ filterModel, filterCallback }">
                                <Select
                                    v-model="filterModel.value"
                                    :options="eventFilterOptions"
                                    option-label="label"
                                    option-value="value"
                                    class="w-full min-w-48"
                                    show-clear
                                    :placeholder="$t('All events')"
                                    @change="filterCallback()"
                                />
                            </template>
                            <template #body="{ data }">
                                <Tag
                                    :severity="eventSeverity(data.event)"
                                    :value="data.event || $t('N/A')"
                                />
                            </template>
                        </Column>

                        <Column
                            field="causer_label"
                            :header="$t('Actor')"
                            v-if="isColumnVisible('causer_label')"
                        >
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">
                                        {{ data.causer_label || $t("System") }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ formatModelType(data.causer_type) }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="subject_label"
                            :header="$t('Subject')"
                            v-if="isColumnVisible('subject_label')"
                        >
                            <template #body="{ data }">
                                <div class="py-1">
                                    <div class="font-medium text-slate-900">
                                        {{ data.subject_label || $t("N/A") }}
                                    </div>
                                    <div class="mt-1 text-sm text-slate-500">
                                        {{ formatModelType(data.subject_type) }}
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="properties"
                            :header="$t('Properties')"
                            v-if="isColumnVisible('properties')"
                        >
                            <template #body="{ data }">
                                <pre
                                    class="max-w-[28rem] overflow-x-auto whitespace-pre-wrap rounded-2xl bg-slate-950 px-4 py-3 text-xs text-slate-100"
                                ><code>{{ formatProperties(data.properties) }}</code></pre>
                            </template>
                        </Column>

                        <Column
                            field="created_at"
                            :header="$t('Created at')"
                            sortable
                            v-if="isColumnVisible('created_at')"
                        >
                            <template #body="{ data }">
                                <span class="text-slate-600">
                                    {{
                                        formatDateTime(data.created_at, {
                                            pattern: "yyyy-mm-dd HH:MM",
                                        })
                                    }}
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
                        </div>

                        <div
                            v-else
                            data-test-id="activity-view-panel-timeline"
                        >
                            <div
                                v-if="loading"
                                class="py-10 text-center text-slate-500"
                            >
                                {{ $t("Loading activity logs...") }}
                            </div>

                            <div
                                v-else-if="activities.length === 0"
                                class="py-10 text-center"
                            >
                                <div class="text-lg font-medium text-slate-700">
                                    {{
                                        $t(
                                            "No activity log entries found for the current filters."
                                        )
                                    }}
                                </div>
                                <div class="mt-2 text-sm text-slate-500">
                                    {{
                                        $t(
                                            "Try clearing filters or broadening your search."
                                        )
                                    }}
                                </div>
                            </div>

                            <Timeline
                                v-else
                                :value="activities"
                                align="alternate"
                                class="activity-log-timeline"
                            >
                                <template #opposite="{ item }">
                                    <div class="text-sm text-slate-500">
                                        {{
                                            formatDateTime(item.created_at, {
                                                pattern: "yyyy-mm-dd HH:MM",
                                            })
                                        }}
                                    </div>
                                </template>

                                <template #marker="{ item }">
                                    <span
                                        class="flex h-3.5 w-3.5 rounded-full border-2 border-white shadow-sm"
                                        :class="{
                                            'bg-emerald-500': eventSeverity(item.event) === 'success',
                                            'bg-sky-500': eventSeverity(item.event) === 'info',
                                            'bg-amber-500': eventSeverity(item.event) === 'warn',
                                            'bg-rose-500': eventSeverity(item.event) === 'danger',
                                            'bg-slate-400': eventSeverity(item.event) === 'secondary',
                                        }"
                                    />
                                </template>

                                <template #content="{ item, index }">
                                    <article
                                        class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm"
                                        :data-test-id="`timeline-item-${index}`"
                                    >
                                        <div
                                            class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between"
                                        >
                                            <div class="space-y-3">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <Tag severity="secondary" :value="item.log_name" />
                                                    <Tag
                                                        :severity="eventSeverity(item.event)"
                                                        :value="item.event || $t('N/A')"
                                                    />
                                                </div>

                                                <div>
                                                    <h3 class="text-base font-semibold text-slate-950">
                                                        {{ item.description }}
                                                    </h3>
                                                    <p class="mt-1 text-sm text-slate-500">
                                                        {{
                                                            item.batch_uuid
                                                                ? `${$t('Batch')}: ${item.batch_uuid}`
                                                                : $t("No batch")
                                                        }}
                                                    </p>
                                                </div>

                                                <dl
                                                    class="grid gap-3 text-sm text-slate-600 sm:grid-cols-2"
                                                >
                                                    <div>
                                                        <dt
                                                            class="text-xs uppercase tracking-[0.2em] text-slate-400"
                                                        >
                                                            {{ $t("Actor") }}
                                                        </dt>
                                                        <dd class="mt-1 font-medium text-slate-900">
                                                            {{ item.causer_label || $t("System") }}
                                                        </dd>
                                                        <dd class="mt-1 text-slate-500">
                                                            {{ formatModelType(item.causer_type) }}
                                                        </dd>
                                                    </div>

                                                    <div>
                                                        <dt
                                                            class="text-xs uppercase tracking-[0.2em] text-slate-400"
                                                        >
                                                            {{ $t("Subject") }}
                                                        </dt>
                                                        <dd class="mt-1 font-medium text-slate-900">
                                                            {{ item.subject_label || $t("N/A") }}
                                                        </dd>
                                                        <dd class="mt-1 text-slate-500">
                                                            {{ formatModelType(item.subject_type) }}
                                                        </dd>
                                                    </div>
                                                </dl>
                                            </div>

                                            <div class="flex shrink-0 items-start">
                                                <Button
                                                    type="button"
                                                    size="small"
                                                    icon="pi pi-eye"
                                                    severity="secondary"
                                                    outlined
                                                    :label="$t('Details')"
                                                    :data-test-id="`timeline-details-${index}`"
                                                    @click="openDetails(item)"
                                                />
                                            </div>
                                        </div>
                                    </article>
                                </template>
                            </Timeline>
                        </div>
                    </div>

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
