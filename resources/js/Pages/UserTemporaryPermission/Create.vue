<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserTemporaryPermissionFields from "@/Pages/UserTemporaryPermission/Partials/UserTemporaryPermissionFields.vue";
import userTemporaryPermissionService from "@/Services/UserTemporaryPermissionService";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import userTemporaryPermissionValidationSchema from "@/Validation/schemas/userTemporaryPermission.json";
import { Head, Link, router } from "@inertiajs/vue3";
import useVuelidate from "@vuelidate/core";
import { trans } from "laravel-vue-i18n";
import { computed, reactive, ref } from "vue";
import Button from "primevue/button";
import Card from "primevue/card";

const props = defineProps({
    userOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
});

const form = reactive({
    user_id: null,
    permission_id: null,
    starts_at: "",
    ends_at: "",
    reason: "",
});
const errors = reactive({});
const processing = ref(false);
const rules = computed(() =>
    buildVuelidateRules(userTemporaryPermissionValidationSchema, {
        translator: trans,
    }),
);
const v$ = useVuelidate(rules, form, { $autoDirty: true });

const submit = async () => {
    processing.value = true;
    Object.keys(errors).forEach((key) => delete errors[key]);

    const isValid = await v$.value.$validate();

    if (!isValid) {
        processing.value = false;
        return;
    }

    try {
        await userTemporaryPermissionService.store(form);
        router.get(route("user-temporary-permissions.index"));
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
    <Head :title="$t('Create Temporary Permission')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Create Temporary Permission") }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                            {{ $t("Access Control") }}
                        </div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ $t("Grant a temporary permission") }}
                        </h1>
                        <p class="mt-2 text-slate-500">
                            {{ $t("Assign a permission directly to a user for a limited validity period.") }}
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
                        <Button
                            type="submit"
                            :label="$t('Create temporary permission')"
                            icon="pi pi-check"
                            :loading="processing"
                        />
                    </div>
                </form>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
