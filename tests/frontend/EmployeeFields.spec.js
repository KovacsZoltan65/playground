import { describe, expect, it, vi } from "vitest";

import { mountPage } from "./helpers/pageTestUtils";

describe("EmployeeFields", () => {
    it("updates the employee form model through the shared field stubs", async () => {
        const { default: EmployeeFields } = await import(
            "@/Pages/Employee/Partials/EmployeeFields.vue"
        );

        const form = {
            company_id: null,
            name: "",
            email: "",
            active: false,
        };

        const touched = {
            company_id: vi.fn(),
            name: vi.fn(),
            email: vi.fn(),
            active: vi.fn(),
        };

        const wrapper = mountPage(EmployeeFields, {
            props: {
                form,
                errors: {},
                validation: {
                    company_id: { $touch: touched.company_id },
                    name: { $touch: touched.name },
                    email: { $touch: touched.email },
                    active: { $touch: touched.active },
                },
                companyOptions: [{ label: "Acme", value: 10 }],
            },
        });

        await wrapper.get('select[id="company_id"]').setValue("10");
        await wrapper.get('input[id="name"]').setValue("Alice");
        await wrapper.get('input[id="email"]').setValue("alice@acme.test");
        await wrapper.get('input[input-id="active"]').setValue(true);

        expect(form.company_id).toBe(10);
        expect(form.name).toBe("Alice");
        expect(form.email).toBe("alice@acme.test");
        expect(form.active).toBe(true);
        expect(touched.company_id).toHaveBeenCalled();
    });

    it("renders backend validation errors", async () => {
        const { default: EmployeeFields } = await import(
            "@/Pages/Employee/Partials/EmployeeFields.vue"
        );

        const wrapper = mountPage(EmployeeFields, {
            props: {
                form: {
                    company_id: null,
                    name: "",
                    email: "",
                    active: false,
                },
                errors: {
                    name: "Employee name is required.",
                },
                validation: null,
                companyOptions: [],
            },
        });

        expect(wrapper.text()).toContain("Employee name is required.");
    });
});

