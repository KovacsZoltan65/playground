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
const bulkDestroyMock = vi.fn();
const requestConfirmationMock = vi.fn();

vi.mock("@/Services/UserTemporaryPermissionService", () => ({
    default: {
        list: listMock,
        bulkDestroy: bulkDestroyMock,
        destroy: vi.fn(),
    },
}));

vi.mock("@/Support/confirm/requestConfirmation", () => ({
    requestConfirmation: requestConfirmationMock,
}));

vi.mock("@inertiajs/vue3", () => ({
    Head: defineComponent({
        name: "Head",
        props: { title: { type: String, default: "" } },
        setup() {
            return () => h("div");
        },
    }),
    Link: defineComponent({
        name: "Link",
        props: { href: { type: String, default: "#" } },
        setup(_, { slots }) {
            return () => h("a", {}, slots.default?.());
        },
    }),
    router: {
        get: vi.fn(),
    },
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

describe("UserTemporaryPermission/Index", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    user_name: "Alice Admin",
                    permission_name: "employees.update",
                    status: "active",
                    starts_at: "2026-03-18 08:00:00",
                    ends_at: "2026-03-25 18:00:00",
                    updated_at: "2026-03-18 08:30:00",
                    reason: "Backfill",
                },
            ]),
        );
        bulkDestroyMock.mockResolvedValue({ deleted: 1 });
        requestConfirmationMock.mockResolvedValue(true);
    });

    afterEach(() => {
        vi.useRealTimers();
    });

    it("debounces keyword search and forwards the query to the list service", async () => {
        const { default: UserTemporaryPermissionIndex } = await import(
            "@/Pages/UserTemporaryPermission/Index.vue"
        );

        const wrapper = mountPage(UserTemporaryPermissionIndex, {
            props: {
                userOptions: [{ label: "Alice Admin", value: 1 }],
                permissionOptions: [{ label: "employees.update", value: 11 }],
            },
        });

        await flushPromises();

        await wrapper.get('input[placeholder="Keyword Search"]').setValue("backfill");
        await vi.advanceTimersByTimeAsync(350);
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                search: "backfill",
                sort_field: "starts_at",
                sort_direction: "desc",
            }),
        );
    });

    it("bulk deletes the selected assignments after confirmation", async () => {
        const { default: UserTemporaryPermissionIndex } = await import(
            "@/Pages/UserTemporaryPermission/Index.vue"
        );

        const wrapper = mountPage(UserTemporaryPermissionIndex, {
            props: {
                userOptions: [{ label: "Alice Admin", value: 1 }],
                permissionOptions: [{ label: "employees.update", value: 11 }],
            },
        });

        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("update:selection", [
            {
                id: 1,
                user_name: "Alice Admin",
                permission_name: "employees.update",
            },
        ]);
        await flushPromises();

        const deleteSelectedButton = wrapper
            .findAll("button")
            .find((button) => button.text() === "Delete selected");

        await deleteSelectedButton.trigger("click");
        await flushPromises();

        expect(requestConfirmationMock).toHaveBeenCalledTimes(1);
        expect(bulkDestroyMock).toHaveBeenCalledWith([1]);
        expect(listMock).toHaveBeenCalledTimes(2);
    });
});
