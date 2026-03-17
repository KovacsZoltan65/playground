<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import UsageTipPageFields from '@/Pages/UsageTips/Partials/UsageTipPageFields.vue';
import sidebarTipPageService from '@/Services/SidebarTipPageService';
import { queueSuccessToast, showErrorToast } from '@/Support/toast/toastHelpers';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, reactive, ref } from 'vue';
import Button from 'primevue/button';
import Card from 'primevue/card';
import ProgressSpinner from 'primevue/progressspinner';
import { useToast } from 'primevue/usetoast';

const props = defineProps({
    sidebarTipPageId: {
        type: Number,
        required: true,
    },
    pageTargets: {
        type: Array,
        required: true,
    },
});

const loading = ref(true);
const processing = ref(false);
const toast = useToast();
const form = reactive({
    page_component: '',
    is_visible: true,
    rotation_interval_seconds: 60,
    tips: [],
});
const errors = reactive({});

const loadTipPage = async () => {
    loading.value = true;

    try {
        const response = await sidebarTipPageService.show(props.sidebarTipPageId);
        Object.assign(form, response.data);
    } finally {
        loading.value = false;
    }
};

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    try {
        const response = await sidebarTipPageService.update(props.sidebarTipPageId, form);
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

onMounted(loadTipPage);
</script>

<template>
    <Head :title="$t('Edit usage tips')" />

    <AuthenticatedLayout>
        <template #header>{{ $t('Edit usage tips') }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div v-if="loading" class="flex justify-center py-16">
                    <ProgressSpinner stroke-width="4" />
                </div>

                <template v-else>
                    <div class="mb-8 flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">{{ $t('Edit') }}</div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">{{ $t('Update usage tips') }}</h1>
                            <p class="mt-2 text-slate-500">{{ $t('Adjust sidebar visibility, interval, and the ordered ideas for this page.') }}</p>
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
                            <Button type="submit" :label="$t('Save changes')" icon="pi pi-save" :loading="processing" />
                        </div>
                    </form>
                </template>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
