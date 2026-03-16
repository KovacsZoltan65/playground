<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { trans } from 'laravel-vue-i18n';

const props = defineProps({
    config: {
        type: Object,
        required: true,
    },
});

const currentTipIndex = ref(0);
let rotationTimer = null;

const tips = computed(() => props.config.tips ?? []);
const hasMultipleTips = computed(() => tips.value.length > 1);
const currentTip = computed(() => tips.value[currentTipIndex.value] ?? null);
const shouldRender = computed(
    () => props.config.visible && tips.value.length > 0,
);

const clearRotationTimer = () => {
    if (rotationTimer !== null) {
        window.clearInterval(rotationTimer);
        rotationTimer = null;
    }
};

const startRotation = () => {
    clearRotationTimer();

    if (!props.config.visible || !hasMultipleTips.value) {
        return;
    }

    rotationTimer = window.setInterval(() => {
        currentTipIndex.value =
            (currentTipIndex.value + 1) % tips.value.length;
    }, props.config.rotationIntervalMs);
};

watch(
    () => [
        props.config.visible,
        props.config.rotationIntervalMs,
        props.config.tips.join('|'),
    ],
    () => {
        currentTipIndex.value = 0;
        startRotation();
    },
    { immediate: true },
);

onBeforeUnmount(() => {
    clearRotationTimer();
});
</script>

<template>
    <div
        v-if="shouldRender"
        class="mt-auto rounded-[2rem] bg-gradient-to-br from-emerald-500/20 to-sky-400/20 p-5 ring-1 ring-white/10"
    >
        <div class="mb-3 text-sm font-semibold">{{ $t('Usage ideas') }}</div>
        <p class="text-sm leading-6 text-slate-300">
            {{ currentTip }}
        </p>
    </div>
</template>
