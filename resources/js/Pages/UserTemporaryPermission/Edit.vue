<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserTemporaryPermissionFields from "@/Pages/UserTemporaryPermission/Partials/UserTemporaryPermissionFields.vue";
import userTemporaryPermissionService from "@/Services/UserTemporaryPermissionService";
import { queueSuccessToast, showErrorToast } from "@/Support/toast/toastHelpers";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import userTemporaryPermissionValidationSchema from "@/Validation/schemas/userTemporaryPermission.json";
import { Head, Link, router } from "@inertiajs/vue3";
import useVuelidate from "@vuelidate/core";
import { trans } from "laravel-vue-i18n";
import { computed, onMounted, reactive, ref } from "vue";
import Button from "primevue/button";
import Card from "primevue/card";
import ProgressSpinner from "primevue/progressspinner";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    userTemporaryPermissionId: { type: Number, required: true },
    userOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
});

const loading = ref(true);
const processing = ref(false);
const toast = useToast();
const form = reactive({
    user_id: null,
    permission_id: null,
    starts_at: "",
    ends_at: "",
    reason: "",
});
const errors = reactive({});
const rules = computed(() =>
    buildVuelidateRules(userTemporaryPermissionValidationSchema, {
        translator: trans,
    }),
);
const v$ = useVuelidate(rules, form, { $autoDirty: true });

const loadAssignment = async () => {
    loading.value = true;

    try {
        const response = await userTemporaryPermissionService.show(props.userTemporaryPermissionId);
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
        const response = await userTemporaryPermissionService.update(props.userTemporaryPermissionId, form);
        queueSuccessToast(response.message);
        router.get(route("user-temporary-permissions.index"));
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

onMounted(loadAssignment);
</script>

<template>
    <Head :title="$t('Edit Temporary Permission')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Edit Temporary Permission") }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div v-if="loading" class="flex justify-center py-16">
                    <ProgressSpinner stroke-width="4" />
                </div>

                <template v-else>
                    <div class="mb-8 flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                                {{ $t("Access Control") }}
                            </div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                {{ $t("Update temporary permission") }}
                            </h1>
                            <p class="mt-2 text-slate-500">
                                {{ $t("Adjust the selected temporary permission assignment and save your changes.") }}
                            </p>
                        </div>

                        <Link :href="route('user-temporary-permissions.index')">
                            <Button :label="$t('Back')" icon="pi pi-arrow-left" severity="secondary" outlined />
                        </Link>
                    </div>

                    <form class="space-y-8" @submit.prevent="submit">
                        <UserTemporaryPermissionFields
                            :form="form"
                            :errors="errors"
                            :validation="v$"
                            :user-options="userOptions"
                            :permission-options="permissionOptions"
                        />

                        <div class="flex justify-end gap-3">
                            <Link :href="route('user-temporary-permissions.index')">
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
