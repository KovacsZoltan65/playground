<script setup>
import RoleFields from "@/Pages/Role/Partials/RoleFields.vue";
import roleService from "@/Services/RoleService";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import { showErrorToast } from "@/Support/toast/toastHelpers";
import roleValidationSchema from "@/Validation/schemas/role.json";
import useVuelidate from "@vuelidate/core";
import { computed, reactive, ref, watch } from "vue";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    role: { type: Object, default: null },
    guardOptions: { type: Array, default: () => [] },
    permissionOptionsByGuard: { type: Object, default: () => ({}) },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const open = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const defaultGuard = computed(() => props.guardOptions[0]?.value ?? "web");
const toast = useToast();
const processing = ref(false);
const form = reactive({
    id: null,
    name: "",
    guard_name: defaultGuard.value,
    permission_ids: [],
});
const errors = reactive({});
const rules = computed(() =>
    buildVuelidateRules(roleValidationSchema, {
        translator: trans,
    }),
);
const v$ = useVuelidate(rules, form, { $autoDirty: true });

const resetErrors = () => {
    Object.keys(errors).forEach((key) => delete errors[key]);
};

const syncPermissionIdsToGuard = () => {
    const allowedIds = new Set(
        (props.permissionOptionsByGuard[form.guard_name] ?? []).map((option) => option.value),
    );

    form.permission_ids = form.permission_ids.filter((id) => allowedIds.has(id));
};

const hydrateFromRole = () => {
    resetErrors();

    form.id = props.role?.id ?? null;
    form.name = props.role?.name ?? "";
    form.guard_name = props.role?.guard_name ?? defaultGuard.value;
    form.permission_ids = props.role?.permission_ids ?? [];
    syncPermissionIdsToGuard();
    v$.value.$reset();
};

watch(
    () => open.value,
    (value) => {
        if (value) {
            hydrateFromRole();
        }
    },
);

watch(
    () => props.role,
    () => {
        if (open.value) {
            hydrateFromRole();
        }
    },
);

watch(
    () => form.guard_name,
    () => {
        syncPermissionIdsToGuard();
    },
);

const close = () => {
    if (!processing.value) {
        open.value = false;
    }
};

const submit = async () => {
    if (!form.id) {
        return;
    }

    processing.value = true;
    resetErrors();

    const isValid = await v$.value.$validate();

    if (!isValid) {
        processing.value = false;
        return;
    }

    try {
        const response = await roleService.update(form.id, {
            name: form.name,
            guard_name: form.guard_name,
            permission_ids: form.permission_ids,
        });
        emit("saved", response.message);
        close();
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(errors, error.response.data.errors);
        } else {
            showErrorToast(toast, error?.response?.data?.message);
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <Dialog
        v-model:visible="open"
        modal
        :header="$t('Edit Role')"
        :style="{ width: '52rem' }"
        :closable="!processing"
        :dismissable-mask="!processing"
        :draggable="false"
    >
        <div v-if="!role" class="text-sm text-slate-500">
            {{ $t("No role selected.") }}
        </div>

        <RoleFields
            v-else
            :form="form"
            :errors="errors"
            :validation="v$"
            :guard-options="guardOptions"
            :permission-options-by-guard="permissionOptionsByGuard"
            :disabled="processing"
        />

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button
                    :label="$t('Cancel')"
                    severity="secondary"
                    :disabled="processing"
                    @click="close"
                />
                <Button
                    :label="$t('Save changes')"
                    icon="pi pi-check"
                    :loading="processing"
                    :disabled="processing || !role"
                    @click="submit"
                />
            </div>
        </template>
    </Dialog>
</template>
