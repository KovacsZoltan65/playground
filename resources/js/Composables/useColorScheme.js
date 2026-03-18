import { computed, onMounted, ref, watch } from 'vue';

const COLOR_SCHEME_STORAGE_KEY = 'playground-color-scheme';
const LIGHT_SCHEME = 'light';
const DARK_SCHEME = 'dark';

const canUseBrowserApis = typeof window !== 'undefined';

const resolveInitialScheme = () => {
    if (!canUseBrowserApis) {
        return LIGHT_SCHEME;
    }

    const storedScheme = window.localStorage.getItem(COLOR_SCHEME_STORAGE_KEY);

    if (storedScheme === LIGHT_SCHEME || storedScheme === DARK_SCHEME) {
        return storedScheme;
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches
        ? DARK_SCHEME
        : LIGHT_SCHEME;
};

const applyScheme = (scheme) => {
    if (!canUseBrowserApis) {
        return;
    }

    document.documentElement.dataset.theme = scheme;
    window.localStorage.setItem(COLOR_SCHEME_STORAGE_KEY, scheme);
};

export const useColorScheme = () => {
    const colorScheme = ref(resolveInitialScheme());
    const isDarkScheme = computed(() => colorScheme.value === DARK_SCHEME);

    onMounted(() => {
        applyScheme(colorScheme.value);
    });

    watch(colorScheme, (scheme) => {
        applyScheme(scheme);
    });

    const toggleColorScheme = () => {
        colorScheme.value = isDarkScheme.value ? LIGHT_SCHEME : DARK_SCHEME;
    };

    return {
        colorScheme,
        isDarkScheme,
        toggleColorScheme,
    };
};
