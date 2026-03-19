import { describe, expect, it, vi } from "vitest";

import { mountPage } from "./helpers/pageTestUtils";

describe("CompanyFields", () => {
    it("updates the company form model and touches fields on blur", async () => {
        const { default: CompanyFields } = await import(
            "@/Pages/Company/Partials/CompanyFields.vue"
        );

        const form = {
            name: "",
            email: "",
            phone: "",
            address: "",
            is_active: false,
        };

        const touched = {
            name: vi.fn(),
            email: vi.fn(),
            phone: vi.fn(),
            address: vi.fn(),
            is_active: vi.fn(),
        };

        const wrapper = mountPage(CompanyFields, {
            props: {
                form,
                errors: {},
                validation: {
                    name: { $touch: touched.name },
                    email: { $touch: touched.email },
                    phone: { $touch: touched.phone },
                    address: { $touch: touched.address },
                    is_active: { $touch: touched.is_active },
                },
            },
        });

        await wrapper.get('input[id="name"]').setValue("Acme");
        await wrapper.get('input[id="name"]').trigger("blur");
        await wrapper.get('textarea[id="address"]').setValue("Main street 1");
        await wrapper.get('input[input-id="is_active"]').setValue(true);

        expect(form.name).toBe("Acme");
        expect(form.address).toBe("Main street 1");
        expect(form.is_active).toBe(true);
        expect(touched.name).toHaveBeenCalled();
        expect(touched.is_active).toHaveBeenCalled();
    });
});

