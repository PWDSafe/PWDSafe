<template>
    <Menu as="div" class="relative inline-block text-left">
        <div>
            <MenuButton
                class="inline-flex w-full justify-center rounded border px-4 py-1.5 text-sm font-medium text-gray-600 duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 dark:border-gray-400 dark:text-gray-400 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-600 dark:hover:text-gray-200"
            >
                <heroicons-cog-6-tooth-icon
                    class="h-5 w-5"
                ></heroicons-cog-6-tooth-icon>
            </MenuButton>
        </div>

        <transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="transform scale-95 opacity-0"
            enter-to-class="transform scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="transform scale-100 opacity-100"
            leave-to-class="transform scale-95 opacity-0"
        >
            <MenuItems
                class="absolute right-0 mt-2 w-56 origin-top-right divide-y divide-gray-200 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:divide-gray-800 dark:bg-gray-600"
            >
                <div class="py-1">
                    <MenuItem v-slot="{ active }" v-if="!ldap">
                        <a
                            :href="'/groups/' + groupid + '/name'"
                            :class="[
                                active
                                    ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
                                    : 'dark:bg-gray-600',
                                'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
                            ]"
                        >
                            Change name
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a
                            :href="'/groups/' + groupid + '/members'"
                            :class="[
                                active
                                    ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
                                    : 'dark:bg-gray-600',
                                'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
                            ]"
                        >
                            Manage members
                        </a>
                    </MenuItem>
                </div>

                <div class="py-1">
                    <MenuItem v-slot="{ active }">
                        <a
                            :href="'/groups/' + groupid + '/delete'"
                            :class="[
                                active
                                    ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
                                    : 'dark:bg-gray-600',
                                'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
                            ]"
                        >
                            <heroicons-trash-icon
                                class="h-5 w-5"
                            ></heroicons-trash-icon>
                            Delete
                        </a>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
<script setup lang="ts">
import { PropType } from 'vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
defineProps({
    groupid: {
        type: String as PropType<String | Number>,
    },
})
</script>
