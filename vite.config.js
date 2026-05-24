import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        tailwindcss(),
        laravel(['resources/css/app.css', 'resources/js/app.js', 'resources/js/login.js', 'resources/js/otp.js', 'resources/js/changepwd.js', 'resources/js/register.js', 'resources/js/vault-setup.js', 'resources/js/vault-unlock.js']),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
})
