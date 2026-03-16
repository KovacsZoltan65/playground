import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { vi } from 'vitest';
import { i18nVue } from 'laravel-vue-i18n';
import SidebarUsageTips from '../../resources/js/Components/SidebarUsageTips.vue';

const createWrapper = (config) =>
    mount(SidebarUsageTips, {
        props: {
            config,
        },
        global: {
            plugins: [
                [
                    i18nVue,
                    {
                        resolve: async () => ({
                            default: {
                                'Usage ideas': 'Usage ideas',
                            },
                        }),
                    },
                ],
            ],
        },
    });

describe('SidebarUsageTips', () => {
    it('hides the tip panel when visibility is disabled', () => {
        const wrapper = createWrapper({
            visible: false,
            rotationIntervalMs: 60000,
            tips: ['First'],
        });

        expect(wrapper.text()).toBe('');
    });

    it('rotates through page tips based on the configured interval', async () => {
        vi.useFakeTimers();

        const wrapper = createWrapper({
            visible: true,
            rotationIntervalMs: 1000,
            tips: ['First', 'Second'],
        });

        expect(wrapper.text()).toContain('First');

        vi.advanceTimersByTime(1000);
        await nextTick();

        expect(wrapper.text()).toContain('Second');

        wrapper.unmount();
        vi.useRealTimers();
    });
});
