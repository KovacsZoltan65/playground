import { defineComponent, h } from "vue";
import { beforeEach, describe, expect, it, vi } from "vitest";

import {
    defaultListResponse,
    flushPromises,
    installBrowserGlobals,
    mountPage,
    resetBrowserState,
    routerMock,
} from "./helpers/pageTestUtils";

const listMock = vi.fn();
const destroyMock = vi.fn();

vi.mock("@/Services/SidebarTipPageService", () => ({
    default: {
        list: listMock,
        destroy: destroyMock,
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

describe("UsageTips/Index", () => {
    beforeEach(() => {
        vi.resetModules();
        resetBrowserState();
        installBrowserGlobals();
        window.confirm = vi.fn(() => true);

        listMock.mockResolvedValue(
            defaultListResponse([
                {
                    id: 1,
                    page_component: "Company/Index",
                    page_label_key: "Companies",
                    is_visible: true,
                    rotation_interval_seconds: 30,
                    tips_count: 3,
                    active_tips_count: 2,
                },
            ]),
        );
        destroyMock.mockResolvedValue({});
    });

    it("loads the usage tips list with the search query", async () => {
        const { default: UsageTipsIndex } = await import("@/Pages/UsageTips/Index.vue");
        const wrapper = mountPage(UsageTipsIndex);
        await flushPromises();

        await wrapper.get('input[placeholder="Search usage tips"]').setValue("company");

        const searchButton = wrapper
            .findAll("button")
            .find((button) => button.text() === "Search");

        await searchButton.trigger("click");
        await flushPromises();

        expect(listMock).toHaveBeenLastCalledWith({
            search: "company",
            per_page: 10,
        });
    });

    it("deletes a usage tips page from the row action menu", async () => {
        const { default: UsageTipsIndex } = await import("@/Pages/UsageTips/Index.vue");
        const wrapper = mountPage(UsageTipsIndex);
        await flushPromises();

        await wrapper.get('[data-test-id="row-action-delete"]').trigger("click");
        await flushPromises();

        expect(window.confirm).toHaveBeenCalledTimes(1);
        expect(destroyMock).toHaveBeenCalledWith(1);
        expect(listMock).toHaveBeenCalledTimes(2);
    });
});

