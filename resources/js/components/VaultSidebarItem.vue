<template>
    <div>
        <div
            class="flex items-center gap-x-1 group px-3 py-1.5 rounded-md cursor-pointer"
            :class="[
                isActive
                    ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300'
                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600',
                isDragOver ? 'ring-2 ring-inset ring-indigo-500' : '',
            ]"
            @dragover.prevent
            @dragenter="onDragEnter"
            @dragleave="onDragLeave"
            @drop.prevent="onDrop"
        >
            <!-- Expand/collapse chevron -->
            <button
                v-if="node.children.length > 0"
                @click.prevent="expanded = !expanded"
                class="flex-shrink-0 w-4 h-4 text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300"
            >
                <heroicons-chevron-down-icon v-if="expanded" class="w-4 h-4" />
                <heroicons-chevron-right-icon v-else class="w-4 h-4" />
            </button>
            <span v-else class="flex-shrink-0 w-4"></span>

            <!-- Folder icon + name link -->
            <a :href="node.url" class="flex items-center gap-x-1.5 flex-1 min-w-0 text-sm font-medium">
                <heroicons-folder-open-icon v-if="isActive" class="w-4 h-4 flex-shrink-0" />
                <heroicons-folder-icon v-else class="w-4 h-4 flex-shrink-0" />
                <span class="truncate">{{ node.name }}</span>
                <span
                    v-if="node.credentialsCount > 0"
                    class="ml-auto flex-shrink-0 text-xs text-gray-400 dark:text-gray-500 tabular-nums"
                >{{ node.credentialsCount }}</span>
            </a>
        </div>

        <!-- Children -->
        <div v-if="expanded && node.children.length > 0" class="ml-4">
            <vault-sidebar-item
                v-for="child in node.children"
                :key="child.id"
                :node="child"
                :active-group-id="activeGroupId"
            />
        </div>
    </div>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useCredentialDrop } from '../composables/useCredentialDrop.js'

interface SidebarNode {
    id: number
    name: string
    url: string
    credentialsCount: number
    children: SidebarNode[]
}

const props = defineProps<{
    node: SidebarNode
    activeGroupId: number | null
}>()

const isActive = computed(() => props.node.id === props.activeGroupId)

const hasActiveDescendant = (node: SidebarNode, targetId: number | null): boolean => {
    if (!targetId) { return false }
    return node.children.some(
        (child) => child.id === targetId || hasActiveDescendant(child, targetId)
    )
}

const expanded = ref(isActive.value || hasActiveDescendant(props.node, props.activeGroupId))

const { isDragOver, onDragEnter, onDragLeave, onDrop } = useCredentialDrop(props.node.id)
</script>
