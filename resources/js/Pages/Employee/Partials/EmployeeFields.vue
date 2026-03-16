<script setup>
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import Select from "primevue/select";

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
    validation: {
        type: Object,
        default: null,
    },
    companyOptions: {
        type: Array,
        default: () => [],
    },
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
        <div class="space-y-2 md:col-span-2">
            <label for="company_id" class="text-sm font-medium text-slate-700">
                {{ $t("Company") }}
            </label>
            <Select
                id="company_id"
                v-model="form.company_id"
                :options="companyOptions"
                option-label="label"
                option-value="value"
                class="w-full"
                :invalid="Boolean(resolveFieldError('company_id'))"
                @change="touchField('company_id')"
            />
            <small
                v-if="resolveFieldError('company_id')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("company_id") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="name" class="text-sm font-medium text-slate-700">
                {{ $t("Employee name") }}
            </label>
            <InputText
                id="name"
                v-model="form.name"
                class="w-full"
                :invalid="Boolean(resolveFieldError('name'))"
                @blur="touchField('name')"
            />
            <small
                v-if="resolveFieldError('name')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("name") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="email" class="text-sm font-medium text-slate-700">
                {{ $t("Email") }}
            </label>
            <InputText
                id="email"
                v-model="form.email"
                class="w-full"
                :invalid="Boolean(resolveFieldError('email'))"
                @blur="touchField('email')"
            />
            <small
                v-if="resolveFieldError('email')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("email") }}
            </small>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 text-sm text-slate-700">
                <Checkbox
                    v-model="form.active"
                    binary
                    input-id="active"
                    @change="touchField('active')"
                />
                <span>{{ $t("Active employee") }}</span>
            </label>
            <small
                v-if="resolveFieldError('active')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("active") }}
            </small>
        </div>
    </div>
</template>
