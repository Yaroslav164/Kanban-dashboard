import vue from '@vitejs/plugin-vue'
import laravel from 'laravel-vite-plugin'
import { configDefaults, defineConfig } from 'vitest/config'

export default defineConfig({
  plugins: [laravel({ input: 'resources/js/app.ts', refresh: true }), vue()],
  test: {
    environment: 'jsdom',
    exclude: [...configDefaults.exclude, '**/e2e/**'],
  },
})
