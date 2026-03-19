import { defineComponent, h } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";

import {
    defaultListResponse,
    flushPromises,
    installBrowserGlobals,
    mountPage,
    resetBrowserState,
    toastAddMock,
} from "./helpers/pageTestUtils";

const listMock = vi.fn();
const showMock = vi.fn();

vi.mock("@/Services/PermissionService", () => ({
    default: {
        list: listMock,
        show: showMock,
        destroy: vi.fn(),
        bulkDestroy: vi.fn(),
    },
}));

vi.mock("@/Support/confirm/requestConfirmation", () => ({
    requestConfirmation: vi.fn(),
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

vi.mock("primevue/useconfirm", () => ({
    useConfirm: () => ({
        require: vi.fn(),
    }),
}));

vi.mock("@/Support/dates/formatDate", () => ({
    formatDateTime: (value) => value ?? "N/A",
}));

describe("Permission/Index", () => {
    beforeEach(() => {
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    name: "reports.view",
                    guard_name: "web",
                    roles_count: 3,
                    updated_at: "2026-03-18 10:00:00",
                },
            ]),
        );
        showMock.mockResolvedValue({
            data: {
                id: 1,
                name: "reports.view",
                guard_name: "web",
            },
        });
    });

    it("applies the guard filter through the shared DataTable filter flow", async () => {
        const { default: PermissionIndex } = await import("@/Pages/Permission/Index.vue");

        const wrapper = mountPage(PermissionIndex, {
            props: {
                guardOptions: [{ label: "web", value: "web" }],
            },
        });

        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        const nextFilters = {
            global: { value: null, matchMode: "contains" },
            name: { value: null, matchMode: "contains" },
            guard_name: { value: "web", matchMode: "equals" },
        };
        dataTable.vm.$emit("update:filters", nextFilters);
        dataTable.vm.$emit("filter", { filters: nextFilters });
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                guard_name: "web",
            }),
        );
    }, 10000);

    it("loads a permission before opening the edit modal", async () => {
        const { default: PermissionIndex } = await import("@/Pages/Permission/Index.vue");

        const wrapper = mountPage(PermissionIndex, {
            props: {
                guardOptions: [{ label: "web", value: "web" }],
            },
        });

        await flushPromises();

        await wrapper.get('[data-test-id="row-action-edit"]').trigger("click");
        await flushPromises();

        expect(showMock).toHaveBeenCalledWith(1);
    });
});
