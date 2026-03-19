import { computed, defineComponent, h, inject, provide } from "vue";
import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";

import {
    defaultListResponse,
    flushPromises,
    installBrowserGlobals,
    mountPage,
    resetBrowserState,
    toastAddMock,
} from "./helpers/pageTestUtils";

const listMock = vi.fn();

const TabsStub = defineComponent({
    name: "Tabs",
    props: {
        value: { type: String, default: "" },
    },
    emits: ["update:value"],
    setup(props, { emit, slots, attrs }) {
        provide("tabsValue", computed(() => props.value));
        provide("setTabsValue", (value) => emit("update:value", value));

        return () => h("div", attrs, slots.default?.());
    },
});

const TabListStub = defineComponent({
    name: "TabList",
    setup(_, { slots }) {
        return () => h("div", { "data-test-id": "activity-view-tab-list" }, slots.default?.());
    },
});

const TabStub = defineComponent({
    name: "Tab",
    props: {
        value: { type: String, default: "" },
    },
    setup(props, { slots, attrs }) {
        const tabsValue = inject("tabsValue", computed(() => ""));
        const setTabsValue = inject("setTabsValue", () => {});

        return () =>
            h(
                "button",
                {
                    ...attrs,
                    type: "button",
                    "data-active": String(tabsValue.value === props.value),
                    onClick: () => setTabsValue(props.value),
                },
                slots.default?.(),
            );
    },
});

const TabPanelsStub = defineComponent({
    name: "TabPanels",
    setup(_, { slots, attrs }) {
        return () => h("div", attrs, slots.default?.());
    },
});

const TabPanelStub = defineComponent({
    name: "TabPanel",
    props: {
        value: { type: String, default: "" },
    },
    setup(props, { slots, attrs }) {
        const tabsValue = inject("tabsValue", computed(() => ""));

        return () =>
            tabsValue.value === props.value ? h("div", attrs, slots.default?.()) : null;
    },
});

const TimelineStub = defineComponent({
    name: "Timeline",
    props: {
        value: { type: Array, default: () => [] },
    },
    setup(props, { slots, attrs }) {
        return () =>
            h(
                "div",
                {
                    ...attrs,
                    "data-test-id": "timeline",
                },
                props.value.map((item, index) =>
                    h("div", { key: item.id ?? index, "data-test-id": `timeline-entry-${index}` }, [
                        h("div", { "data-test-id": `timeline-opposite-${index}` }, slots.opposite?.({ item, index })),
                        h("div", { "data-test-id": `timeline-marker-${index}` }, slots.marker?.({ item, index })),
                        h("div", { "data-test-id": `timeline-content-${index}` }, slots.content?.({ item, index })),
                    ]),
                ),
            );
    },
});

vi.mock("@/Services/ActivityLogService.js", () => ({
    default: {
        list: listMock,
    },
}));

vi.mock("@inertiajs/vue3", () => ({
    Head: defineComponent({
        name: "Head",
        props: { title: { type: String, default: "" } },
        setup() {
            return () => h("div");
        },
    }),
}));

vi.mock("laravel-vue-i18n", () => ({
    currentLocale: { value: "en" },
    trans: (value, params = {}) =>
        Object.entries(params).reduce(
            (result, [key, paramValue]) =>
                result.replace(`:${key}`, String(paramValue)),
            value,
        ),
}));

vi.mock("primevue/usetoast", () => ({
    useToast: () => ({
        add: toastAddMock,
    }),
}));

vi.mock("@/Support/dates/formatDate", () => ({
    formatDateTime: (value) => value ?? "N/A",
}));

const pageStubs = {
    Tabs: TabsStub,
    TabList: TabListStub,
    Tab: TabStub,
    TabPanels: TabPanelsStub,
    TabPanel: TabPanelStub,
    Timeline: TimelineStub,
};

describe("ActivityLog/Index", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    description: "Company updated",
                    log_name: "companies",
                    event: "updated",
                    causer_label: "Alice Admin",
                    causer_type: "App\\Models\\User",
                    causer_id: 12,
                    subject_label: "Acme Kft",
                    subject_type: "App\\Models\\Company",
                    subject_id: 42,
                    properties: { attributes: { name: "Acme Kft" } },
                    batch_uuid: "batch-1",
                    created_at: "2026-03-18 10:00:00",
                },
            ]),
        );
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("loads the page with the default created_at sort and updates sort parameters", async () => {
        const { default: ActivityLogIndex } = await import("@/Pages/ActivityLog/Index.vue");

        const wrapper = mountPage(ActivityLogIndex, {
            props: {
                logNameOptions: [{ label: "companies", value: "companies" }],
                eventOptions: [{ label: "updated", value: "updated" }],
            },
            global: {
                stubs: pageStubs,
            },
        });

        await flushPromises();

        expect(listMock).toHaveBeenCalledWith(
            expect.objectContaining({
                sort_field: "created_at",
                sort_direction: "desc",
                page: 1,
                per_page: 10,
            }),
        );

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("sort", {
            sortField: "log_name",
            sortOrder: 1,
        });
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                sort_field: "log_name",
                sort_direction: "asc",
            }),
        );
    });

    it("opens and closes the details dialog from the row action menu", async () => {
        const { default: ActivityLogIndex } = await import("@/Pages/ActivityLog/Index.vue");

        const wrapper = mountPage(ActivityLogIndex, {
            props: {
                logNameOptions: [],
                eventOptions: [],
            },
            global: {
                stubs: pageStubs,
            },
        });

        await flushPromises();

        await wrapper.get('[data-test-id="row-action-details"]').trigger("click");
        await flushPromises();

        expect(wrapper.get('[data-test-id="dialog"]').text()).toContain(
            "Company updated",
        );
        expect(wrapper.get('[data-test-id="dialog"]').text()).toContain("Alice Admin");

        await wrapper.get('[data-test-id="dialog-close"]').trigger("click");
        await flushPromises();

        expect(wrapper.find('[data-test-id="dialog"]').exists()).toBe(false);
    });

    it("switches to the timeline view and opens details from a timeline item", async () => {
        const { default: ActivityLogIndex } = await import("@/Pages/ActivityLog/Index.vue");

        const wrapper = mountPage(ActivityLogIndex, {
            props: {
                logNameOptions: [],
                eventOptions: [],
            },
            global: {
                stubs: pageStubs,
            },
        });

        await flushPromises();

        expect(wrapper.find('[data-test-id="activity-view-panel-table"]').exists()).toBe(true);
        expect(wrapper.find('[data-test-id="activity-view-panel-timeline"]').exists()).toBe(false);

        await wrapper.get('[data-test-id="activity-view-tab-timeline"]').trigger("click");
        await flushPromises();

        expect(wrapper.find('[data-test-id="activity-view-panel-table"]').exists()).toBe(false);
        expect(wrapper.get('[data-test-id="activity-view-panel-timeline"]').text()).toContain(
            "Company updated",
        );
        expect(wrapper.get('[data-test-id="timeline"]').text()).toContain("Alice Admin");

        await wrapper.get('[data-test-id="timeline-details-0"]').trigger("click");
        await flushPromises();

        expect(wrapper.get('[data-test-id="dialog"]').text()).toContain("Company updated");
        expect(wrapper.get('[data-test-id="dialog"]').text()).toContain("Alice Admin");
    });
});
