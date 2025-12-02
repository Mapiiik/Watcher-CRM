import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    tailwindcss(),
  ],
  build: {
    outDir: 'webroot/css',
    emptyOutDir: false,
    rollupOptions: {
      input: 'resources/css/app.css',
      output: {
        assetFileNames: 'app.css'
      }
    }
  }
})
