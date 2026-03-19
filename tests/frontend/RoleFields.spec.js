import { describe, expect, it } from "vitest";

import { mountPage } from "./helpers/pageTestUtils";

describe("RoleFields", () => {
    it("uses guard-specific permission options when available", async () => {
        const { default: RoleFields } = await import("@/Pages/Role/Partials/RoleFields.vue");

        const wrapper = mountPage(RoleFields, {
            props: {
                form: {
                    name: "Manager",
                    guard_name: "web",
                    permission_ids: [],
                },
                errors: {},
                validation: null,
                guardOptions: [{ label: "web", value: "web" }],
                permissionOptions: [{ label: "fallback.permission", value: 1 }],
                permissionOptionsByGuard: {
                    web: [
                        { label: "reports.view", value: 2, group: "Reports" },
                    ],
                },
                disabled: false,
            },
        });

        expect(wrapper.find('select[id="permission_ids"]').exists()).toBe(true);
        expect(wrapper.text()).toContain("Choose which permissions belong to this role.");
    });
});
