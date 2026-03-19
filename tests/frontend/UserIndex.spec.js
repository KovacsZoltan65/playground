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
const sendVerificationEmailMock = vi.fn();

vi.mock("@/Services/UserService", () => ({
    default: {
        list: listMock,
        destroy: vi.fn(),
        bulkDestroy: vi.fn(),
        sendVerificationEmail: sendVerificationEmailMock,
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

describe("User/Index", () => {
    beforeEach(() => {
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    name: "Alice Admin",
                    email: "alice@example.com",
                    roles_count: 2,
                    role_names: ["Admin", "Manager"],
                    email_verified_at: "2026-03-18 10:00:00",
                    updated_at: "2026-03-18 10:00:00",
                },
            ]),
        );
        sendVerificationEmailMock.mockResolvedValue({
            message: "Verification email sent.",
        });
    });

    it("updates the sort parameters for the user list", async () => {
        const { default: UserIndex } = await import("@/Pages/User/Index.vue");

        const wrapper = mountPage(UserIndex, {
            props: {
                roleOptions: [{ label: "Admin", value: 1 }],
            },
        });

        await flushPromises();

        const dataTable = wrapper.findComponent({ name: "DataTable" });
        dataTable.vm.$emit("sort", {
            sortField: "roles_count",
            sortOrder: 1,
        });
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith(
            expect.objectContaining({
                sort_field: "roles_count",
                sort_direction: "asc",
            }),
        );
    });

    it("sends the verification email from the row action menu", async () => {
        const { default: UserIndex } = await import("@/Pages/User/Index.vue");

        const wrapper = mountPage(UserIndex, {
            props: {
                roleOptions: [{ label: "Admin", value: 1 }],
            },
        });

        await flushPromises();

        await wrapper.get('[data-test-id="row-action-resend-verification-email"]').trigger("click");
        await flushPromises();

        expect(sendVerificationEmailMock).toHaveBeenCalledWith(1);
    });
});
