<script setup>
import InputText from "primevue/inputtext";
import Select from "primevue/select";

const props = defineProps({
    form: { type: Object, required: true },
    errors: { type: Object, required: true },
    validation: { type: Object, default: null },
    guardOptions: { type: Array, default: () => [] },
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
                {{ $t("Permission name") }}
            </label>
            <InputText
                id="name"
                v-model="form.name"
                class="w-full"
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
    </div>
</template>
