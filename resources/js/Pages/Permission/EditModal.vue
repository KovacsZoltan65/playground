<script setup>
import PermissionFields from "@/Pages/Permission/Partials/PermissionFields.vue";
import permissionService from "@/Services/PermissionService";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import { showErrorToast } from "@/Support/toast/toastHelpers";
import permissionValidationSchema from "@/Validation/schemas/permission.json";
import useVuelidate from "@vuelidate/core";
import { computed, reactive, ref, watch } from "vue";
import { trans } from "laravel-vue-i18n";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    permission: { type: Object, default: null },
    guardOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:modelValue", "saved"]);

const open = computed({
    get: () => props.modelValue,
    set: (value) => emit("update:modelValue", value),
});

const toast = useToast();
const processing = ref(false);
const form = reactive({
    id: null,
    name: "",
    guard_name: props.guardOptions[0]?.value ?? "web",
});
const errors = reactive({});
const rules = computed(() =>
    buildVuelidateRules(permissionValidationSchema, {
        translator: trans,
    }),
);
const v$ = useVuelidate(rules, form, { $autoDirty: true });

const resetErrors = () => {
    Object.keys(errors).forEach((key) => delete errors[key]);
};

const hydrateFromPermission = () => {
    resetErrors();

    form.id = props.permission?.id ?? null;
    form.name = props.permission?.name ?? "";
    form.guard_name = props.permission?.guard_name ?? props.guardOptions[0]?.value ?? "web";
    v$.value.$reset();
};

watch(
    () => open.value,
    (value) => {
        if (value) {
            hydrateFromPermission();
        }
    },
);

watch(
    () => props.permission,
    () => {
        if (open.value) {
            hydrateFromPermission();
        }
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
        const response = await permissionService.update(form.id, {
            name: form.name,
            guard_name: form.guard_name,
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
        :header="$t('Edit Permission')"
        :style="{ width: '48rem' }"
        :closable="!processing"
        :dismissable-mask="!processing"
        :draggable="false"
    >
        <div v-if="!permission" class="text-sm text-slate-500">
            {{ $t("No permission selected.") }}
        </div>

        <PermissionFields
            v-else
            :form="form"
            :errors="errors"
            :validation="v$"
            :guard-options="guardOptions"
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
                    :disabled="processing || !permission"
                    @click="submit"
                />
            </div>
        </template>
    </Dialog>
</template>
