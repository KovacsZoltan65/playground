<script setup>
import { computed } from "vue";
import InputText from "primevue/inputtext";
import MultiSelect from "primevue/multiselect";
import Password from "primevue/password";

const props = defineProps({
    form: { type: Object, required: true },
    errors: { type: Object, required: true },
    validation: { type: Object, default: null },
    roleOptions: { type: Array, default: () => [] },
    passwordRequired: { type: Boolean, default: false },
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

const passwordHelp = computed(() =>
    props.passwordRequired
        ? "Set the initial password for the new user account."
        : "Leave the password fields empty to keep the current password unchanged.",
);
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <div class="space-y-2">
            <label for="name" class="text-sm font-medium text-slate-700">
                {{ $t("User name") }}
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
            <small v-if="resolveFieldError('email')" class="block text-sm text-rose-500">
                {{ resolveFieldError("email") }}
            </small>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="role_ids" class="text-sm font-medium text-slate-700">
                {{ $t("Assigned roles") }}
            </label>
            <MultiSelect
                id="role_ids"
                v-model="form.role_ids"
                :options="roleOptions"
                option-label="label"
                option-value="value"
                filter
                display="chip"
                class="w-full"
                :max-selected-labels="6"
                :placeholder="$t('Select roles')"
                @change="touchField('role_ids')"
            />
            <small class="block text-sm text-slate-500">
                {{ $t("Choose which roles should be assigned to this user.") }}
            </small>
            <small v-if="resolveFieldError('role_ids')" class="block text-sm text-rose-500">
                {{ resolveFieldError("role_ids") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="password" class="text-sm font-medium text-slate-700">
                {{ $t("Password") }}
            </label>
            <Password
                id="password"
                v-model="form.password"
                class="w-full"
                fluid
                toggle-mask
                :feedback="false"
                :invalid="Boolean(resolveFieldError('password'))"
                @blur="touchField('password')"
            />
            <small class="block text-sm text-slate-500">
                {{ $t(passwordHelp) }}
            </small>
            <small v-if="resolveFieldError('password')" class="block text-sm text-rose-500">
                {{ resolveFieldError("password") }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="password_confirmation" class="text-sm font-medium text-slate-700">
                {{ $t("Confirm password") }}
            </label>
            <Password
                id="password_confirmation"
                v-model="form.password_confirmation"
                class="w-full"
                fluid
                toggle-mask
                :feedback="false"
                :invalid="Boolean(resolveFieldError('password_confirmation'))"
                @blur="touchField('password_confirmation')"
            />
            <small
                v-if="resolveFieldError('password_confirmation')"
                class="block text-sm text-rose-500"
            >
                {{ resolveFieldError("password_confirmation") }}
            </small>
        </div>
    </div>
</template>
