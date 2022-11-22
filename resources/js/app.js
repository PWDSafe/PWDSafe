/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import { createApp } from 'vue'
import { VueClipboard } from '@soerenmartius/vue3-clipboard'
import {
    PlusIcon,
    ArrowDownOnSquareIcon,
    ArrowUpOnSquareIcon,
    UserIcon,
    Cog6ToothIcon,
    TrashIcon,
    KeyIcon,
} from '@heroicons/vue/24/outline'

import './bootstrap'

const app = createApp({
    data() {
        return { mobileMenuOpen: false }
    },
})

app.use(VueClipboard)

app.component('heroicons-plus-icon', PlusIcon)
app.component('heroicons-arrow-down-on-square-icon', ArrowDownOnSquareIcon)
app.component('heroicons-arrow-up-on-square-icon', ArrowUpOnSquareIcon)
app.component('heroicons-user-icon', UserIcon)
app.component('heroicons-cog-6-tooth-icon', Cog6ToothIcon)
app.component('heroicons-trash-icon', TrashIcon)
app.component('heroicons-key-icon', KeyIcon)

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
import ProfileMenu from './components/ProfileMenu.vue'
import GroupManagementMenu from './components/GroupManagementMenu.vue'

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
app.component('profile-menu', ProfileMenu)
app.component('group-management-menu', GroupManagementMenu)

app.mount('#app')
