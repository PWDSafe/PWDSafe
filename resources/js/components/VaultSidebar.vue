<template>
    <div class="py-4 px-2 flex flex-col h-full">
        <div v-if="loading" class="px-3 py-2 text-sm text-gray-400 dark:text-gray-500">
            Loading…
        </div>

        <div v-else-if="error" class="px-3 py-2 text-sm text-red-500">
            Could not load groups.
        </div>

        <template v-else>
            <!-- Private section -->
            <div class="mb-4">
                <div class="flex items-center justify-between px-3 mb-1">
                    <a
                        v-if="privateGroup"
                        :href="privateGroup.url"
                        class="flex items-center gap-x-1.5 text-xs font-semibold uppercase tracking-wider transition duration-150 rounded px-1 -mx-1"
                        :class="[
                            activeGroupId === privateGroup.id
                                ? 'text-indigo-600 dark:text-indigo-400'
                                : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300',
                            privateRootDragOver ? 'ring-2 ring-indigo-500 text-indigo-600 dark:text-indigo-400' : '',
                        ]"
                        @dragover.prevent
                        @dragenter="privateRootOnDragEnter"
                        @dragleave="privateRootOnDragLeave"
                        @drop.prevent="privateRootOnDrop"
                    >
                        <heroicons-lock-closed-icon class="w-3.5 h-3.5 flex-shrink-0" />
                        Private
                    </a>
                    <span v-else class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Private
                    </span>
                    <a
                        v-if="privateGroup"
                        :href="'/groups/' + privateGroup.id + '/subgroups/create'"
                        class="text-gray-400 dark:text-gray-500 hover:text-indigo-500 dark:hover:text-indigo-400 transition duration-150"
                        title="New sub-group"
                    >
                        <heroicons-folder-plus-icon class="w-4 h-4" />
                    </a>
                </div>
                <!-- Children render directly under the label; no tree item for the private root -->
                <vault-sidebar-item
                    v-for="child in (privateGroup?.children ?? [])"
                    :key="child.id"
                    :node="child"
                    :active-group-id="activeGroupId"
                />
            </div>

            <!-- Shared section -->
            <div>
                <div class="flex items-center justify-between px-3 mb-1">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                        Shared
                    </span>
                    <a
                        href="/groups/create"
                        class="text-gray-400 dark:text-gray-500 hover:text-indigo-500 dark:hover:text-indigo-400 transition duration-150"
                        title="New group"
                    >
                        <heroicons-folder-plus-icon class="w-4 h-4" />
                    </a>
                </div>
                <p v-if="sharedGroups.length === 0" class="px-3 text-xs text-gray-400 dark:text-gray-500 italic">
                    No shared groups yet.
                </p>
                <vault-sidebar-item
                    v-for="group in sharedGroups"
                    :key="group.id"
                    :node="group"
                    :active-group-id="activeGroupId"
                />
            </div>

            <!-- Bottom links -->
            <div class="mt-auto pt-4 border-t border-gray-200 dark:border-gray-600">
                <a
                    href="/securitycheck"
                    class="flex items-center gap-x-2 px-3 py-1.5 rounded-md text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-150"
                >
                    <ShieldCheckIcon class="w-4 h-4 flex-shrink-0" />
                    Security check
                </a>
            </div>
        </template>
    </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { ShieldCheckIcon } from '@heroicons/vue/24/outline'
import { useCredentialDrop } from '../composables/useCredentialDrop.js'

interface SidebarNode {
    id: number
    name: string
    url: string
    credentialsCount: number
    children: SidebarNode[]
}

const props = defineProps<{
    activeGroupId: number | null
}>()

const loading = ref(true)
const error = ref(false)
const privateGroup = ref<SidebarNode | null>(null)
const sharedGroups = ref<SidebarNode[]>([])

onMounted(async () => {
    try {
        const response = await fetch('/api/sidebar', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        if (!response.ok) { throw new Error('Failed to load sidebar') }
        const data = await response.json()
        privateGroup.value = data.private
        sharedGroups.value = data.shared
    } catch {
        error.value = true
    } finally {
        loading.value = false
    }
})

const privateGroupId = computed(() => privateGroup.value?.id ?? 0)
const {
    isDragOver: privateRootDragOver,
    onDragEnter: privateRootOnDragEnter,
    onDragLeave: privateRootOnDragLeave,
    onDrop: privateRootOnDrop,
} = useCredentialDrop(privateGroupId)
</script>
