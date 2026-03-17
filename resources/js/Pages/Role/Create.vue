<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import roleValidationSchema from "@/Validation/schemas/role.json";
import RoleFields from "@/Pages/Role/Partials/RoleFields.vue";
import roleService from "@/Services/RoleService";
import { queueSuccessToast, showErrorToast } from "@/Support/toast/toastHelpers";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import { trans } from "laravel-vue-i18n";
import useVuelidate from "@vuelidate/core";
import Button from "primevue/button";
import Card from "primevue/card";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    guardOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
});

const form = reactive({
    name: "",
    guard_name: props.guardOptions[0]?.value ?? "web",
    permission_ids: [],
});

const errors = reactive({});
const processing = ref(false);
const toast = useToast();
const rules = computed(() =>
    buildVuelidateRules(roleValidationSchema, {
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
        const response = await roleService.store(form);
        queueSuccessToast(response.message);
        router.get(route("roles.index"));
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
    <Head :title="$t('Create Role')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Create Role") }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                            {{ $t("Create") }}
                        </div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ $t("Add a new role") }}
                        </h1>
                        <p class="mt-2 text-slate-500">
                            {{ $t("Create a new role and assign the permissions it should include.") }}
                        </p>
                    </div>

                    <Link :href="route('roles.index')">
                        <Button :label="$t('Back')" icon="pi pi-arrow-left" severity="secondary" outlined />
                    </Link>
                </div>

                <form class="space-y-8" @submit.prevent="submit">
                    <RoleFields
                        :form="form"
                        :errors="errors"
                        :validation="v$"
                        :guard-options="guardOptions"
                        :permission-options="permissionOptions"
                    />

                    <div class="flex justify-end gap-3">
                        <Link :href="route('roles.index')">
                            <Button type="button" :label="$t('Cancel')" severity="secondary" outlined />
                        </Link>
                        <Button type="submit" :label="$t('Create role')" icon="pi pi-check" :loading="processing" />
                    </div>
                </form>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
