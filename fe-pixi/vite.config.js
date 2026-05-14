import { defineConfig } from 'vite';

export default defineConfig({
	base: '/',
  css: {
    lightningcss: {
      errorRecovery: true,
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
      },
    },
  },
});
