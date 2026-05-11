import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { existsSync } from 'node:fs'
import { fileURLToPath, URL } from 'node:url'
import tailwindcss from '@tailwindcss/vite'

const defaultApiProxyTarget = existsSync('/.dockerenv')
  ? 'http://host.docker.internal:11000'
  : 'http://localhost:11000'

const apiProxyTarget = process.env.VITE_API_PROXY_TARGET ?? defaultApiProxyTarget

export default defineConfig({
  plugins: [vue(), tailwindcss()],

  server: {
    port: 12000,
    strictPort: true,
    proxy: {
      '/api': {
        target: apiProxyTarget,
        changeOrigin: true,
      },
    },
  },

  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
