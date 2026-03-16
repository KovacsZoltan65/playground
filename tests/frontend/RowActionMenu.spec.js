import { defineComponent, h } from 'vue';
import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';

import RowActionMenu from '@/Components/RowActionMenu.vue';

const ButtonStub = defineComponent({
    name: 'ButtonStub',
    props: {
        ariaControls: {
            type: String,
            default: null,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
    },
    emits: ['click'],
    setup(props, { emit }) {
        return () =>
            h(
                'button',
                {
                    'aria-controls': props.ariaControls,
                    disabled: props.disabled,
                    onClick: (event) => emit('click', event),
                },
                'actions',
            );
    },
});

const MenuStub = defineComponent({
    name: 'MenuStub',
    props: {
        id: {
            type: String,
            required: true,
        },
        model: {
            type: Array,
            default: () => [],
        },
    },
    setup(props, { expose, slots }) {
        expose({
            toggle: vi.fn(),
        });

        return () =>
            h(
                'div',
                { id: props.id },
                props.model.map((item, index) =>
                    slots.item?.({
                        item,
                        props: {
                            action: {
                                onClick: item.command,
                                'data-test-id': `menu-item-${index}`,
                            },
                        },
                    }),
                ),
            );
    },
});

function mountMenu(items) {
    return mount(RowActionMenu, {
        props: { items },
        global: {
            stubs: {
                Button: ButtonStub,
                Menu: MenuStub,
            },
        },
    });
}

describe('RowActionMenu', () => {
    it('calls the configured command when a menu item is clicked', async () => {
        const command = vi.fn();
        const wrapper = mountMenu([
            { label: 'Edit', icon: 'pi pi-pencil', command },
        ]);

        await wrapper.get('[data-test-id="menu-item-0"]').trigger('click');

        expect(command).toHaveBeenCalledTimes(1);
    });

    it('creates a unique menu id for each instance', () => {
        const firstWrapper = mountMenu([]);
        const secondWrapper = mountMenu([]);

        expect(firstWrapper.get('button').attributes('aria-controls')).not.toBe(
            secondWrapper.get('button').attributes('aria-controls'),
        );
    });

    it('applies semantic success and danger styles', () => {
        const wrapper = mountMenu([
            { label: 'Activate', icon: 'pi pi-check-circle', severity: 'success', command: vi.fn() },
            { label: 'Delete', icon: 'pi pi-trash', severity: 'danger', command: vi.fn() },
        ]);

        const items = wrapper.findAll('[data-test-id]');

        expect(items[0].classes()).toContain('text-green-900');
        expect(items[1].classes()).toContain('text-red-900');
    });
});
