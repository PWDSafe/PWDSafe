<template>
    <a
        :href="group.url"
        class="w-full sm:w-72 card bg-white dark:bg-gray-700 shadow flex justify-between p-3 rounded-md text-base text-gray-900 dark:text-gray-100 border border-gray-200 dark:border-gray-700 hover:border-indigo-500 focus:border-indigo-500 outline-none duration-200 gap-x-2"
        :class="isDragOver ? 'border-indigo-500 ring-2 ring-indigo-500' : ''"
        @dragover.prevent
        @dragenter="onDragEnter"
        @dragleave="onDragLeave"
        @drop.prevent="onDrop"
    >
        {{ group.name }}
        <span class="flex items-start gap-x-1.5 flex-wrap">
            <span
                v-if="group.users_count > 1"
                class="bg-gray-100 dark:bg-gray-600 text-sm text-gray-600 dark:text-gray-300 p-0.5 px-1.5 rounded-md flex items-center gap-x-0.5"
            >
                <heroicons-user-icon class="w-3.5 h-3.5" />{{ group.users_count }}
            </span>
            <span
                v-if="group.children_count > 0"
                class="bg-gray-100 dark:bg-gray-600 text-sm text-gray-600 dark:text-gray-300 p-0.5 px-1.5 rounded-md flex items-center gap-x-0.5"
            >
                <heroicons-folder-open-icon class="w-3.5 h-3.5" />{{ group.children_count }}
            </span>
            <span class="bg-gray-100 dark:bg-gray-600 text-sm text-gray-600 dark:text-gray-300 p-0.5 px-1.5 rounded-md flex items-center gap-x-0.5">
                <heroicons-key-icon class="w-3.5 h-3.5" />{{ group.credentials_count }}
            </span>
        </span>
    </a>
</template>

<script setup lang="ts">
import { useCredentialDrop } from '../composables/useCredentialDrop.js'

interface SubGroup {
    id: number
    name: string
    url: string
    users_count: number
    children_count: number
    credentials_count: number
}

const props = defineProps<{ group: SubGroup }>()

const { isDragOver, onDragEnter, onDragLeave, onDrop } = useCredentialDrop(props.group.id)
</script>
