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
const bulkDeactivateMock = vi.fn();
const toggleActiveStatusMock = vi.fn();
const requestConfirmationMock = vi.fn();

vi.mock("@/Services/CompanyService", () => ({
    default: {
        list: listMock,
        bulkActivate: bulkActivateMock,
        bulkDeactivate: bulkDeactivateMock,
        bulkDestroy: vi.fn(),
        destroy: vi.fn(),
        toggleActiveStatus: toggleActiveStatusMock,
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
        bulkDeactivateMock.mockResolvedValue({ data: [] });
        toggleActiveStatusMock.mockResolvedValue({
            data: {
                id: 1,
                name: "Acme",
                email: "info@acme.test",
                phone: "123",
                employees_count: 12,
                is_active: false,
                updated_at: "2026-03-18 11:00:00",
            },
        });
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
                include_employee_count: true,
                sort_field: "employees_count",
                sort_direction: "asc",
            }),
        );
    });

    it("debounces column filters before reloading the company list", async () => {
        const { default: CompanyIndex } = await import("@/Pages/Company/Index.vue");

        const wrapper = mountPage(CompanyIndex);
        await flushPromises();

        const nameFilterInput = wrapper.find('[data-test-id="filter-name"] input');

        await nameFilterInput.setValue("A");
        await nameFilterInput.setValue("Ac");
        await flushPromises();

        expect(listMock).toHaveBeenCalledTimes(1);

        vi.advanceTimersByTime(349);
        await flushPromises();

        expect(listMock).toHaveBeenCalledTimes(1);

        vi.advanceTimersByTime(1);
        await flushPromises();

        expect(listMock).toHaveBeenCalledTimes(2);
        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                name: "Ac",
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

    it("updates the visible row locally after toggling the company status", async () => {
        const { default: CompanyIndex } = await import("@/Pages/Company/Index.vue");

        const wrapper = mountPage(CompanyIndex);
        await flushPromises();

        const toggleButton = wrapper.find('[data-test-id="row-action-deactivate"]');

        await toggleButton.trigger("click");
        await flushPromises();

        expect(toggleActiveStatusMock).toHaveBeenCalledWith(1);
        expect(listMock).toHaveBeenCalledTimes(1);
        expect(wrapper.text()).toContain("Inactive");
    });
});
