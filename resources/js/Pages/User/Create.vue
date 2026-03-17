<script setup>
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout.vue";
import UserFields from "@/Pages/User/Partials/UserFields.vue";
import userService from "@/Services/UserService";
import { buildVuelidateRules } from "@/Support/validation/buildVuelidateRules";
import userValidationSchema from "@/Validation/schemas/user.json";
import { Head, Link, router } from "@inertiajs/vue3";
import { computed, reactive, ref } from "vue";
import { trans } from "laravel-vue-i18n";
import useVuelidate from "@vuelidate/core";
import Button from "primevue/button";
import Card from "primevue/card";

const props = defineProps({
    roleOptions: { type: Array, default: () => [] },
});

const form = reactive({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role_ids: [],
});

const errors = reactive({});
const processing = ref(false);
const rules = computed(() =>
    buildVuelidateRules(userValidationSchema, {
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
        await userService.store(form);
        router.get(route("users.index"));
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
    <Head :title="$t('Create User')" />

    <AuthenticatedLayout>
        <template #header>{{ $t("Create User") }}</template>

        <Card class="app-card border-0">
            <template #content>
                <div class="mb-8 flex items-start justify-between gap-4">
                    <div>
                        <div class="text-sm uppercase tracking-[0.3em] text-emerald-600">
                            {{ $t("Create") }}
                        </div>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-slate-950">
                            {{ $t("Add a new user") }}
                        </h1>
                        <p class="mt-2 text-slate-500">
                            {{ $t("Create a new user account and assign the appropriate access roles.") }}
                        </p>
                    </div>

                    <Link :href="route('users.index')">
                        <Button :label="$t('Back')" icon="pi pi-arrow-left" severity="secondary" outlined />
                    </Link>
                </div>

                <form class="space-y-8" @submit.prevent="submit">
                    <UserFields
                        :form="form"
                        :errors="errors"
                        :validation="v$"
                        :role-options="roleOptions"
                        :password-required="true"
                    />

                    <div class="flex justify-end gap-3">
                        <Link :href="route('users.index')">
                            <Button type="button" :label="$t('Cancel')" severity="secondary" outlined />
                        </Link>
                        <Button type="submit" :label="$t('Create user')" icon="pi pi-check" :loading="processing" />
                    </div>
                </form>
            </template>
        </Card>
    </AuthenticatedLayout>
</template>
