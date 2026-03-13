import { mount } from '@vue/test-utils';
import ApplicationLogo from '../../resources/js/Components/ApplicationLogo.vue';

describe('ApplicationLogo', () => {
    it('renders the svg logo', () => {
        const wrapper = mount(ApplicationLogo);

        expect(wrapper.find('svg').exists()).toBe(true);
    });
});
