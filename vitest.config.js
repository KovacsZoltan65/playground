import { defineConfig, mergeConfig } from 'vitest/config';
import viteConfig from './vite.config';

export default mergeConfig(
    viteConfig,
    defineConfig({
        test: {
            environment: 'jsdom',
            globals: true,
            setupFiles: ['./tests/frontend/setup.js'],
            include: ['tests/frontend/**/*.spec.{js,ts}'],
        },
    }),
);
