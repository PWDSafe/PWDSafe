<template>
    <Menu as="div" class="relative inline-block text-left">
        <div>
            <MenuButton
                class="inline-flex w-full justify-center px-4 py-2 text-sm font-medium text-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75 dark:text-gray-300 dark:hover:text-gray-200"
            >
                <heroicons-user-icon class="mr-1 h-5 w-5"></heroicons-user-icon>
                {{ username }}
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
                class="absolute right-0 z-10 mt-2 w-56 origin-top-right divide-y divide-gray-200 rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:divide-gray-800 dark:bg-gray-600"
            >
                <div class="py-1">
                    <MenuItem v-slot="{ active }" v-if="!ldap">
                        <a
                            href="/changepwd"
                            :class="[
                                active
                                    ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
                                    : 'dark:bg-gray-600',
                                'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
                            ]"
                        >
                            Change password
                        </a>
                    </MenuItem>
                    <MenuItem v-slot="{ active }">
                        <a
                            href="/settings/twofa"
                            :class="[
                                active
                                    ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
                                    : 'dark:bg-gray-600',
                                'group flex w-full items-center px-4 py-2 text-sm text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700',
                            ]"
                        >
                            Two factor authentication
                        </a>
                    </MenuItem>
                </div>

                <div class="py-1">
                    <MenuItem v-slot="{ active }">
                        <form method="post" action="/logout">
                            <input type="hidden" name="_token" :value="csrf" />
                            <button
                                type="submit"
                                class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 transition duration-150 ease-in-out hover:bg-gray-100 focus:bg-gray-100 focus:outline-none dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-gray-200 dark:focus:bg-gray-700"
                                :class="[
                                    active
                                        ? 'bg-gray-100 dark:bg-gray-700 dark:text-white'
                                        : 'dark:bg-gray-600',
                                ]"
                            >
                                Logout
                            </button>
                        </form>
                    </MenuItem>
                </div>
            </MenuItems>
        </transition>
    </Menu>
</template>
<script setup lang="ts">
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'

defineProps({
    ldap: {
        type: Boolean,
        required: true,
    },
    csrf: {
        type: String,
        required: true,
    },
    username: {
        type: String,
        required: true,
    },
})
</script>
