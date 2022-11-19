/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp } from 'vue'
import { VueClipboard } from '@soerenmartius/vue3-clipboard'

import './bootstrap'

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

import DropdownMenu from './components/DropdownMenu.vue'
import DropdownLink from './components/DropdownLink.vue'
import PwdsafeButton from './components/Button.vue'
import PwdsafeAlert from './components/Alert.vue'
import PwdsafeLabel from './components/Label.vue'
import PwdsafeInput from './components/Input.vue'
import PwdsafeTextarea from './components/Textarea.vue'
import PwdsafeSelect from './components/Select.vue'
import CredentialCard from './components/CredentialCard.vue'
import UpdatePermission from './components/UpdatePermission.vue'
import PwdsafeModal from './components/Modal.vue'
import WarningMessage from './components/WarningMessage.vue'

app.component('dropdown-menu', DropdownMenu)
app.component('dropdown-link', DropdownLink)
app.component('pwdsafe-button', PwdsafeButton)
app.component('pwdsafe-alert', PwdsafeAlert)
app.component('pwdsafe-label', PwdsafeLabel)
app.component('pwdsafe-input', PwdsafeInput)
app.component('pwdsafe-textarea', PwdsafeTextarea)
app.component('pwdsafe-select', PwdsafeSelect)
app.component('credential-card', CredentialCard)
app.component('update-permission', UpdatePermission)
app.component('pwdsafe-modal', PwdsafeModal)
app.component('warning-message', WarningMessage)

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

app.mount('#app')
