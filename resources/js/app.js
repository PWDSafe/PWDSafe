/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp } from 'vue'
import { VueClipboard } from '@soerenmartius/vue3-clipboard'

require('./bootstrap')

const app = createApp({
    data() {
        return { mobileMenuOpen: false }
    },
})
app.use(VueClipboard)

import {
    PlusIcon,
    ArrowDownOnSquareIcon,
    ArrowUpOnSquareIcon,
    UserIcon,
    Cog6ToothIcon,
    TrashIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline'

app.component('heroicons-plus-icon', PlusIcon)
app.component('heroicons-arrow-down-on-square-icon', ArrowDownOnSquareIcon)
app.component('heroicons-arrow-up-on-square-icon', ArrowUpOnSquareIcon)
app.component('heroicons-user-icon', UserIcon)
app.component('heroicons-cog-6-tooth-icon', Cog6ToothIcon)
app.component('heroicons-trash-icon', TrashIcon)
app.component('heroicons-key-icon', KeyIcon)

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i)
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default))

app.component('dropdown-menu', require('./components/DropdownMenu.vue').default)
app.component('dropdown-link', require('./components/DropdownLink.vue').default)
app.component('pwdsafe-button', require('./components/Button.vue').default)
app.component('pwdsafe-alert', require('./components/Alert.vue').default)
app.component('pwdsafe-label', require('./components/Label.vue').default)
app.component('pwdsafe-input', require('./components/Input.vue').default)
app.component('pwdsafe-textarea', require('./components/Textarea.vue').default)
app.component('pwdsafe-select', require('./components/Select.vue').default)
app.component(
    'credential-card',
    require('./components/CredentialCard.vue').default
)
app.component(
    'update-permission',
    require('./components/UpdatePermission.vue').default
)
app.component('pwdsafe-modal', require('./components/Modal.vue').default)
app.component(
    'warning-message',
    require('./components/WarningMessage.vue').default
)

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

app.mount('#app')
