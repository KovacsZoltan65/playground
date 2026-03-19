import { defineComponent, h } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";

import {
    defaultListResponse,
    flushPromises,
    installBrowserGlobals,
    mountPage,
    resetBrowserState,
    routerMock,
    toastAddMock,
} from "./helpers/pageTestUtils";

const listMock = vi.fn();
const bulkActivateMock = vi.fn();
const requestConfirmationMock = vi.fn();

vi.mock("@/Services/EmployeeService", () => ({
    default: {
        list: listMock,
        bulkActivate: bulkActivateMock,
        bulkDeactivate: vi.fn(),
        bulkDestroy: vi.fn(),
        destroy: vi.fn(),
        toggleActiveStatus: vi.fn(),
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
    router: routerMock,
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
    useToast: () => ({ add: toastAddMock }),
}));

vi.mock("primevue/useconfirm", () => ({
    useConfirm: () => ({ require: vi.fn() }),
}));

vi.mock("@/Support/dates/formatDate", () => ({
    formatDateTime: (value) => value ?? "N/A",
}));

describe("Employee/Index", () => {
    beforeEach(() => {
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();
        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    company_id: 10,
                    company_name: "Acme",
                    name: "Alice",
                    email: "alice@acme.test",
                    active: true,
                    updated_at: "2026-03-18 10:00:00",
                },
            ]),
        );
        bulkActivateMock.mockResolvedValue({});
        requestConfirmationMock.mockResolvedValue(true);
    });

    it("updates the employee list sort parameters", async () => {
        const { default: EmployeeIndex } = await import("@/Pages/Employee/Index.vue");
        const wrapper = mountPage(EmployeeIndex, {
            props: {
                companyOptions: [{ label: "Acme", value: 10 }],
            },
        });

        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("sort", { sortField: "active", sortOrder: -1 });
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                sort_field: "active",
                sort_direction: "desc",
            }),
        );
    });

    it("bulk activates selected employees after confirmation", async () => {
        const { default: EmployeeIndex } = await import("@/Pages/Employee/Index.vue");
        const wrapper = mountPage(EmployeeIndex, {
            props: {
                companyOptions: [{ label: "Acme", value: 10 }],
            },
        });

        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("update:selection", [{ id: 1, name: "Alice" }]);
        await flushPromises();

        const activateButton = wrapper
            .findAll("button")
            .find((button) => button.text() === "Activate selected");

        await activateButton.trigger("click");
        await flushPromises();

        expect(requestConfirmationMock).toHaveBeenCalledTimes(1);
        expect(bulkActivateMock).toHaveBeenCalledWith([1]);
    });
});

