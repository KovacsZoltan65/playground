<script setup>
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import Textarea from "primevue/textarea";

defineProps({
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <div class="grid gap-5 md:grid-cols-2">
        <div class="space-y-2 md:col-span-2">
            <label for="name" class="text-sm font-medium text-slate-700">
                Company name
            </label>
            <InputText id="name" v-model="form.name" class="w-full" />
            <small v-if="errors.name" class="block text-sm text-rose-500">
                {{ errors.name }}
            </small>
        </div>

        <div class="space-y-2">
            <label for="email" class="text-sm font-medium text-slate-700">Email</label>
            <InputText id="email" v-model="form.email" class="w-full" />
            <small v-if="errors.email" class="block text-sm text-rose-500">{{
                errors.email
            }}</small>
        </div>

        <div class="space-y-2">
            <label for="phone" class="text-sm font-medium text-slate-700">Phone</label>
            <InputText id="phone" v-model="form.phone" class="w-full" />
            <small v-if="errors.phone" class="block text-sm text-rose-500">{{
                errors.phone
            }}</small>
        </div>

        <div class="space-y-2 md:col-span-2">
            <label for="address" class="text-sm font-medium text-slate-700"
                >Address</label
            >
            <Textarea
                id="address"
                v-model="form.address"
                rows="4"
                class="w-full"
                auto-resize
            />
            <small v-if="errors.address" class="block text-sm text-rose-500">{{
                errors.address
            }}</small>
        </div>

        <div class="md:col-span-2">
            <label class="flex items-center gap-3 text-sm text-slate-700">
                <Checkbox v-model="form.is_active" binary input-id="is_active" />
                <span>Active company</span>
            </label>
            <small v-if="errors.is_active" class="block text-sm text-rose-500">{{
                errors.is_active
            }}</small>
        </div>
    </div>
</template>
