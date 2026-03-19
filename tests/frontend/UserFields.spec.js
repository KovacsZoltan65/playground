import { describe, expect, it } from "vitest";

import { mountPage } from "./helpers/pageTestUtils";

describe("UserFields", () => {
    it("switches the password help text based on passwordRequired", async () => {
        const { default: UserFields } = await import("@/Pages/User/Partials/UserFields.vue");

        const requiredWrapper = mountPage(UserFields, {
            props: {
                form: {
                    name: "",
                    email: "",
                    role_ids: [],
                    password: "",
                    password_confirmation: "",
                },
                errors: {},
                validation: null,
                roleOptions: [],
                passwordRequired: true,
            },
        });

        expect(requiredWrapper.text()).toContain(
            "Set the initial password for the new user account.",
        );

        const optionalWrapper = mountPage(UserFields, {
            props: {
                form: {
                    name: "",
                    email: "",
                    role_ids: [],
                    password: "",
                    password_confirmation: "",
                },
                errors: {},
                validation: null,
                roleOptions: [],
                passwordRequired: false,
            },
        });

        expect(optionalWrapper.text()).toContain(
            "Leave the password fields empty to keep the current password unchanged.",
        );
    });
});

