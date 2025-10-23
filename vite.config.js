import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
  server: {
    host: '127.0.0.1',
    port: 5173,
    strictPort: true, // falla si el puerto está ocupado
    hmr: { host: '127.0.0.1' },
    // proxy: enviar todas las peticiones normales a Laravel (excepto endpoints internos de Vite)
    proxy: {
      // cualquier ruta que NO empiece por '/@vite' o '/@fs' se proxea a Laravel
      '^/(?!@vite|@fs|favicon.ico|api)': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.js'],
      refresh: true,
    }),
  ],
});