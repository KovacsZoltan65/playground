<script setup>
import InputError from '@/Components/InputError.vue';
import { computed } from 'vue';
import { currentLocale, trans } from 'laravel-vue-i18n';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputNumber from 'primevue/inputnumber';
import Select from 'primevue/select';
import Textarea from 'primevue/textarea';

const props = defineProps({
    form: {
        type: Object,
        required: true,
    },
    errors: {
        type: Object,
        required: true,
    },
    pageTargets: {
        type: Array,
        required: true,
    },
});

const targetOptions = computed(() => {
    currentLocale.value;

    return props.pageTargets.map((target) => ({
        value: target.component,
        label: trans(target.label_key),
    }));
});

const getErrorMessage = (key) => props.errors[key]?.[0] ?? props.errors[key];

const addTip = () => {
    props.form.tips.push({
        id: null,
        content: '',
        sort_order: props.form.tips.length + 1,
        is_active: true,
    });
};

const removeTip = (index) => {
    props.form.tips.splice(index, 1);

    props.form.tips.forEach((tip, tipIndex) => {
        if (!tip.sort_order || tip.sort_order > props.form.tips.length + 1) {
            tip.sort_order = tipIndex + 1;
        }
    });
};
</script>

<template>
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">{{ $t('Target page') }}</label>
            <Select
                v-model="form.page_component"
                :options="targetOptions"
                option-label="label"
                option-value="value"
                class="w-full"
            />
            <InputError :message="getErrorMessage('page_component')" />
        </div>

        <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">{{ $t('Idea rotation in seconds') }}</label>
            <InputNumber
                v-model="form.rotation_interval_seconds"
                :min="5"
                :max="3600"
                :use-grouping="false"
                class="w-full"
                input-class="w-full"
            />
            <InputError :message="getErrorMessage('rotation_interval_seconds')" />
        </div>
    </div>

    <div class="space-y-2">
        <label class="inline-flex items-center gap-3">
            <Checkbox v-model="form.is_visible" binary />
            <span class="text-sm font-medium text-slate-700">{{ $t('Show idea panel in the sidebar') }}</span>
        </label>
        <InputError :message="getErrorMessage('is_visible')" />
    </div>

    <div class="space-y-4">
        <div class="flex items-center justify-between gap-4">
            <div>
                <div class="text-sm font-medium text-slate-700">{{ $t('Ideas') }}</div>
                <div class="text-sm text-slate-500">{{ $t('The sidebar displays active ideas in ascending sort order.') }}</div>
            </div>

            <Button type="button" :label="$t('Add idea')" icon="pi pi-plus" severity="secondary" outlined @click="addTip" />
        </div>

        <InputError :message="getErrorMessage('tips')" />

        <div
            v-for="(tip, index) in form.tips"
            :key="tip.id ?? `new-${index}`"
            class="rounded-2xl border border-slate-200/70 bg-slate-50/80 p-5"
        >
            <div class="grid gap-4 lg:grid-cols-[1fr_180px_180px]">
                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">{{ $t('Idea text') }}</label>
                    <Textarea v-model="tip.content" rows="4" class="w-full" auto-resize />
                    <InputError :message="getErrorMessage(`tips.${index}.content`)" />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-medium text-slate-700">{{ $t('Sort order') }}</label>
                    <InputNumber
                        v-model="tip.sort_order"
                        :min="1"
                        :use-grouping="false"
                        class="w-full"
                        input-class="w-full"
                    />
                    <InputError :message="getErrorMessage(`tips.${index}.sort_order`)" />
                </div>

                <div class="flex flex-col justify-between gap-4">
                    <div class="space-y-2">
                        <label class="inline-flex items-center gap-3">
                            <Checkbox v-model="tip.is_active" binary />
                            <span class="text-sm font-medium text-slate-700">{{ $t('Active') }}</span>
                        </label>
                        <InputError :message="getErrorMessage(`tips.${index}.is_active`)" />
                    </div>

                    <Button
                        type="button"
                        :label="$t('Remove idea')"
                        icon="pi pi-trash"
                        severity="danger"
                        text
                        @click="removeTip(index)"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
