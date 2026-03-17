<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import employeeValidationSchema from "@/Validation/schemas/employee.json";
import EmployeeFields from "@/Pages/Employee/Partials/EmployeeFields.vue";
import employeeService from "@/Services/EmployeeService";
import { queueSuccessToast, showErrorToast } from "@/Support/toast/toastHelpers";
import { Head, Link, router } from "@inertiajs/vue3";
import useVuelidate from "@vuelidate/core";
import { trans } from "laravel-vue-i18n";
import { computed, reactive, ref } from "vue";
import Button from "primevue/button";
import Card from "primevue/card";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    companyOptions: {
        type: Array,
        default: () => [],
    },
});

const form = reactive({
    company_id: null,
    name: "",
    email: "",
    active: true,
});

const errors = reactive({});
const processing = ref(false);
const toast = useToast();
const rules = computed(() =>
    buildVuelidateRules(employeeValidationSchema, {
        translator: trans,
    })
);
const v$ = useVuelidate(rules, form, {
    $autoDirty: true,
});

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    const isValid = await v$.value.$validate();

    if (!isValid) {
        processing.value = false;
        return;
    }

    try {
        const response = await employeeService.store(form);
        queueSuccessToast(response.message);
        router.get(route("employees.index"));
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
    <Head :title="$t('Create Employee')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Create Employee") }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                            {{ $t("Create") }}
                        </div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ $t("Add a new employee") }}
                        </h1>
                        <p class="mt-2 text-slate-500">
                            {{ $t("Fill out the form below to create a new employee record.") }}
                        </p>
                    </div>

                    <Link :href="route('employees.index')">
                        <Button :label="$t('Back')" icon="pi pi-arrow-left" severity="secondary" outlined />
                    </Link>
                </div>

                <form class="space-y-8" @submit.prevent="submit">
                    <EmployeeFields
                        :form="form"
                        :errors="errors"
                        :validation="v$"
                        :company-options="companyOptions"
                    />

                    <div class="flex justify-end gap-3">
                        <Link :href="route('employees.index')">
                            <Button type="button" :label="$t('Cancel')" severity="secondary" outlined />
                        </Link>
                        <Button type="submit" :label="$t('Create employee')" icon="pi pi-check" :loading="processing" />
                    </div>
                </form>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
