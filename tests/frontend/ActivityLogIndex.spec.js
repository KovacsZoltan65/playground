import { defineComponent, h } from "vue";
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
});
