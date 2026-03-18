<script setup>
import { computed } from "vue";
import InputText from "primevue/inputtext";
import MultiSelect from "primevue/multiselect";
import Select from "primevue/select";

const props = defineProps({
    form: { type: Object, required: true },
    errors: { type: Object, required: true },
    validation: { type: Object, default: null },
    guardOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
    permissionOptionsByGuard: { type: Object, default: () => ({}) },
    disabled: { type: Boolean, default: false },
});

const resolvedPermissionOptions = computed(() => {
    const guardOptions = props.permissionOptionsByGuard?.[props.form.guard_name];

    if (Array.isArray(guardOptions)) {
        return guardOptions;
    }

    return props.permissionOptions;
});

const groupedPermissionOptions = computed(() => {
    const groups = new Map();

    resolvedPermissionOptions.value.forEach((option) => {
        const group = option.group || "Other";

        if (!groups.has(group)) {
            groups.set(group, []);
        }

        groups.get(group).push(option);
    });

    return Array.from(groups.entries()).map(([label, items]) => ({
        label,
        items,
    }));
});

function resolveValidationField(field) {
    if (!props.validation) {
        return null;
    }

    return props.validation.value?.[field] ?? props.validation[field] ?? null;
}

function resolveFieldError(field) {
    const backendError = props.errors[field];

    if (Array.isArray(backendError)) {
        return backendError[0] ?? null;
    }

    if (typeof backendError === "string" && backendError.length > 0) {
        return backendError;
    }

    const validationField = resolveValidationField(field);

    if (!validationField?.$error) {
        return null;
    }

    return validationField.$errors[0]?.$message ?? null;
}

function touchField(field) {
    resolveValidationField(field)?.$touch?.();
}
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <div class="space-y-2">
            <label for="name" class="text-sm font-medium text-slate-700">
                {{ $t("Role name") }}
            </label>
            <InputText
                id="name"
                v-model="form.name"
                class="w-full"
                :disabled="disabled"
                :invalid="Boolean(resolveFieldError('name'))"
                @blur="touchField('name')"
            />
            <small v-if="resolveFieldError('name')" class="block text-sm text-rose-500">
                {{ resolveFieldError("name") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="guard_name" class="text-sm font-medium text-slate-700">
                {{ $t("Guard") }}
            </label>
            <Select
                id="guard_name"
                v-model="form.guard_name"
                :options="guardOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                :disabled="disabled"
                :invalid="Boolean(resolveFieldError('guard_name'))"
                @change="touchField('guard_name')"
            />
            <small
                v-if="resolveFieldError('guard_name')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("guard_name") }}
            </small>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="permission_ids" class="text-sm font-medium text-slate-700">
                {{ $t("Assigned permissions") }}
            </label>
            <MultiSelect
                id="permission_ids"
                v-model="form.permission_ids"
                :options="groupedPermissionOptions"
                option-group-label="label"
                option-group-children="items"
                option-label="label"
                option-value="value"
                filter
                display="chip"
                class="w-full"
                :disabled="disabled"
                :max-selected-labels="6"
                :placeholder="$t('Select permissions')"
                @change="touchField('permission_ids')"
            />
            <small class="block text-sm text-slate-500">
                {{ $t("Choose which permissions belong to this role.") }}
            </small>
            <small
                v-if="resolveFieldError('permission_ids')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("permission_ids") }}
            </small>
        </div>
    </div>
</template>
