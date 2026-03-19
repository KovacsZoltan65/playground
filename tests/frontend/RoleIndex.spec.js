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

vi.mock("@/Services/RoleService", () => ({
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

describe("Role/Index", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    name: "Manager",
                    guard_name: "web",
                    permissions_count: 5,
                    permission_names: ["reports.view"],
                    updated_at: "2026-03-18 10:00:00",
                },
            ]),
        );
        showMock.mockResolvedValue({
            data: {
                id: 1,
                name: "Manager",
                guard_name: "web",
            },
        });
    });

    it("debounces keyword search for the role list", async () => {
        const { default: RoleIndex } = await import("@/Pages/Role/Index.vue");

        const wrapper = mountPage(RoleIndex, {
            props: {
                guardOptions: [{ label: "web", value: "web" }],
                permissionOptionsByGuard: { web: [] },
            },
        });

        await flushPromises();

        await wrapper.get('input[placeholder="Keyword Search"]').setValue("manager");
        await vi.advanceTimersByTimeAsync(350);
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                search: "manager",
            }),
        );
    });

    it("loads role details before opening the edit modal", async () => {
        const { default: RoleIndex } = await import("@/Pages/Role/Index.vue");

        const wrapper = mountPage(RoleIndex, {
            props: {
                guardOptions: [{ label: "web", value: "web" }],
                permissionOptionsByGuard: { web: [] },
            },
        });

        await flushPromises();

        await wrapper.get('[data-test-id="row-action-edit"]').trigger("click");
        await flushPromises();

        expect(showMock).toHaveBeenCalledWith(1);
    });
});
