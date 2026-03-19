import { describe, expect, it } from "vitest";

import { mountPage } from "./helpers/pageTestUtils";

describe("PermissionFields", () => {
    it("updates the permission form and respects the disabled state", async () => {
        const { default: PermissionFields } = await import(
            "@/Pages/Permission/Partials/PermissionFields.vue"
        );

        const form = {
            name: "",
            guard_name: "web",
        };

        const wrapper = mountPage(PermissionFields, {
            props: {
                form,
                errors: {},
                validation: null,
                guardOptions: [{ label: "web", value: "web" }],
                disabled: false,
            },
        });

        await wrapper.get('input[id="name"]').setValue("reports.view");
        expect(form.name).toBe("reports.view");
    });
});

