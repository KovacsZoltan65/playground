<script setup>
import { ref } from "vue";
import Button from "primevue/button";
import Menu from "primevue/menu";

defineProps({
    items: {
        type: Array,
        required: true,
    },
});

const menu = ref(null);
const menuPt = {
    root: { class: "app-row-action-menu" },
    item: { class: "app-row-action-menu-item" },
    itemContent: { class: "app-row-action-menu-item-content" },
    itemLink: { class: "app-row-action-menu-item-link" },
    itemIcon: { class: "app-row-action-menu-item-icon" },
    itemLabel: { class: "app-row-action-menu-item-label" },
};

const toggle = (event) => {
    menu.value?.toggle(event);
};
</script>

<template>
    <div class="flex justify-end">
        <Button
            icon="pi pi-ellipsis-v"
            severity="secondary"
            text
            rounded
            aria-haspopup="true"
            aria-controls="row-action-menu"
            @click="toggle"
        />
        <Menu id="row-action-menu" ref="menu" :model="items" :pt="menuPt" popup />
    </div>
</template>
