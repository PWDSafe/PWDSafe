<template>
    <div class="relative flex items-center gap-x-2">
        <!-- Hidden import-button so its modal can be triggered programmatically -->
        <div v-if="canUpdate" class="hidden">
            <import-button ref="importRef" :groupid="Number(groupid)" />
        </div>

        <Menu as="div" class="relative inline-block text-left">
            <MenuButton
                class="inline-flex w-full justify-center rounded border px-3 py-1.5 text-sm font-medium text-gray-600 duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/75 dark:border-gray-400 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200"
            >
                <heroicons-ellipsis-horizontal-icon class="h-5 w-5" />
            </MenuButton>

            <transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <MenuItems
                    class="absolute right-0 mt-2 w-56 origin-top-right divide-y divide-gray-200 rounded-md bg-white shadow-lg ring-1 ring-black/5 focus:outline-none dark:divide-gray-800 dark:bg-gray-600"
                >
                    <!-- Sub-group action -->
                    <div v-if="canCreateSubGroup" class="py-1">
                        <MenuItem v-slot="{ active }">
                            <a
                                :href="'/groups/' + groupid + '/subgroups/create'"
                                :class="menuItemClass(active)"
                            >
                                New sub-group
                            </a>
                        </MenuItem>
                    </div>

                    <!-- Group management -->
                    <div v-if="canAdminister || canManageMembers" class="py-1">
                        <MenuItem v-if="canAdminister" v-slot="{ active }">
                            <a
                                :href="'/groups/' + groupid + '/name'"
                                :class="menuItemClass(active)"
                            >
                                Change name
                            </a>
                        </MenuItem>
                        <MenuItem v-if="canManageMembers" v-slot="{ active }">
                            <a
                                :href="'/groups/' + groupid + '/members'"
                                :class="menuItemClass(active)"
                            >
                                Manage members
                            </a>
                        </MenuItem>
                    </div>

                    <!-- Import / Export -->
                    <div v-if="canUpdate" class="py-1">
                        <MenuItem v-slot="{ active }">
                            <button
                                type="button"
                                :class="menuItemClass(active)"
                                @click="triggerImport"
                            >
                                Import credentials
                            </button>
                        </MenuItem>
                        <MenuItem v-slot="{ active }">
                            <button
                                type="button"
                                :class="[menuItemClass(active), exporting ? 'opacity-50 cursor-not-allowed' : '']"
                                :disabled="exporting"
                                @click="triggerExport"
                            >
                                {{ exporting ? 'Exporting…' : 'Export credentials' }}
                            </button>
                        </MenuItem>
                    </div>

                    <!-- Delete -->
                    <div v-if="canDelete" class="py-1">
                        <MenuItem v-slot="{ active }">
                            <a
                                :href="'/groups/' + groupid + '/delete'"
                                :class="[menuItemClass(active), 'text-red-600 dark:text-red-400']"
                            >
                                <heroicons-trash-icon class="h-4 w-4 inline mr-1" />
                                Delete group
                            </a>
                        </MenuItem>
                    </div>
                </MenuItems>
            </transition>
        </Menu>
    </div>
</template>

<script setup lang="ts">
import { ref, PropType } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { loadPrivkey, decryptCredential } from '../vault.js'

const props = defineProps({
    groupid: {
        type: [String, Number] as PropType<string | number>,
        required: true,
    },
    groupname: {
        type: String,
        default: '',
    },
    canUpdate: {
        type: Boolean,
        default: false,
    },
    canAdminister: {
        type: Boolean,
        default: false,
    },
    canManageMembers: {
        type: Boolean,
        default: false,
    },
    canCreateSubGroup: {
        type: Boolean,
        default: false,
    },
    canDelete: {
        type: Boolean,
        default: false,
    },
})

const importRef = ref<any>(null)
const exporting = ref(false)

const triggerImport = () => {
    importRef.value?.openImport()
}

const menuItemClass = (active: boolean) =>
    [
        active ? 'bg-gray-100 dark:bg-gray-700 dark:text-white' : 'dark:bg-gray-600',
        'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
    ].join(' ')

const triggerExport = async () => {
    exporting.value = true
    try {
        const privkeyPem = loadPrivkey()
        const { data: credentials } = await axios.get(`/api/groups/${props.groupid}/export-data`)

        const rows = await Promise.all(
            credentials.map(async (cred: any) => ({
                name: cred.name,
                url: cred.url,
                username: cred.username,
                password: privkeyPem ? await decryptCredential(cred.data, privkeyPem) : cred.data,
                notes: cred.notes,
            })),
        )

        const sanitized = props.groupname
            .replace(/ /g, '_')
            .replace(/[^\w\s\d\-~,;[\]().]/g, '')
            .replace(/\.{2,}/g, '')
            .slice(0, 200)

        const filename = `pwdsafe_export_${sanitized}_${new Date().toISOString().slice(0, 10)}.json`
        const blob = new Blob([JSON.stringify(rows)], { type: 'application/json' })
        const url = URL.createObjectURL(blob)
        const a = document.createElement('a')
        a.href = url
        a.download = filename
        a.click()
        URL.revokeObjectURL(url)
    } finally {
        exporting.value = false
    }
}
</script>
