import { defineComponent, h } from "vue";
import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";

import UserTemporaryPermissionFields from "@/Pages/UserTemporaryPermission/Partials/UserTemporaryPermissionFields.vue";

const SelectStub = defineComponent({
    name: "SelectStub",
    props: {
        id: { type: String, default: null },
        modelValue: { type: [String, Number, null], default: null },
        options: { type: Array, default: () => [] },
        optionLabel: { type: String, default: "label" },
        optionValue: { type: String, default: "value" },
        optionDisabled: { type: [String, Function], default: null },
    },
    emits: ["update:modelValue", "change"],
    setup(props, { emit }) {
        const isDisabled = (option) => {
            if (typeof props.optionDisabled === "function") {
                return props.optionDisabled(option);
            }

            if (typeof props.optionDisabled === "string" && props.optionDisabled.length > 0) {
                return Boolean(option?.[props.optionDisabled]);
            }

            return false;
        };

        return () =>
            h(
                "select",
                {
                    "data-test-id": props.id,
                    value: props.modelValue ?? "",
                    onChange: (event) => {
                        const nextValue = event.target.value === "" ? null : Number(event.target.value);
                        emit("update:modelValue", nextValue);
                        emit("change");
                    },
                },
                [
                    h("option", { value: "" }, "empty"),
                    ...props.options.map((option) =>
                        h(
                            "option",
                            {
                                key: option[props.optionValue],
                                value: option[props.optionValue],
                                disabled: isDisabled(option),
                            },
                            option[props.optionLabel]
                        )
                    ),
                ]
            );
    },
});

const InputTextStub = defineComponent({
    name: "InputTextStub",
    props: {
        id: { type: String, default: null },
        modelValue: { type: String, default: "" },
    },
    emits: ["update:modelValue", "blur"],
    setup(props, { emit }) {
        return () =>
            h("input", {
                "data-test-id": props.id,
                value: props.modelValue,
                onInput: (event) => emit("update:modelValue", event.target.value),
                onBlur: () => emit("blur"),
            });
    },
});

const TextareaStub = defineComponent({
    name: "TextareaStub",
    props: {
        id: { type: String, default: null },
        modelValue: { type: String, default: "" },
    },
    emits: ["update:modelValue", "blur"],
    setup(props, { emit }) {
        return () =>
            h("textarea", {
                "data-test-id": props.id,
                value: props.modelValue,
                onInput: (event) => emit("update:modelValue", event.target.value),
                onBlur: () => emit("blur"),
            });
    },
});

function mountFields(props = {}) {
    return mount(UserTemporaryPermissionFields, {
        props: {
            form: {
                user_id: null,
                permission_id: null,
                starts_at: "",
                ends_at: "",
                reason: "",
            },
            errors: {},
            validation: null,
            userOptions: [
                { value: 1, label: "Alice" },
                { value: 2, label: "Bob" },
            ],
            permissionOptions: [
                { value: 11, label: "employees.update" },
                { value: 12, label: "employees.delete" },
            ],
            userEffectivePermissionIds: {
                1: [11],
                2: [],
            },
            ...props,
        },
        global: {
            stubs: {
                Select: SelectStub,
                InputText: InputTextStub,
                Textarea: TextareaStub,
            },
            mocks: {
                $t: (value) => value,
            },
        },
    });
}

describe("UserTemporaryPermissionFields", () => {
    it("disables permissions already assigned to the selected user", async () => {
        const wrapper = mountFields();

        await wrapper.get('[data-test-id="user_id"]').setValue("1");

        const options = wrapper.get('[data-test-id="permission_id"]').findAll("option");

        expect(options[1].attributes("disabled")).toBeDefined();
        expect(options[2].attributes("disabled")).toBeUndefined();
    });

    it("clears the selected permission when changing to a user who already has it", async () => {
        const form = {
            user_id: 2,
            permission_id: 11,
            starts_at: "",
            ends_at: "",
            reason: "",
        };

        const wrapper = mountFields({ form });

        await wrapper.get('[data-test-id="user_id"]').setValue("1");

        expect(form.permission_id).toBeNull();
    });
});
