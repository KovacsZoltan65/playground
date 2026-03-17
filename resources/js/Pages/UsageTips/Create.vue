<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UsageTipPageFields from '@/Pages/UsageTips/Partials/UsageTipPageFields.vue';
import sidebarTipPageService from '@/Services/SidebarTipPageService';
import { queueSuccessToast, showErrorToast } from '@/Support/toast/toastHelpers';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    pageTargets: {
        type: Array,
        required: true,
    },
});

const form = reactive({
    page_component: '',
    is_visible: true,
    rotation_interval_seconds: 60,
    tips: [
        {
            id: null,
            content: '',
            sort_order: 1,
            is_active: true,
        },
    ],
});
const errors = reactive({});
const processing = ref(false);
const toast = useToast();

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const response = await sidebarTipPageService.store(form);
        queueSuccessToast(response.message);
        router.get(route('usage-tips.index'));
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
    <Head :title="$t('Create usage tips')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Create usage tips') }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">{{ $t('Create') }}</div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $t('Add usage tips for a page') }}</h1>
                        <p class="mt-2 text-slate-500">{{ $t('Select a page, configure the sidebar panel, then add the ideas to rotate.') }}</p>
                    </div>

                    <Link :href="route('usage-tips.index')">
                        <Button :label="$t('Back')" icon="pi pi-arrow-left" severity="secondary" outlined />
                    </Link>
                </div>

                <form class="space-y-8" @submit.prevent="submit">
                    <UsageTipPageFields :form="form" :errors="errors" :page-targets="pageTargets" />

                    <div class="flex justify-end gap-3">
                        <Link :href="route('usage-tips.index')">
                            <Button type="button" :label="$t('Cancel')" severity="secondary" outlined />
                        </Link>
                        <Button type="submit" :label="$t('Create usage tips')" icon="pi pi-check" :loading="processing" />
                    </div>
                </form>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
