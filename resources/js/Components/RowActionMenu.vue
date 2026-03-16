<script setup>
import { computed, getCurrentInstance, ref } from 'vue';
import Button from 'primevue/button';
import Menu from 'primevue/menu';

const props = defineProps({
    items: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const menuRef = ref(null);
const instance = getCurrentInstance();
const menuId = `row-action-menu-${instance?.uid ?? Math.random().toString(36).slice(2)}`;

const normalizedItems = computed(() => props.items.filter((item) => item && item.visible !== false));

function toggleMenu(event) {
    if (props.disabled) {
        return;
    }

    menuRef.value?.toggle(event);
}

function resolveSeverity(item) {
    if (item.severity) {
        return item.severity;
    }

    if (item.icon === 'pi pi-check-circle') {
        return 'success';
    }

    if (item.icon === 'pi pi-eye-slash' || item.icon === 'pi pi-trash') {
        return 'danger';
    }

    return null;
}

function resolveItemClasses(item) {
    const severity = resolveSeverity(item);

    return [
        'flex items-center gap-2 rounded-md px-3 py-2 text-sm transition-colors',
        item.disabled ? 'cursor-not-allowed opacity-50' : 'cursor-pointer',
        severity === 'success' ? 'text-green-900 hover:bg-green-100 focus:bg-green-100' : null,
        severity === 'danger' ? 'text-red-900 hover:bg-red-100 focus:bg-red-100' : null,
        item.itemClass ?? null,
    ];
}
</script>

<template>
    <div class="inline-flex">
        <Button
            icon="pi pi-ellipsis-v"
            severity="secondary"
            text
            rounded
            aria-haspopup="true"
            :aria-controls="menuId"
            :disabled="disabled"
            @click="toggleMenu"
        />

        <Menu
            :id="menuId"
            ref="menuRef"
            :model="normalizedItems"
            popup
        >
            <template #item="{ item, props: menuItemProps }">
                <a
                    v-bind="menuItemProps.action"
                    class="w-full"
                    :class="resolveItemClasses(item)"
                >
                    <i
                        v-if="item.icon"
                        :class="item.icon"
                    />
                    <span>{{ item.label }}</span>
                </a>
            </template>
        </Menu>
    </div>
</template>
