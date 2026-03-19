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

vi.mock("@/Services/CompanyService", () => ({
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

describe("Company/Index", () => {
    beforeEach(() => {
        vi.useFakeTimers();
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    name: "Acme",
                    email: "info@acme.test",
                    phone: "123",
                    employees_count: 12,
                    is_active: true,
                    updated_at: "2026-03-18 10:00:00",
                },
            ]),
        );
        bulkActivateMock.mockResolvedValue({});
        requestConfirmationMock.mockResolvedValue(true);
    });

    it("requests sorted data for the company table", async () => {
        const { default: CompanyIndex } = await import("@/Pages/Company/Index.vue");

        const wrapper = mountPage(CompanyIndex);
        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("sort", {
            sortField: "employees_count",
            sortOrder: 1,
        });
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                sort_field: "employees_count",
                sort_direction: "asc",
            }),
        );
    });

    it("bulk activates selected companies after confirmation", async () => {
        const { default: CompanyIndex } = await import("@/Pages/Company/Index.vue");

        const wrapper = mountPage(CompanyIndex);
        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("update:selection", [
            {
                id: 1,
                name: "Acme",
            },
        ]);
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
