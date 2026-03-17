<script setup>
import InputText from "primevue/inputtext";
import Select from "primevue/select";
import Textarea from "primevue/textarea";

const props = defineProps({
    form: { type: Object, required: true },
    errors: { type: Object, required: true },
    validation: { type: Object, default: null },
    userOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
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
            <label for="user_id" class="text-sm font-medium text-slate-700">
                {{ $t("User") }}
            </label>
            <Select
                id="user_id"
                v-model="form.user_id"
                :options="userOptions"
                option-label="label"
                option-value="value"
                filter
                class="w-full"
                :invalid="Boolean(resolveFieldError('user_id'))"
                @change="touchField('user_id')"
            />
            <small v-if="resolveFieldError('user_id')" class="block text-sm text-rose-500">
                {{ resolveFieldError("user_id") }}
            </small>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="permission_id" class="text-sm font-medium text-slate-700">
                {{ $t("Permission") }}
            </label>
            <Select
                id="permission_id"
                v-model="form.permission_id"
                :options="permissionOptions"
                option-label="label"
                option-value="value"
                filter
                class="w-full"
                :invalid="Boolean(resolveFieldError('permission_id'))"
                @change="touchField('permission_id')"
            />
            <small
                v-if="resolveFieldError('permission_id')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("permission_id") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="starts_at" class="text-sm font-medium text-slate-700">
                {{ $t("Starts at") }}
            </label>
            <InputText
                id="starts_at"
                v-model="form.starts_at"
                type="datetime-local"
                class="w-full"
                :invalid="Boolean(resolveFieldError('starts_at'))"
                @blur="touchField('starts_at')"
            />
            <small
                v-if="resolveFieldError('starts_at')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("starts_at") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="ends_at" class="text-sm font-medium text-slate-700">
                {{ $t("Ends at") }}
            </label>
            <InputText
                id="ends_at"
                v-model="form.ends_at"
                type="datetime-local"
                class="w-full"
                :invalid="Boolean(resolveFieldError('ends_at'))"
                @blur="touchField('ends_at')"
            />
            <small v-if="resolveFieldError('ends_at')" class="block text-sm text-rose-500">
                {{ resolveFieldError("ends_at") }}
            </small>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="reason" class="text-sm font-medium text-slate-700">
                {{ $t("Reason") }}
            </label>
            <Textarea
                id="reason"
                v-model="form.reason"
                rows="4"
                auto-resize
                class="w-full"
                :invalid="Boolean(resolveFieldError('reason'))"
                @blur="touchField('reason')"
            />
            <small class="block text-sm text-slate-500">
                {{ $t("Optional note explaining why the permission is granted temporarily.") }}
            </small>
            <small v-if="resolveFieldError('reason')" class="block text-sm text-rose-500">
                {{ resolveFieldError("reason") }}
            </small>
        </div>
    </div>
</template>
