import { describe, expect, it } from "vitest";

import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import companyValidationSchema from "@/Validation/schemas/company.json";

const translator = (key, replacements = {}) =>
    key.replace(":max", replacements.max ?? ":max");

describe("buildVuelidateRules", () => {
    it("builds required and max length validators from the shared schema", async () => {
        const rules = buildVuelidateRules(companyValidationSchema, {
            translator,
        });

        expect(await rules.name.required.$validator("")).toBe(false);
        expect(await rules.name.required.$validator("Acme")).toBe(true);
        expect(await rules.name.maxLength.$validator("A".repeat(256))).toBe(
            false,
        );
    });

    it("builds email validators from the shared schema", async () => {
        const rules = buildVuelidateRules(companyValidationSchema, {
            translator,
        });

        expect(await rules.email.email.$validator("invalid-email")).toBe(false);
        expect(await rules.email.email.$validator("hello@example.com")).toBe(
            true,
        );
    });

    it("builds boolean presence validators from the shared schema", async () => {
        const rules = buildVuelidateRules(companyValidationSchema, {
            translator,
        });

        expect(await rules.is_active.boolean.$validator(true)).toBe(true);
        expect(await rules.is_active.boolean.$validator(false)).toBe(true);
        expect(await rules.is_active.boolean.$validator(null)).toBe(false);
    });
});
