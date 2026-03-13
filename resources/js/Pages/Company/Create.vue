<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import CompanyFields from '@/Pages/Company/Partials/CompanyFields.vue';
import companyService from '@/Services/CompanyService';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';

const form = reactive({
    name: '',
    email: '',
    phone: '',
    address: '',
    is_active: true,
});

const errors = reactive({});
const processing = ref(false);

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        await companyService.store(form);
        router.get(route('companies.index'));
    } catch (error) {
        if (error.response?.status === 422) {
            Object.assign(errors, error.response.data.errors);
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <Head title="Create Company" />

    <AuthenticatedLayout>
        <template #header>Create Company</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">Create</div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">Add a new company</h1>
                        <p class="mt-2 text-slate-500">Fill out the form below to create a new company record.</p>
                    </div>

                    <Link :href="route('companies.index')">
                        <Button label="Back" icon="pi pi-arrow-left" severity="secondary" outlined />
                    </Link>
                </div>

                <form class="space-y-8" @submit.prevent="submit">
                    <CompanyFields :form="form" :errors="errors" />

                    <div class="flex justify-end gap-3">
                        <Link :href="route('companies.index')">
                            <Button type="button" label="Cancel" severity="secondary" outlined />
                        </Link>
                        <Button type="submit" label="Create company" icon="pi pi-check" :loading="processing" />
                    </div>
                </form>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
