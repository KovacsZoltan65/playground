import { describe, expect, it } from "vitest";

import { mountPage } from "./helpers/pageTestUtils";

describe("UsageTipPageFields", () => {
    it("adds and removes ideas while keeping the form model in sync", async () => {
        const { default: UsageTipPageFields } = await import(
            "@/Pages/UsageTips/Partials/UsageTipPageFields.vue"
        );

        const form = {
            page_component: "Company/Index",
            rotation_interval_seconds: 30,
            is_visible: true,
            tips: [],
        };

        const wrapper = mountPage(UsageTipPageFields, {
            props: {
                form,
                errors: {},
                pageTargets: [
                    { component: "Company/Index", label_key: "Companies" },
                    { component: "User/Index", label_key: "Users" },
                ],
            },
        });

        const addButton = wrapper
            .findAll("button")
            .find((button) => button.text() === "Add idea");

        await addButton.trigger("click");
        expect(form.tips).toHaveLength(1);
        expect(form.tips[0].sort_order).toBe(1);

        await wrapper.get("textarea").setValue("First idea");
        expect(form.tips[0].content).toBe("First idea");

        const removeButton = wrapper
            .findAll("button")
            .find((button) => button.text() === "Remove idea");

        await removeButton.trigger("click");
        expect(form.tips).toHaveLength(0);
    });
});

