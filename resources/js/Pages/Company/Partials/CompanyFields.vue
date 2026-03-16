<script setup>
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';
import Textarea from 'primevue/textarea';

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

    if (typeof backendError === 'string' && backendError.length > 0) {
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
            <label for="name" class="text-sm font-medium text-slate-700">
                {{ $t('Company name') }}
            </label>
            <InputText id="name" v-model="form.name" class="w-full" :invalid="Boolean(resolveFieldError('name'))" @blur="touchField('name')" />
            <small v-if="resolveFieldError('name')" class="block text-sm text-rose-500">
                {{ resolveFieldError('name') }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="email" class="text-sm font-medium text-slate-700">{{ $t('Email') }}</label>
            <InputText id="email" v-model="form.email" class="w-full" :invalid="Boolean(resolveFieldError('email'))" @blur="touchField('email')" />
            <small v-if="resolveFieldError('email')" class="block text-sm text-rose-500">{{
                resolveFieldError('email')
            }}</small>
        </div>

        <div class="space-y-2">
            <label for="phone" class="text-sm font-medium text-slate-700">{{ $t('Phone') }}</label>
            <InputText id="phone" v-model="form.phone" class="w-full" :invalid="Boolean(resolveFieldError('phone'))" @blur="touchField('phone')" />
            <small v-if="resolveFieldError('phone')" class="block text-sm text-rose-500">{{
                resolveFieldError('phone')
            }}</small>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="address" class="text-sm font-medium text-slate-700"
                >{{ $t('Address') }}</label
            >
            <Textarea
                id="address"
                v-model="form.address"
                rows="4"
                class="w-full"
                :invalid="Boolean(resolveFieldError('address'))"
                auto-resize
                @blur="touchField('address')"
            />
            <small v-if="resolveFieldError('address')" class="block text-sm text-rose-500">{{
                resolveFieldError('address')
            }}</small>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 text-sm text-slate-700">
                <Checkbox v-model="form.is_active" binary input-id="is_active" @change="touchField('is_active')" />
                <span>{{ $t('Active company') }}</span>
            </label>
            <small v-if="resolveFieldError('is_active')" class="block text-sm text-rose-500">{{
                resolveFieldError('is_active')
            }}</small>
        </div>
    </div>
</template>
