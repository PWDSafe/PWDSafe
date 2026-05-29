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
    ChevronRightIcon,
    ChevronDownIcon,
    FolderIcon,
    FolderOpenIcon,
    FolderPlusIcon,
    EllipsisHorizontalIcon,
    ClipboardDocumentListIcon,
    LockClosedIcon,
} from '@heroicons/vue/24/outline'

import './bootstrap'

const app = createApp({
    data() {
        return { mobileMenuOpen: false, sidebarOpen: window.innerWidth >= 1024 }
    },
    mounted() {
        window.addEventListener('resize', this.handleResize)
    },
    beforeUnmount() {
        window.removeEventListener('resize', this.handleResize)
    },
    methods: {
        handleResize() {
            this.sidebarOpen = window.innerWidth >= 1024
        },
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
app.component('heroicons-chevron-right-icon', ChevronRightIcon)
app.component('heroicons-chevron-down-icon', ChevronDownIcon)
app.component('heroicons-folder-icon', FolderIcon)
app.component('heroicons-folder-open-icon', FolderOpenIcon)
app.component('heroicons-folder-plus-icon', FolderPlusIcon)
app.component('heroicons-ellipsis-horizontal-icon', EllipsisHorizontalIcon)
app.component('heroicons-clipboard-document-list-icon', ClipboardDocumentListIcon)
app.component('heroicons-lock-closed-icon', LockClosedIcon)

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
import PasswordGenerator from './components/PasswordGenerator.vue'
import AddCredentialsForm from './components/AddCredentialsForm.vue'
import SecurityCheck from './components/SecurityCheck.vue'
import AddGroupMember from './components/AddGroupMember.vue'
import ExportButton from './components/ExportButton.vue'
import ImportButton from './components/ImportButton.vue'
import AdminAuthSettings from './components/AdminAuthSettings.vue'
import AdminGeneralSettings from './components/AdminGeneralSettings.vue'
import AdminUsers from './components/AdminUsers.vue'
import Toast from './components/Toast.vue'
import VaultSidebar from './components/VaultSidebar.vue'
import VaultSidebarItem from './components/VaultSidebarItem.vue'
import GroupActionsMenu from './components/GroupActionsMenu.vue'
import CredentialTable from './components/CredentialTable.vue'
import VaultUnlockModal from './components/VaultUnlockModal.vue'
import SubGroupCard from './components/SubGroupCard.vue'

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
app.component('pwdsafe-passwordgen', PasswordGenerator)
app.component('add-credentials-form', AddCredentialsForm)
app.component('security-check', SecurityCheck)
app.component('add-group-member', AddGroupMember)
app.component('export-button', ExportButton)
app.component('import-button', ImportButton)
app.component('admin-auth-settings', AdminAuthSettings)
app.component('admin-general-settings', AdminGeneralSettings)
app.component('admin-users', AdminUsers)
app.component('toast', Toast)
app.component('vault-sidebar', VaultSidebar)
app.component('vault-sidebar-item', VaultSidebarItem)
app.component('group-actions-menu', GroupActionsMenu)
app.component('credential-table', CredentialTable)
app.component('vault-unlock-modal', VaultUnlockModal)
app.component('sub-group-card', SubGroupCard)

app.mount('#app')
