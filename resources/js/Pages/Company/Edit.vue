<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { buildVuelidateRules } from '@/Support/validation/buildVuelidateRules';
import companyValidationSchema from '@/Validation/schemas/company.json';
import CompanyFields from '@/Pages/Company/Partials/CompanyFields.vue';
import companyService from '@/Services/CompanyService';
import { queueSuccessToast, showErrorToast } from '@/Support/toast/toastHelpers';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onMounted, reactive, ref } from 'vue';
import { trans } from 'laravel-vue-i18n';
import useVuelidate from '@vuelidate/core';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    companyId: {
        type: Number,
        required: true,
    },
});

const loading = ref(true);
const processing = ref(false);
const toast = useToast();
const form = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
});
const errors = reactive({});
const rules = computed(() =>
    buildVuelidateRules(companyValidationSchema, {
        translator: trans,
    }),
);
const v$ = useVuelidate(rules, form, {
    $autoDirty: true,
});

const loadCompany = async () => {
    loading.value = true;

    try {
        const response = await companyService.show(props.companyId);
        Object.assign(form, response.data);
        v$.value.$reset();
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    const isValid = await v$.value.$validate();

    if (!isValid) {
        processing.value = false;
        return;
    }

    try {
        const response = await companyService.update(props.companyId, form);
        queueSuccessToast(response.message);
        router.get(route('companies.index'));
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

onMounted(loadCompany);
</script>

<template>
    <Head :title="$t('Edit Company')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Edit Company') }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div v-if="loading" class="flex justify-center py-16">
                    <ProgressSpinner stroke-width="4" />
                </div>

                <template v-else>
                    <div class="mb-8 flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">{{ $t('Edit') }}</div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $t('Update company') }}</h1>
                            <p class="mt-2 text-slate-500">{{ $t('Edit the selected company record and save your changes.') }}</p>
                        </div>

                        <Link :href="route('companies.index')">
                            <Button :label="$t('Back')" icon="pi pi-arrow-left" severity="secondary" outlined />
                        </Link>
                    </div>

                    <form class="space-y-8" @submit.prevent="submit">
                        <CompanyFields :form="form" :errors="errors" :validation="v$" />

                        <div class="flex justify-end gap-3">
                            <Link :href="route('companies.index')">
                                <Button type="button" :label="$t('Cancel')" severity="secondary" outlined />
                            </Link>
                            <Button type="submit" :label="$t('Save changes')" icon="pi pi-save" :loading="processing" />
                        </div>
                    </form>
                </template>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
