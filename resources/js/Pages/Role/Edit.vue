<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import roleValidationSchema from "@/Validation/schemas/role.json";
import RoleFields from "@/Pages/Role/Partials/RoleFields.vue";
import roleService from "@/Services/RoleService";
import { queueSuccessToast, showErrorToast } from "@/Support/toast/toastHelpers";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, onMounted, reactive, ref } from "vue";
import { trans } from "laravel-vue-i18n";
import useVuelidate from "@vuelidate/core";
import Button from "primevue/button";
import Card from "primevue/card";
import ProgressSpinner from "primevue/progressspinner";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    roleId: { type: Number, required: true },
    guardOptions: { type: Array, default: () => [] },
    permissionOptions: { type: Array, default: () => [] },
});

const loading = ref(true);
const processing = ref(false);
const toast = useToast();
const form = reactive({
    name: "",
    guard_name: props.guardOptions[0]?.value ?? "web",
    permission_ids: [],
});
const errors = reactive({});
const rules = computed(() =>
    buildVuelidateRules(roleValidationSchema, {
        translator: trans,
    }),
);
const v$ = useVuelidate(rules, form, { $autoDirty: true });

const loadRole = async () => {
    loading.value = true;

    try {
        const response = await roleService.show(props.roleId);
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
        const response = await roleService.update(props.roleId, form);
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

onMounted(loadRole);
</script>

<template>
    <Head :title="$t('Edit Role')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Edit Role") }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div v-if="loading" class="flex justify-center py-16">
                    <ProgressSpinner stroke-width="4" />
                </div>

                <template v-else>
                    <div class="mb-8 flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                                {{ $t("Edit") }}
                            </div>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                                {{ $t("Update role") }}
                            </h1>
                            <p class="mt-2 text-slate-500">
                                {{ $t("Edit the selected role and adjust its permission assignments.") }}
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
                            <Button type="submit" :label="$t('Save changes')" icon="pi pi-save" :loading="processing" />
                        </div>
                    </form>
                </template>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
